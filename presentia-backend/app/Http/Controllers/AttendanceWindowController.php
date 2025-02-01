<?php

namespace App\Http\Controllers;

use App\Models\AttendanceSchedule;
use App\Models\AttendanceWindow;
use App\Models\Day;
use Carbon\Carbon;
use Illuminate\Http\Request;
use function App\Helpers\current_school_timezone;

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
    public function getAllInUtcFormat(){
        $currentSchoolTimezone = current_school_timezone();
        $data = AttendanceWindow::all()->map(function ($record) use ($currentSchoolTimezone) {
            return [
                'id' => $record->id,
                'day_id' => $record->day_id,
                'name' => $record->name,
                'school_id' => $record->school_id,
                'total_present' => $record->total_present,
                'total_absent' => $record->total_absent,
                'date' => Carbon::parse($record->date, $currentSchoolTimezone)->utc()->toDateString(),
                'type' => $record->type,
                'check_in_start_time' => Carbon::parse($record->check_in_start_time, $currentSchoolTimezone)->utc()->toDateTimeString(),
                'check_in_end_time' => Carbon::parse($record->check_in_end_time, $currentSchoolTimezone)->utc()->toDateTimeString(),
                'check_out_start_time' => Carbon::parse($record->check_out_start_time, $currentSchoolTimezone)->utc()->toDateTimeString(),
                'check_out_end_time' => Carbon::parse($record->check_out_end_time, $currentSchoolTimezone)->utc()->toDateTimeString(),
            ];
        });
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


    public function getById($id)
    {
        $attendanceWindow=AttendanceWindow::find($id);

        return response()->json([
            'status' => 'success',
            'message' => 'Attendance window retrieved successfully',
            'data' => $attendanceWindow
        ]);
    }

    public function update(Request $request, $id)
    {
        $attendanceWindow=AttendanceWindow::find($id);

        $request->validate([
            'name' => 'sometimes|string',
            'date' => 'sometimes|date_format:Y-m-d',
            'total_present' => 'sometimes|integer',
            'total_absent' => 'sometimes|integer',
            'check_in_start_time' => 'sometimes|date_format:H:i:s',
            'check_in_end_time' => 'sometimes|date_format:H:i:s',
            'check_out_start_time' => 'sometimes|date_format:H:i:s',
            'check_out_end_time' => 'sometimes|date_format:H:i:s'
        ]);

        $attendanceWindow->update($request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Attendance window updated successfully',
            'data' => $attendanceWindow
        ]);
    }

    public function destroy($id)
    {
        $attendanceWindow=AttendanceWindow::find($id);
        $attendanceWindow->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Attendance window deleted successfully'
        ]);
    }
}
