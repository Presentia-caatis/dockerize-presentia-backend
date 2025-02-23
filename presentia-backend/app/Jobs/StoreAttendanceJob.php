<?php

namespace App\Jobs;

use App\Models\Attendance;
use App\Models\AttendanceWindow;
use App\Models\CheckInStatus;
use App\Models\FailedStoreAttendanceJob;
use App\Models\Scopes\SchoolScope;
use App\Models\Student;
use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use function App\Helpers\convert_timezone_to_utc;
use function App\Helpers\convert_utc_to_timezone;
use function App\Helpers\current_school_timezone;

class StoreAttendanceJob implements ShouldQueue
{
    use Queueable;

    protected $jsonInput;

    /**
     * Create a new job instance.
     */
    public function __construct(array $jsonInput)
    {
        $this->jsonInput = $jsonInput;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (empty($this->jsonInput)) {
            return;
        }

        $studentId = $this->jsonInput[0]['id'];
        $schoolId = Student::withoutGlobalScope(SchoolScope::class)->find($studentId)?->school_id;

        if (!$schoolId) {
            $this->logFailure($studentId, Carbon::parse($this->jsonInput[0]['date']),'Invalid student ID');
            return;
        }

        config(['school.id' => $schoolId]);
        $schoolTimezone = current_school_timezone() ?? 'Asia/Jakarta';

        $inputDates = array_unique(array_map(function ($item) use ($schoolTimezone) {
            return Carbon::parse($item['date'])->setTimezone($schoolTimezone)->format('Y-m-d');
        }, $this->jsonInput));

        $attendanceWindows = AttendanceWindow::withoutGlobalScope(SchoolScope::class)
            ->where('school_id', $schoolId)
            ->whereIn('date', $inputDates)
            ->get()
            ->keyBy('date');

        $checkInTypes = CheckInStatus::withoutGlobalScope(SchoolScope::class)
            ->where('school_id', $schoolId)
            ->where('is_active', true)
            ->where('late_duration', '!=', -1)
            ->orderBy('late_duration', 'asc')
            ->get();

        $absenceCheckInStatus = CheckInStatus::withoutGlobalScope(SchoolScope::class)
            ->where('school_id', $schoolId)
            ->where('late_duration', -1)
            ->first();

        foreach ($this->jsonInput as $student) {
            $isInCheckInTimeRange = false;
            $studentId = $student['id'];
            $checkTime = Carbon::parse($student['date']);
            $formattedDate = Carbon::parse($student['date'])->setTimezone($schoolTimezone)->format('Y-m-d');

            $attendanceWindow = $attendanceWindows[$formattedDate] ?? null;
            if (!$attendanceWindow) {
                $this->logFailure($studentId,$checkTime, "No attendance window found for date $formattedDate");
                continue;
            }

            $checkInStart = convert_timezone_to_utc($attendanceWindow->date . ' ' . $attendanceWindow->check_in_start_time, $schoolTimezone);
            $checkInEnd = convert_timezone_to_utc($attendanceWindow->date . ' ' . $attendanceWindow->check_in_end_time, $schoolTimezone);
            $checkOutStart = convert_timezone_to_utc($attendanceWindow->date . ' ' . $attendanceWindow->check_out_start_time, $schoolTimezone);
            $checkOutEnd = convert_timezone_to_utc($attendanceWindow->date . ' ' . $attendanceWindow->check_out_end_time, $schoolTimezone);

            if ($checkTime->lt($checkInStart) || $checkTime->gt($checkOutEnd) || $checkTime->between($checkInEnd->copy()->addMinutes($checkInTypes->max('late_duration')), $checkOutStart)) {
                $this->logFailure($studentId,$checkTime, "Invalid check time: $checkTime for attendance window id : ".$attendanceWindow->id);
                continue;
            }

            if (!Student::find($studentId)) {
                $this->logFailure($studentId, $checkTime , 'Unrecognized student ID');
                continue;
            }

            $attendance = Attendance::withoutGlobalScope(SchoolScope::class)
                ->where('school_id', $schoolId)
                ->where("student_id", $studentId)
                ->where('attendance_window_id', $attendanceWindow->id)
                ->first();

            if (!$attendance) {
                if ($checkTime->gt($checkOutStart)) {
                    $this->logFailure($studentId,$checkTime, "Student has not checked in yet, check time: $checkTime");
                    continue;
                }
                try {
                    $attendance = Attendance::create([
                        'school_id' => $schoolId,
                        'student_id' => $studentId,
                        'attendance_window_id' => $attendanceWindow->id,
                        'check_in_status_id' => $absenceCheckInStatus->id
                    ]);
                } catch (Exception $e) {
                    $this->logFailure($studentId,$checkTime, 'Something went wrong: ' . $e->getMessage());
                    continue;
                }
            } else {
                // Prevent duplicate check-ins
                if ($attendance->check_in_time && $checkTime->between($checkInStart, $checkInEnd->copy()->addMinutes($checkInTypes->max('late_duration')))) {
                    $this->logFailure($studentId,$checkTime, 'Duplicate check-in attempt');
                    continue;
                } else if ($attendance->check_out_time && $checkTime->between($checkOutStart, $checkOutEnd)){
                    $this->logFailure($studentId,$checkTime, 'Duplicate check-out attempt');
                    continue;
                }
            }

            foreach ($checkInTypes as $cit) {
                if ($checkTime->between($checkInStart, $checkInEnd->copy()->addMinutes($cit->late_duration))) {
                    $isInCheckInTimeRange = true;
                    $attendance->update([
                        'check_in_status_id' => $cit->id,
                        'check_in_time' => convert_utc_to_timezone($checkTime, $schoolTimezone)
                    ]);
                    break;
                }
            }

            // If the student is not in check-in range, mark check-out time
            if (!$isInCheckInTimeRange) {
                $attendance->update([
                    'check_out_time' => convert_utc_to_timezone($checkTime, $schoolTimezone)
                ]);
            }
        }
    }

    private function logFailure($studentId,$date ,$message)
    {
        FailedStoreAttendanceJob::create([
            'student_id' => $studentId,
            'date' => $date,
            'message' => $message,
        ]);
    }
}
