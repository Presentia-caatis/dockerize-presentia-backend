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


    
    public function getById($id)
    {
        $day=Day::find($id);
        $data = $day->load('school', 'attendanceSchedule');
        return response()->json([
            'status' => 'success',
            'message' => 'Day retrieved successfully',
            'data' => $data
        ]);
    }

    
    public function update(Request $request, $id)
    {
        $day=Day::find($id);
        $validatedData = $request->validate([
            'attendance_schedule_id' => 'nullable|exists:attendance_schedules,id',
        ]);

        $day->update($validatedData);
        return response()->json([
            'status' => 'success',
            'message' => 'Day updated successfully',
            'data' => $day->load('school', 'attendanceSchedule')
        ]);
    }

}
