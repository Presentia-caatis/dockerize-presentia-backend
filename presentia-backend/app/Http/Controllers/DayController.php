<?php

namespace App\Http\Controllers;

use App\Models\Day;
use Illuminate\Http\Request;

class DayController extends Controller
{
    
    public function index(Request $request)
    {
        $validatedData = $request->validate([
            'perPage' => 'sometimes|integer|min:1' 
        ]);

        $perPage = $validatedData['perPage'] ?? 10;

        $data = Day::with('school', 'attendanceSchedule')->paginate($perPage);
        return response()->json([
            'status' => 'success',
            'message' => 'Days retrieved successfully',
            'data' => $data
        ]);
    }


    
    public function getById($id)
    {
        $day=Day::findOrFail($id);
        $data = $day->load('school', 'attendanceSchedule');
        return response()->json([
            'status' => 'success',
            'message' => 'Day retrieved successfully',
            'data' => $data
        ]);
    }

    
    public function update(Request $request, $id)
    {
        $day=Day::findOrFail($id);
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
