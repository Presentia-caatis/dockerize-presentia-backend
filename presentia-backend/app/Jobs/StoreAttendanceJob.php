<?php

namespace App\Jobs;

use App\Models\Attendance;
use App\Models\AttendanceWindow;
use App\Models\CheckInStatus;
use App\Models\CheckOutStatus;
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
        // check if the given data is empty<<
        if (empty($this->jsonInput)) {
            $this->logFailure(null, now(), 'Data is empty'); 
            return;
        }
        //>>

        //Get the school id from given jsonInput <<
        $students = null;

        foreach ($this->jsonInput as $val) {
            $studentId = $val['id'];

            $student = Student::withoutGlobalScope(SchoolScope::class)->find($studentId);

            if ($student) {
                $students = $student->pluck('school_id', 'id')->toArray();
                break;
            }
            
            $this->logFailure($studentId, Carbon::parse($this->jsonInput[0]['date']), 'Unrecognize student Id for any school');
        }

        if(!$students){
            $this->logFailure(null, Carbon::parse($this->jsonInput[0]['date']), 'None of the data has valid student Id for any school');
            return;
        }

        $schoolId = reset($students);
        //>>

        
        $schoolTimezone = \App\Models\School::findOrFail($schoolId)->timezone; //get the school's timezone 

        //get all the dates from jsonInput and put it into array 
        $inputDates = array_unique(array_map(function ($item) use ($schoolTimezone) {
            return Carbon::parse($item['date'])->setTimezone($schoolTimezone)->format('Y-m-d');
        }, $this->jsonInput));
        //>>

        //get all attendance windows
        $attendanceWindows = AttendanceWindow::withoutGlobalScope(SchoolScope::class)
            ->where('school_id', $schoolId)
            ->whereIn('date', $inputDates)
            ->get()
            ->groupBy('date');
        //>>

        //get all check in statuses except the absence once <<
        $checkInStatuses = CheckInStatus::withoutGlobalScope(SchoolScope::class)
            ->where('school_id', $schoolId)
            ->where('is_active', true)
            ->where('late_duration', '!=', -1)
            ->orderBy('late_duration', 'asc')
            ->get();
        //>>

        //get all the absence check in status <<
        $absenceCheckInStatus = CheckInStatus::withoutGlobalScope(SchoolScope::class)
            ->where('school_id', $schoolId)
            ->where('late_duration', -1)
            ->first();
        //>>

        //get all check out statuses <<
        $checkOutStatuses = CheckOutStatus::withoutGlobalScope(SchoolScope::class)
            ->where('school_id', $schoolId)
            ->pluck('id', 'late_duration')
            ->toArray();
        //>>



        foreach ($this->jsonInput as $student) {
            $studentId = $student['id']; //get student id
            $checkTime = Carbon::parse($student['date']); //get the time where the student is triggering the adms
            $formattedDate = Carbon::parse($student['date'])->setTimezone($schoolTimezone)->format('Y-m-d'); // get the date only from student
            $attendanceWindowsPerDate = $attendanceWindows[$formattedDate] ?? null; //get all attendance window for desired date

            if($students[$studentId]){
                $this->logFailure(null, $checkTime, 'Unrecognized student ID for this school id:' . $schoolId);
                continue;
            }

            if (!$attendanceWindowsPerDate) {
                $this->logFailure($studentId, $checkTime, "No attendance window found for date $formattedDate");
                continue;
            }

            foreach($attendanceWindows as $attendanceWindow){
                $checkInStartTime = convert_timezone_to_utc($attendanceWindow->date . ' ' . $attendanceWindow->check_in_start_time, $schoolTimezone);
                $checkInEndTime = convert_timezone_to_utc($attendanceWindow->date . ' ' . $attendanceWindow->check_in_end_time, $schoolTimezone);
                $checkOutStartTime = convert_timezone_to_utc($attendanceWindow->date . ' ' . $attendanceWindow->check_out_start_time, $schoolTimezone);
                $checkOutEndTime = convert_timezone_to_utc($attendanceWindow->date . ' ' . $attendanceWindow->check_out_end_time, $schoolTimezone);
                
                $lateCutoffTime = $checkInEndTime->copy()->addMinutes($checkInStatuses->max('late_duration'));
                $isInAttendanceSession = false;
                
                if ($checkTime->lt($checkInStartTime) || 
                    $checkTime->gt($checkOutEndTime) || 
                        (   
                            $checkTime->gt($lateCutoffTime) &&
                            $checkTime->lt($checkOutStartTime)
                        )
                    ) {
                    continue;
                }

                $isInAttendanceSession = true;
                
                $attendance = Attendance::withoutGlobalScope(SchoolScope::class)
                    ->where('school_id', $schoolId)
                    ->where("student_id", $studentId)
                    ->where('attendance_window_id', $attendanceWindow->id)
                    ->first();

                if (!$attendance) {
                    try {
                        $attendance = Attendance::create([
                            'school_id' => $schoolId,
                            'student_id' => $studentId,
                            'attendance_window_id' => $attendanceWindow->id,
                            'check_in_status_id' => $absenceCheckInStatus->id,
                            'check_out_status_id' => $checkOutStatuses["-1"]
                        ]);

                    } catch (Exception $e) {
                        $this->logFailure($studentId, $checkTime, 'Something went wrong: ' . $e->getMessage(), $attendanceWindow->id);
                        continue;
                    }
                } else {
                    // Prevent duplicate check-ins
                    if (
                        $attendance->check_in_time &&
                        $attendance->check_in_status_id != $absenceCheckInStatus->id &&
                        $checkTime->between($checkInStartTime, $checkOutStartTime)
                    ) {
                        $this->logFailure($studentId, $checkTime, 'Duplicate check-in attempt', $attendanceWindow->id);
                        continue;
                    } else if (
                        $attendance->check_out_time &&
                        $attendance->check_out_status_id != $checkOutStatuses["-1"] &&
                        $checkTime->between($checkOutStartTime, $checkOutEndTime)
                    ) { // Prevent duplicate check-outs
                        $this->logFailure($studentId, $checkTime, 'Duplicate check-out attempt', $attendanceWindow->id);
                        continue;
                    }
                }

                if($checkTime->between($checkInStartTime, $lateCutoffTime)){
                    foreach ($checkInStatuses as $cit) {
                        if ($checkTime->between($checkInStartTime, $checkInEndTime->copy()->addMinutes($cit->late_duration))) {
                            $attendance->update([
                                'check_in_status_id' => $cit->id,
                                'check_in_time' => convert_utc_to_timezone($checkTime, $schoolTimezone)
                            ]);
                            break;
                        }
                    }
                } else {
                    $attendance->update([
                        'check_out_status_id' =>  $checkOutStatuses["0"],
                        'check_out_time' => convert_utc_to_timezone($checkTime, $schoolTimezone)
                    ]);
                    if(!$attendance->check_in_time) {
                        $attendance->update(['check_in_time' => convert_utc_to_timezone($checkTime, $schoolTimezone)]);
                        $this->logFailure($studentId, $checkTime, 'Student has not checked in yet', $attendanceWindow->id);
                    }
                }
                break;
            }

            if (!$isInAttendanceSession) {
                $this->logFailure(
                    $studentId, 
                    $checkTime, 
                    "No attendance session found for this check time.", 
                );
                continue;
            }
            
        }
    }

    private function logFailure($studentId = null, $date, $message, $attendanceWindowId = null)
    {
        FailedStoreAttendanceJob::create([
            'student_id' => $studentId,
            'date' => $date,
            'message' => $message,
            'attendance_window_id' => $attendanceWindowId
        ]);
    }
}
