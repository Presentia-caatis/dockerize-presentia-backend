<?php

namespace App\Jobs;

use App\Models\Attendance;
use App\Models\AttendanceWindow;
use App\Models\CheckInStatus;
use App\Models\CheckOutStatus;
use App\Models\SchoolDataModel;
use App\Models\Scopes\SchoolScope;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Request;

class AdjustAttendanceJob implements ShouldQueue
{
    use Queueable;

    private $attendanceWindowIds;
    private $validatedUpdatedAttendanceWindowData;

    private $context;
    private $attendances;

    private $schoolId;

    /**
     * Create a new job instance.
     */
    public function __construct($attendanceWindowIds, $validatedUpdatedAttendanceWindowData, $context, $schoolId)
    {
        $this->attendanceWindowIds = $attendanceWindowIds;
        $this->validatedUpdatedAttendanceWindowData = $validatedUpdatedAttendanceWindowData;
        $this->context = $context;
        /*
            0 = 'Adjust Attendance'
            1 = 'With Attendance Window Changes or Attendance Schedule Changes ;',
            2 = 'Check In Status Changes'
        */
        $this->attendances = Attendance::whereIn('attendance_window_id', $this->attendanceWindowIds)->get()->groupBy('attendance_window_id');
        $this->schoolId = $schoolId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $attendanceWindows = AttendanceWindow::whereIn('id', $this->attendanceWindowIds)->get()->keyBy('id');

        if ($this->context == 0) {
            foreach ($attendanceWindows as $id => $attendanceWindow) {
                $this->adjustAttendance($id, $attendanceWindow);
            }
        } else if ($this->context == 1) {
            foreach ($attendanceWindows as $id => $attendanceWindow) {
                $attendanceWindow->update($this->validatedUpdatedAttendanceWindowData);
                $this->adjustAttendance($id, $attendanceWindow);
            }
        } else if ($this->context == 2) {
            $checkInStatuses = CheckInStatus::all();
            foreach ($attendanceWindows as $id => $attendanceWindow) {
                $this->adjustAttendance($id, $attendanceWindow, $checkInStatuses);
            }
        }

    }

    private function adjustAttendance($attendanceWindowId, $attendanceWindow, $checkInStatuses = [])
    {
        $checkInStatuses = $checkInStatuses ?? CheckInStatus::where('late_duration', '!=', '-1')
            ->orderBy('late_duration')
            ->pluck('id', 'late_duration')
            ->toArray();
        $maxLateDuration = max(array_keys($checkInStatuses));

        $absenceCheckInStatusId = $checkInStatuses["-1"] ?? null;

        //get all check out statuses <<
        $checkOutStatuses = CheckOutStatus::withoutGlobalScope(SchoolScope::class)
            ->where('school_id', $this->schoolId)
            ->pluck('id', 'late_duration')
            ->toArray();
        //>>

        if ($absenceCheckInStatusId !== null) {
            unset($checkInStatuses["-1"]);
        } else {
            throw new \Exception("Check-in status with late_duration = -1 is missing!");
        }

        $checkInStartTime = Carbon::parse($attendanceWindow["check_in_start_time"]);
        $checkInEndTime = Carbon::parse($attendanceWindow["check_in_end_time"]);
        $checkOutStartTime = Carbon::parse($attendanceWindow["check_out_start_time"]);
        $checkOutEndTime = Carbon::parse($attendanceWindow["check_out_end_time"]);

        foreach ($this->attendances[$attendanceWindowId] as $attendance) {
            $checkInTime = Carbon::parse($attendance["check_in_time"]);
            $checkOutTime = Carbon::parse($attendance["check_out_time"]);
            $isInCheckInTimeRange = false;

            if ($checkInTime->between($checkInStartTime, $checkInEndTime->copy()->addMinutes($maxLateDuration))) {
                foreach ($checkInStatuses as $late_duration => $id) {
                    if ($checkInTime->between($checkInStartTime, $checkInEndTime->copy()->addMinutes($late_duration))) {
                        $isInCheckInTimeRange = true;
                        $attendance->update([
                            'check_in_status_id' => $id,
                        ]);
                    }
                }
            }

            if(!$isInCheckInTimeRange){
                $attendance->update([
                    'check_in_status_id' => $absenceCheckInStatusId,
                ]);
            }

            $attendance->update([
                'check_out_status_id' => $checkOutTime->between($checkOutStartTime, $checkOutEndTime) 
                    ? $checkOutStatuses['0'] 
                        : $checkOutStatuses['-1'],
            ]);
            
        }
    }
}
