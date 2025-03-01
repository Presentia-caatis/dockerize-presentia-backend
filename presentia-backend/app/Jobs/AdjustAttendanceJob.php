<?php

namespace App\Jobs;

use App\Models\Attendance;
use App\Models\AttendanceWindow;
use App\Models\CheckInStatus;
use App\Models\Scopes\SchoolScope;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Request;

class AdjustAttendanceJob implements ShouldQueue
{
    use Queueable;

    private $attendanceWindowIds;
    private Request $request;

    private $context;
    private $attendances;

    private $schoolId;

    /**
     * Create a new job instance.
     */
    public function __construct($attendanceWindowIds, Request $request, $context, $schoolId)
    {
        $this->attendanceWindowIds = $attendanceWindowIds;
        $this->request = $request;
        $this->context = $context;
        /*
            0 = 'Adjust Attendance'
            1 = 'WitAttendance Window Changes or Attendance Schedule Changes ;',
            2 = 'Check In Status Changes'
        */
        $this->attendances = Attendance::whereIn('attendance_window_id', $this->attendanceWindowIds)->get()->keyBy('attendance_window_id');
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

            }
        } else if ($this->context == 2) {
            foreach ($attendanceWindows as $id => $attendanceWindow) {

            }
        }

    }

    private function adjustAttendance($attendanceWindowId, $attendanceWindow, $checkInStatuses = [])
    {
        $checkInStatuses = $checkInStatuses ?? CheckInStatus::withoutGlobalScope(SchoolScope::class)
            ->where('school_id', $this->schoolId)
            ->where('late_duration', '!=', '-1')
            ->orderBy('late_duration')
            ->pluck('id', 'late_duration')
            ->toArray();
        $maxLateDuration = max(array_keys($checkInStatuses));

        $absenceCheckInStatusId = $checkInStatuses["-1"] ?? null;

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

            //Check Out Status
            if ($checkInTime->between($checkOutStartTime, $checkOutEndTime)) {
                $attendance->update([
                    'check_in_status_id' => $id,
                ]);
            }
        }
    }
}
