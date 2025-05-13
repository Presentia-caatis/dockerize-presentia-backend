<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendanceWindow;
use App\Models\CheckInStatus;
use App\Models\CheckOutStatus;
use App\Models\School;
use App\Models\Scopes\SchoolScope;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use function App\Helpers\convert_utc_to_timezone;
use function App\Helpers\current_school_id;
use function App\Helpers\current_school_timezone;
use function App\Helpers\stringify_convert_utc_to_timezone;

class DashboardStatistic extends Controller
{
    public function StaticStatistic()
    {
        $school = School::findOrFail(current_school_id())->load('subscriptionPlan');
        $data = [
            'active_students' => Student::where('is_active', true)->count(),
            'inactive_students' => Student::where('is_active', false)->count(),
            'male_students' => Student::where('gender', 'male')->count(),
            'female_students' => Student::where('gender', 'female')->count(),
            'subscription_packet' => $school->subscriptionPlan,
        ];
        $data['subscription_packet']['end_duration'] = Carbon::parse($school->latest_subscription)->copy()->addMonths($school->subscriptionPlan->billing_cycle_month);
        $data['is_subscription_packet_active'] = convert_utc_to_timezone(Carbon::now(), $school->timezone)->lte($data['subscription_packet']['end_duration']);
        return response()->json([
            'status' => 'success',
            'message' => 'Static statistic retrieved successfully',
            'data' => $data
        ]);
    }

    public function DailyStatistic(Request $request): JsonResponse
    {
        $validated = $this->validateDailyStatisticRequest($request);
        $date = $validated['date'];
        $summarize = $validated['summarize'];

        $attendanceWindows = AttendanceWindow::whereDate('date', $date)->get();

        if ($attendanceWindows->isEmpty()) {
            return $this->emptyResponse();
        }

        $checkInStatuses = CheckInStatus::orderBy('late_duration')->pluck('status_name', 'id');
        $checkOutStatuses = CheckOutStatus::orderBy('late_duration')->pluck('status_name', 'id');
        $absentCheckIn = CheckInStatus::where('late_duration', -1)->first()?->status_name ?? null;
        $absentCheckOut = CheckOutStatus::where('late_duration', -1)->first()?->status_name ?? null;

        $finalData = [];
        $totalPresent = 0;
        $totalAbsent = 0;

        foreach ($attendanceWindows as $window) {
            $checkInData = $this->compileAttendanceStats($window->id, 'check_in_status_id', 'check_in_statuses', $checkInStatuses, $absentCheckIn);
            $checkOutData = $this->compileAttendanceStats($window->id, 'check_out_status_id', 'check_out_statuses', $checkOutStatuses, $absentCheckOut);

            $presentCheckIn = $checkInData['present'];
            $presentCheckOut = $checkOutData['present'];

            $entry = [
                'attendance_window_name' => $window->name,
                'attendance_window_type' => $window->type,
                'statistic' => $summarize
                    ? [
                        'check_in' => [
                            'present' => $presentCheckIn,
                            'absent' => $checkInData['raw'][$absentCheckIn] ?? 0,
                        ],
                        'check_out' => [
                            'present' => $presentCheckOut,
                            'absent' => $checkOutData['raw'][$absentCheckOut] ?? 0,
                        ]
                    ]
                    : [
                        'check_in' => array_merge(['Total Hadir' => $presentCheckIn], $checkInData['raw']),
                        'check_out' => array_merge(['Total Keluar' => $presentCheckOut], $checkOutData['raw']),
                    ]
            ];

            $finalData[] = $entry;
            $totalPresent += $presentCheckIn;
            $totalAbsent += $checkInData['raw'][$absentCheckIn] ?? 0;
        }

        if (count($attendanceWindows) > 1) {
            $finalData['summary'] = [
                'present' => $totalPresent,
                'absent' => $totalAbsent
            ];
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Daily statistics retrieved successfully',
            'data' => $finalData
        ]);
    }


    private function validateDailyStatisticRequest(Request $request): array
    {
        $validated = $request->validate([
            'date' => 'sometimes|date_format:Y-m-d',
            'summarize' => 'sometimes|boolean'
        ]);

        return [
            'date' => $validated['date'] ?? stringify_convert_utc_to_timezone(now(), current_school_timezone(), 'Y-m-d'),
            'summarize' => $validated['summarize'] ?? true
        ];
    }

    private function emptyResponse(): JsonResponse
    {
        $count = Student::where('is_active', true)->count();
        $data = ['present' => 0, 'absent' => $count];

        return response()->json([
            'status' => 'success',
            'message' => 'No attendance data available for the selected date.',
            'data' => $data
        ]);
    }

    /**
     * @param int $windowId
     * @param string $column
     * @param string $table
     * @param \Illuminate\Support\Collection $statuses
     * @param string | null $absentName 
     * @return array{present: int, raw: array}
     */
    private function compileAttendanceStats(int $windowId, string $column, string $table, $statuses, $absentName): array
    {
        $counts = Attendance::withoutGlobalScope(SchoolScope::class)
            ->where('attendances.attendance_window_id', $windowId)
            ->join($table, "attendances.$column", '=', "$table.id")
            ->where('attendances.school_id', config('school.id'))
            ->selectRaw("attendances.$column, COUNT(*) as count")
            ->groupBy("attendances.$column")
            ->pluck('count', "attendances.$column")
            ->toArray();

        $data = [];
        $present = 0;

        foreach ($statuses as $id => $statusName) {
            $data[$statusName] = $counts[$id] ?? 0;
            $present += $data[$statusName];
        }

        $present -= $data[$absentName];

        return ['present' => $present, 'raw' => $data];
    }

}
