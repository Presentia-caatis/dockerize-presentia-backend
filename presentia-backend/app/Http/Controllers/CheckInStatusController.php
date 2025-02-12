<?php

namespace App\Http\Controllers;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;

use App\Models\CheckInStatus;

class CheckInStatusController extends Controller
{
    public function index()
    {

        $data = CheckInStatus::orderBy('late_duration')->get();
        return response()->json([
            'status' => 'success',
            'message' => 'Attendance late types retrieved successfully',
            'data' => $data
        ]);

    }

    
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'status_name' => 'required|string',
            'description' => 'required|string',
            'is_active' => 'required|boolean',
            'late_duration' => 'required|integer'
        ],201);

        $validatedData['school_id'] = config('school.id');

        $data = CheckInStatus::create($validatedData);
        return response()->json([
            'status' => 'success',
            'message' => 'Attendance late type created successfully',
            'data' => $data
        ]);

    }

    public function getById($id)
    {
        $checkInStatus = CheckInStatus::find($id);
        return response()->json([
            'status' => 'success',
            'message' => 'Attendance late type retrieved successfully',
            'data' => $checkInStatus
        ]);

    }

    public function update(Request $request, $id)
    {
        $checkInStatus = CheckInStatus::findOrFail($id);
        
        $validatedData = $request->validate([
            'status_name' => 'sometimes|string',
            'description' => 'sometimes|string',
            'is_active' => 'sometimes|boolean',
            'late_duration' => 'sometimes|integer',
            '*' => 'required_without_all:status_name,description,is_active,late_duration'
        ]);

        if(isset($validatedData['late_duration']) && ($checkInStatus->late_duration == 0 || $checkInStatus->late_duration == -1)){
            throw new AuthorizationException('You are not allowed to update late_duration column for this id');
        }

        $checkInStatus->update($validatedData);
        return response()->json([
            'status' => 'success',
            'message' => 'Attendance late type updated successfully',
            'data' => $checkInStatus
        ]);

    }

    public function destroy($id)
    {
        $checkInStatus = CheckInStatus::find($id);
        $checkInStatus->delete();
        return response()->json([
            'status' => 'success',
            'message' => 'Attendance late type deleted successfully'
        ]);
    }
}
