<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendanceWindow;
use App\Models\CheckInStatus;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardStatistic extends Controller
{
    function StaticStatistic(){
        
        $data = [
            'packet' => 0,
            'active_students' => Student::where('is_active', true)->count(),
            'inactive_students' => Student::where('is_active', false)->count(),
        ];

        return response()->json([
            'status' => 'success',
            'message' => 'Static statistic retrieved successfully',
            'data' => $data
        ]);
    }

    function DailyStatistic(Request $request){
        $request->validate([
            'date' => 'required|date',
        ]);

        $attendanceWindow = AttendanceWindow::whereDate('date', Carbon::parse($request->date)->format('Y-m-d'))
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
