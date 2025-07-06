<?php

namespace App\Jobs;

use App\Models\Attendance;
use App\Models\AttendanceWindow;
use App\Models\CheckInStatus;
use App\Models\CheckOutStatus;
use App\Models\FailedStoreAttendanceJob;
use App\Models\Scopes\SchoolScope;
use App\Models\Student;
use App\Services\BelongsToSchoolService;
use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use function App\Helpers\convert_timezone_to_utc;
use function App\Helpers\convert_utc_to_timezone;
use function App\Helpers\current_school_id;
use function App\Helpers\current_school_timezone;

class StoreAttendanceJob implements ShouldQueue
{
    use Queueable;

    protected $jsonInput;
    public $response;

    /**
     * Create a new job instance.
     */
    public function __construct(array $jsonInput)
    {
        $this->jsonInput = $jsonInput;
        $this->response = [];
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        config(['school.id' => Student::withoutGlobalScope(SchoolScope::class)->find($this->jsonInput[0]['id'])?->school_id]);
        $schoolId = current_school_id();
        $schoolTimezone = current_school_timezone(); //get the school's timezone 

        // (new BelongsToSchoolService($schoolId))->apply();

        $inputDates = [];

        foreach ($this->jsonInput as $student) {
            $inputDates[Carbon::parse($student['date'])->setTimezone($schoolTimezone)->format('Y-m-d')] = true;
        }

        $inputDates = array_keys($inputDates);


        //get all attendance windows
        $attendanceWindows = AttendanceWindow::whereIn('date', $inputDates)
            ->get()
            ->groupBy('date');
        //>>

        //get all check in statuses except the absence once <<
        $checkInStatuses = CheckInStatus::where('is_active', true)
            ->where('late_duration', '!=', -1)
            ->orderBy('late_duration', 'asc')
            ->get();
        //>>

        //get all the absence check in status <<
        $absenceCheckInStatus = CheckInStatus::where('late_duration', -1)
            ->first();
        //>>

        //get all check out statuses <<
        $checkOutStatuses = CheckOutStatus::pluck('id', 'late_duration')
            ->toArray();
        //>>

        $isInAttendanceSession = false;

        foreach ($this->jsonInput as $student) {
            $studentId = $student['id']; //get student id
            $checkTime = Carbon::parse($student['date']); //get the time where the student is triggering the adms
            $formattedDate = Carbon::parse($student['date'])->setTimezone($schoolTimezone)->format('Y-m-d'); // get the date only from student
            $attendanceWindowsPerDate = $attendanceWindows[$formattedDate] ?? null; //get all attendance window for desired date

            if (!$attendanceWindowsPerDate) {
                $this->setResponse("failed", $studentId, "Tidak ada jadwal presensi untuk hari ini");
                $this->logFailure($studentId, $checkTime, "No attendance window found for date $formattedDate");
                continue;
            }

            foreach ($attendanceWindowsPerDate as $attendanceWindow) {
                $checkInStartTime = convert_timezone_to_utc($attendanceWindow->date . ' ' . $attendanceWindow->check_in_start_time, $schoolTimezone);
                $checkInEndTime = convert_timezone_to_utc($attendanceWindow->date . ' ' . $attendanceWindow->check_in_end_time, $schoolTimezone);
                $checkOutStartTime = convert_timezone_to_utc($attendanceWindow->date . ' ' . $attendanceWindow->check_out_start_time, $schoolTimezone);
                $checkOutEndTime = convert_timezone_to_utc($attendanceWindow->date . ' ' . $attendanceWindow->check_out_end_time, $schoolTimezone);

                $lateCutoffTime = $checkInEndTime->copy()->addMinutes($checkInStatuses->max('late_duration'));
                $isInAttendanceSession = false;

                if (
                    $checkTime->lt($checkInStartTime) ||
                    $checkTime->gt($checkOutEndTime) ||
                    (
                        $checkTime->gt($lateCutoffTime) &&
                        $checkTime->lt($checkOutStartTime)
                    )
                ) {
                    continue;
                }

                $isInAttendanceSession = true;

                $attendance = Attendance::where("student_id", $studentId)
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
                        $this->setResponse("failed", $studentId, "Gagal Input Presensi", $e->getMessage());
                        $this->logFailure($studentId, $checkTime, 'Something went wrong: ' . $e->getMessage(), $attendanceWindow->id);
                        continue;
                    }
                } else {
                    // Prevent duplicate check-ins
                    if (
                        $attendance->check_in_time &&
                        $attendance->check_in_status_id != $absenceCheckInStatus->id &&
                        $checkTime->between($checkInStartTime, $checkInEndTime)
                    ) {
                        $this->setResponse("failed", $studentId, "Siswa sudah presensi masuk");
                        $this->logFailure($studentId, $checkTime, 'Duplicate check-in attempt', $attendanceWindow->id);
                        continue;
                    } else if (
                        $attendance->check_out_time &&
                        $attendance->check_out_status_id != $checkOutStatuses["-1"] &&
                        $checkTime->between($checkOutStartTime, $checkOutEndTime)
                    ) { // Prevent duplicate check-outs
                        $this->setResponse("failed", $studentId, "Siswa sudah presensi keluar");
                        $this->logFailure($studentId, $checkTime, 'Duplicate check-out attempt', $attendanceWindow->id);
                        continue;
                    }
                }

                if ($checkTime->between($checkInStartTime, $lateCutoffTime)) {
                    if ($attendanceWindow->type == 'event' && $checkTime->lte($attendanceWindow->check_in_end_time)) {
                        $attendance->update([
                            'check_in_status_id' => $absenceCheckInStatus->id,
                            'check_in_time' => convert_utc_to_timezone($checkTime, $schoolTimezone)
                        ]);
                    } else {
                        foreach ($checkInStatuses as $cit) {
                            if ($checkTime->between($checkInStartTime, $checkInEndTime->copy()->addMinutes($cit->late_duration))) {
                                $attendance->update([
                                    'check_in_status_id' => $cit->id,
                                    'check_in_time' => convert_utc_to_timezone($checkTime, $schoolTimezone)
                                ]);
                                break;
                            }
                        }
                    }
                } else {
                    $attendance->update([
                        'check_out_status_id' => $checkOutStatuses["0"],
                        'check_out_time' => convert_utc_to_timezone($checkTime, $schoolTimezone)
                    ]);
                    if (!$attendance->check_in_time) {
                        $this->setResponse("warning" ,$studentId, "Siswa belum presensi masuk");
                        $this->logFailure($studentId, $checkTime, 'Student has not checked in yet', $attendanceWindow->id);
                    }
                }
                $this->setResponse("success" ,$studentId, "Presensi berhasil");
                break;
            }

            if (!$isInAttendanceSession) {
                $this->setResponse("failed", $studentId, "Waktu presensi diluar jangka waktu yang ditentukan");
                $this->logFailure(
                    $studentId,
                    $checkTime,
                    "No attendance session found for this check time.",
                );
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

    private function setResponse($status ,$studentId, $message, $error = ""){
        $this->response[$studentId][] = [
            'status' => $status,
            'message' => $message,
            'error' => $error
        ];
    }
}
