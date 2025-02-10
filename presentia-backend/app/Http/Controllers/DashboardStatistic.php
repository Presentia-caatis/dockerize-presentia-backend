<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendanceWindow;
use App\Models\CheckInStatus;
use App\Models\School;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;
use function App\Helpers\convert_utc_to_timezone;
use function App\Helpers\current_school_id;

class DashboardStatistic extends Controller
{
    public function StaticStatistic(){
        $school =  School::find(current_school_id())->load('subscriptionPlan');
        $data = [
            'active_students' => Student::where('is_active', true)->count(),
            'inactive_students' => Student::where('is_active', false)->count(),
            'male_students' =>Student::where('gender', 'male')->count(),
            'female_student' => Student::where('gender', 'female')->count(),
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

    public function DailyStatistic(Request $request){
        $validatedData = $request->validate([
            'date' => 'required|date',
        ]);

        $attendanceWindow = AttendanceWindow::whereDate('date', Carbon::parse($validatedData['date'])->format('Y-m-d'))
            ->first();
        
        $data =[
            CheckInStatus::where('late_duration', 0)->first()->type_name => $attendanceWindow->total_present,
            CheckInStatus::where('late_duration', -1)->first()->type_name => $attendanceWindow->total_absent,
        ];

        foreach(CheckInStatus::where('late_duration', '!= ',0)->where('late_duration', '!= ',-1)->get() as $checkInStatus){
            $data[$checkInStatus->type_name] = Attendance::where('attendance_window_id', $attendanceWindow->id)
                                        ->where("check_in_status_id", $checkInStatus->id)->count();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Daily statistic retrieved successfully',
            'data' => $data
        ]);
    }
}
