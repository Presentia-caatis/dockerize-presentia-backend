<?php

namespace App\Http\Controllers;

use App\Models\Day;
use Illuminate\Http\Request;

class DayController extends Controller
{
    
    public function index()
    {
        $data = Day::with('school', 'attendanceSchedule')->get();
        return response()->json([
            'status' => 'success',
            'message' => 'Days retrieved successfully',
            'data' => $data
        ]);
    }


    
    public function show(Day $day)
    {
        $data = $day->load('school', 'attendanceSchedule');
        return response()->json([
            'status' => 'success',
            'message' => 'Day retrieved successfully',
            'data' => $data
        ]);
    }

    public function showAllBySchool(){
        $data = Day::with('school', 'attendanceSchedule')->where('school_id', auth()->user()->school_id)->get();
        return response()->json([
            'status' => 'success',
            'message' => 'Days retrieved successfully',
            'data' => $data
        ]);
    }

    
    public function update(Request $request, Day $day)
    {
        $request->validate([
            'attendance_schedule_id' => 'nullable|exists:attendance_schedules,id',
        ]);

        $day->update($request->all());
        return response()->json([
            'status' => 'success',
            'message' => 'Day updated successfully',
            'data' => $day->load('school', 'attendanceSchedule')
        ]);
    }

}
