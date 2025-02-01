<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\CheckInStatus;

class CheckInStatusController extends Controller
{
    public function index()
    {

        $data = CheckInStatus::all();
        return response()->json([
            'status' => 'success',
            'message' => 'Attendance late types retrieved successfully',
            'data' => $data
        ]);

    }

    
    public function store(Request $request)
    {
        $request->validate([
            'type_name' => 'required|string',
            'description' => 'required|string',
            'is_active' => 'required|boolean',
            'school_id' => 'required|exists:schools,id',
        ],201);


        $data = CheckInStatus::create($request->all());
        return response()->json([
            'status' => 'success',
            'message' => 'Attendance late type created successfully',
            'data' => $data
        ]);

    }

    public function show($id)
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
        $checkInStatus = CheckInStatus::find($id);
        $request->validate([
            'type_name' => 'required|string',
            'description' => 'required|string',
            'is_active' => 'required|boolean',
            'school_id' => 'required|exists:schools,id',
        ]);

        $checkInStatus->update($request->all());
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
