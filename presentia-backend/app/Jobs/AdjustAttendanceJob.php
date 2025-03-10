<?php

namespace App\Jobs;

use App\Models\Attendance;
use App\Models\AttendanceWindow;
use App\Models\CheckInStatus;
use App\Models\CheckOutStatus;
use App\Models\FailedAdjustAttendanceJob;
use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class AdjustAttendanceJob implements ShouldQueue
{
    use Queueable;

    private $attendanceWindowIds;
    private $validatedUpdatedAttendanceWindowData;
    private $context;
    private $schoolId;
    private $checkInStatuses;
    private $maxLateDuration;
    private $absenceCheckInStatusId;
    private $checkOutStatuses;

    /**
     * Create a new job instance.
     */
    public function __construct($attendanceWindowIds, $context, $schoolId, $validatedUpdatedAttendanceWindowData = null)
    {
        $this->attendanceWindowIds = $attendanceWindowIds;
        $this->validatedUpdatedAttendanceWindowData = $validatedUpdatedAttendanceWindowData;
        $this->context = $context;
        $this->schoolId = $schoolId;
        /*
            0 = 'Adjust Attendance'
            1 = 'With Attendance Window Changes or Attendance Schedule Changes ;',
            2 = 'Check In Status Changes'
        */
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        config(['school.id' => $this->schoolId]);

        
        if(empty($this->attendanceWindowIds)){
            $this->logFailure($this->validatedUpdatedAttendanceWindowData, "Attendance Window Ids are empty");
            return;
        }

        $this->checkInStatuses = CheckInStatus::orderBy('late_duration')
            ->pluck('id', 'late_duration')
            ->toArray();
        $this->maxLateDuration = max(array_keys($this->checkInStatuses));

        $this->absenceCheckInStatusId = $checkInStatuses["-1"] ?? null;

        //get all check out statuses <<
        $this->checkOutStatuses = CheckOutStatus::pluck('id', 'late_duration')
            ->toArray();
        //>>

        if ($this->absenceCheckInStatusId !== null) {
            unset($checkInStatuses["-1"]);
        } else {
            $this->logFailure($this->validatedUpdatedAttendanceWindowData, "Absence Check In Status Id is null");
        }


        try {
            $attendanceWindows = AttendanceWindow::whereIn('id', $this->attendanceWindowIds)->get()->groupBy('id');
            $attendances = Attendance::whereIn('attendance_window_id', $this->attendanceWindowIds)->get()->groupBy('attendance_window_id');

            switch ($this->context) {
                case 0:
                    foreach ($attendanceWindows as $id => $attendanceWindow) {
                        $this->adjustAttendance($id, $attendanceWindow->first(), $attendances);
                    }
                    break;
        
                case 1:
                    if (empty($this->validatedUpdatedAttendanceWindowData)) {
                        $this->logFailure($this->validatedUpdatedAttendanceWindowData, "Updated Attendance Window Data is empty");
                        return;
                    }
                    foreach ($attendanceWindows as $id => $attendanceWindow) {
                        $attendanceWindow->update($this->validatedUpdatedAttendanceWindowData);
                        $this->adjustAttendance($id, $attendanceWindow->first(), $attendances);
                    }
                    break;
        
                case 2:
                    foreach ($attendanceWindows as $id => $attendanceWindow) {
                        $this->adjustAttendance($id, $attendanceWindow->first(), $attendances);
                    }
                    break;
        
                default:
                    $this->logFailure($this->validatedUpdatedAttendanceWindowData, "Invalid context");
                    return;
            }
        } catch (Exception $e) {
            $this->logFailure($this->validatedUpdatedAttendanceWindowData, $e->getMessage());
        }
    }

    private function adjustAttendance($attendanceWindowId, $attendanceWindow, $attendances)
    {
        $checkInStartTime = Carbon::parse($attendanceWindow["check_in_start_time"]);
        $checkInEndTime = Carbon::parse($attendanceWindow["check_in_end_time"]);
        $checkOutStartTime = Carbon::parse($attendanceWindow["check_out_start_time"]);
        $checkOutEndTime = Carbon::parse($attendanceWindow["check_out_end_time"]);

        foreach ($attendances[$attendanceWindowId] as $attendance) {
            try {
                $attendance = $attendance->first();
                $checkInTime = Carbon::parse($attendance["check_in_time"]);
                $checkOutTime = Carbon::parse($attendance["check_out_time"]);
                $isInCheckInTimeRange = false;

                if ($checkInTime->between($checkInStartTime, $checkInEndTime->copy()->addMinutes($this->maxLateDuration))) {
                    foreach ($this->checkInStatuses as $late_duration => $id) {
                        if ($checkInTime->between($checkInStartTime, $checkInEndTime->copy()->addMinutes($late_duration))) {
                            $isInCheckInTimeRange = true;
                            $attendance->update([
                                'check_in_status_id' => $id,
                            ]);
                            break;
                        }
                    }
                }

                if (!$isInCheckInTimeRange) {
                    $attendance->update([
                        'check_in_status_id' => $this->absenceCheckInStatusId,
                    ]);
                }

                $attendance->update([
                    'check_out_status_id' => $checkOutTime->between($checkOutStartTime, $checkOutEndTime)
                        ? $this->checkOutStatuses['0']
                        : $this->checkOutStatuses['-1'],
                ]);
            } catch (Exception $e) {
                $this->logFailure($this->validatedUpdatedAttendanceWindowData, $e->getMessage(), $attendance['student_id'], $attendanceWindowId);
            }
        }
    }

    private function logFailure($validatedUpdatedAttendanceWindowData, $message, $studentId = null, $attendanceWindowId = null)
    {
        FailedAdjustAttendanceJob::create([
            'student_id' => $studentId,
            'attendance_window_id' => $attendanceWindowId,
            'upcoming_attendance_window_data' => $validatedUpdatedAttendanceWindowData,
            'context' => $this->context == 0 ? 'Adjust Attendance' :
                ($this->context == 1 ? 'Attendance Window or Attendance Schedule Changes' : 'Check In Status Changes'),
            'message' => $message,
        ]);
    }
}
