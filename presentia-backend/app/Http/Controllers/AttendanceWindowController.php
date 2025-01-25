<?php

namespace App\Http\Controllers;

use App\Models\AttendanceSchedule;
use App\Models\AttendanceWindow;
use App\Models\Day;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AttendanceWindowController extends Controller
{

    public function index()
    {
        $data = AttendanceWindow::all();
        return response()->json([
            'status' => 'success',
            'message' => 'Attendance windows retrieved successfully',
            'data' => $data
        ]);
    }

    public function generateWindow(Request $request)
    {
        $request->validate([
            'date'  => 'required|date_format:Y-m-d',
        ]);

        $day = strtolower(Carbon::parse($request->date)->format('l'));
        

        $dayData = Day::where('name', $day)
        ->first();

        $dataSchedule = $dayData->attendanceSchedule;

        $date = Carbon::parse($request->date);

        $attendanceWindow = AttendanceWindow::create([
            'day_id' => $dayData->id,
            'name' => $dataSchedule->name . ' ' . Carbon::parse($request->date)->format('d-m-Y'),
            'school_id' => $dayData->school_id,
            'total_present' => 0,
            'total_absent' => 0,
            'date' => $request->date,
            'type' => $dataSchedule->type,
            'check_in_start_time' => Carbon::parse($date->toDateString() . ' ' . Carbon::parse($dataSchedule->check_in_start_time)->format('H:i:s')),
            'check_in_end_time' => Carbon::parse($date->toDateString() . ' ' . Carbon::parse($dataSchedule->check_in_end_time)->format('H:i:s')),
            'check_out_start_time' => Carbon::parse($date->toDateString() . ' ' . Carbon::parse($dataSchedule->check_out_start_time)->format('H:i:s')),
            'check_out_end_time' => Carbon::parse($date->toDateString() . ' ' . Carbon::parse($dataSchedule->check_out_end_time)->format('H:i:s'))
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Attendance window generated successfully',
            'data' => $attendanceWindow
        ]);
    }


    public function show(AttendanceWindow $attendanceWindow)
    {
        //
    }

    public function update(Request $request, AttendanceWindow $attendanceWindow)
    {
        //
    }

    public function destroy(AttendanceWindow $attendanceWindow)
    {
        //
    }
}
