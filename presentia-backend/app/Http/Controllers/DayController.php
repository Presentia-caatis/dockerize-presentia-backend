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

        $data = Day::with('attendanceSchedule')->paginate($perPage);
        return response()->json([
            'status' => 'success',
            'message' => 'Days retrieved successfully',
            'data' => $data
        ]);
    }


    
    public function getById($id)
    {
        $day=Day::findOrFail($id);
        $data = $day->load('attendanceSchedule');
        return response()->json([
            'status' => 'success',
            'message' => 'Day retrieved successfully',
            'data' => $data
        ]);
    }
}
