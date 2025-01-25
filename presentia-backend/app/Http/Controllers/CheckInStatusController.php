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

    public function show(CheckInStatus $CheckInStatus)
    {

        return response()->json([
            'status' => 'success',
            'message' => 'Attendance late type retrieved successfully',
            'data' => $CheckInStatus
        ]);

    }

    public function update(Request $request, CheckInStatus $CheckInStatus)
    {
        $request->validate([
            'type_name' => 'required|string',
            'description' => 'required|string',
            'is_active' => 'required|boolean',
            'school_id' => 'required|exists:schools,id',
        ]);

        $CheckInStatus->update($request->all());
        return response()->json([
            'status' => 'success',
            'message' => 'Attendance late type updated successfully',
            'data' => $CheckInStatus
        ]);

    }

    public function destroy(CheckInStatus $CheckInStatus)
    {

        $CheckInStatus->delete();
        return response()->json([
            'status' => 'success',
            'message' => 'Attendance late type deleted successfully'
        ]);
    }
}
