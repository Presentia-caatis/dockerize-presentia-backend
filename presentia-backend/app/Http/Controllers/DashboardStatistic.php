<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendanceWindow;
use App\Models\CheckInStatus;
use App\Models\School;
use App\Models\Scopes\SchoolScope;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;
use function App\Helpers\convert_utc_to_timezone;
use function App\Helpers\current_school_id;
use function App\Helpers\current_school_timezone;
use function App\Helpers\stringify_convert_utc_to_timezone;

class DashboardStatistic extends Controller
{
    public function StaticStatistic()
    {
        $school = School::find(current_school_id())->load('subscriptionPlan');
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

    public function DailyStatistic(Request $request)
    {
        $validatedData = $request->validate([
            'date' => 'sometimes|date|date_format:Y-m-d',
            'summarize' => 'sometimes|boolean'
        ]);

        $summarize = $validatedData['summarize'] ?? true;

        // Get the date or default to today in school's timezone
        $date = $validatedData['date'] ?? stringify_convert_utc_to_timezone(now(), current_school_timezone(), 'Y-m-d');

        //Get the attendance window id
        $attendanceWindowId = optional(AttendanceWindow::whereDate('date', $date)->first())->id;
        
        // Get all possible CheckInStatuses
        $checkInStatuses = CheckInStatus::orderBy('late_duration')->pluck('status_name', 'id');

        if (!$attendanceWindowId) {
            $studentCounter = Student::where('is_active', true)->count();
            return response()->json([
                'status' => 'success',
                'message' => 'No attendance data available for the selected date.',
                'data' => ($summarize ? [
                    'presence' => 0,
                    'absence' => $studentCounter,
                ] : (function () use ($checkInStatuses, $studentCounter) {
                    return array_merge([
                        $checkInStatuses->values()->first() => $studentCounter
                    ], $checkInStatuses->skip(1)->mapWithKeys(function ($statusName) {return [$statusName => 0];})->toArray()
                    );
                })())
            ]);
        }

        // Get attendance counts grouped by check_in_status_id
        $attendanceCounts = Attendance::withoutGlobalScope(SchoolScope::class)
            ->where('attendances.attendance_window_id', $attendanceWindowId)
            ->join('check_in_statuses', 'attendances.check_in_status_id', '=', 'check_in_statuses.id')
            ->where('check_in_statuses.late_duration', '!=', -1)
            ->where('attendances.school_id', config('school.id'))
            ->selectRaw('attendances.check_in_status_id, COUNT(*) as count')
            ->groupBy('attendances.check_in_status_id')
            ->pluck('count', 'attendances.check_in_status_id')
            ->toArray();

        $data = [];
        $presenceCounter = 0;

        //map the status name with the counter
        foreach ($checkInStatuses as $id => $statusName) {
            $data[$statusName] = $attendanceCounts[$id] ?? 0;
            $presenceCounter += $data[$statusName];
        }

        $absenceStatusName = CheckInStatus::where('late_duration', -1)->first()->status_name;

        // Get the total active students
        $totalActiveStudents = Student::where('is_active', true)->count();
        $data[$absenceStatusName] = max(0, $totalActiveStudents - $presenceCounter);

        if ($summarize) {
            return response()->json([
                'status' => 'success',
                'message' => 'Daily statistic retrieved successfully',
                'data' => [
                    'presence' => $presenceCounter,
                    'absence' => $data[$absenceStatusName]
                ]
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Daily statistic retrieved successfully',
            'data' => $data
        ]);
    }
}
