<?php

namespace App\Http\Controllers;

use App\Models\AttendanceSchedule;
use App\Models\Event;
use App\Models\School;
use Carbon\Carbon;
use Illuminate\Http\Request;
use function App\Helpers\current_school;
use function App\Helpers\current_school_timezone;

class AttendanceScheduleController extends Controller
{

    public function index()
    {
        $nullEventSchedules = AttendanceSchedule::whereNull('event_id')->get()->map(function ($item) {
            unset($item->event_id);
            return $item;
        });

        $existingEventSchedules = AttendanceSchedule::whereNotNull('event_id')->get();

        $mergedSchedules = $nullEventSchedules->merge($existingEventSchedules);

        return response()->json([
            'status' => 'success',
            'message' => 'Attendance schedule retrieved successfully',
            'data' => $mergedSchedules
        ]);
    }

    public function getById($id)
    {
        $attendanceSchedule = AttendanceSchedule::find($id);
        return response()->json([
            'status' => 'success',
            'message' => 'Attendance windows retrieved successfully',
            'data' => $attendanceSchedule
        ]);
    }

    public function showByType(Request $request)
    {
        $validatedData = $request->validate([
            'type' => 'required|in:event,default,holiday',
        ]);

        $data = AttendanceSchedule::where('type', $request->type)->get();
        return response()->json([
            'status' => 'success',
            'message' => 'Attendance windows retrieved successfully',
            'data' => $data
        ]);
    }


    public function storeEvent(Request $request)
    {
        $validatedData = $request->validate([
            'event_id' => 'nullable',
            'name' => 'required|string',
            'type' => 'required|in:event',
            'date' => 'required|date_format: Y-m-d',
            'check_in_start_time' => 'required|date_format:H:i:s',
            'check_in_end_time' => 'required|date_format:H:i:s|after:check_in_start_time',
            'check_out_start_time' => 'required|date_format:H:i:s|after:check_in_end_time',
            'check_out_end_time' => 'required|date_format:H:i:s|after:check_out_start_time',
        ]);

        $data = $validatedData;

        if (!current_school_timezone()) {
            return response()->json([
                'status' => 'error',
                'message' => 'School timezone is not set'
            ], 400);
        }

        if (!$data['event_id']) {
            $event = Event::create([
                'start_date' => Carbon::parse($data['check_in_start_time'])->format('Y-m-d'),
                'end_date' => Carbon::parse($data['check_out_end_time'])->format('Y-m-d'),
            ]);

            $data['event_id'] = $event->id;
        }

        $attendanceSchedule = AttendanceSchedule::create($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Attendance schedule created successfully',
            'data' => $attendanceSchedule
        ], 201);
    }



    public function update(Request $request, $id)
    {
        $attendanceSchedule = AttendanceSchedule::find($id);
        $validatedData = $request->validate([
            'event_id' => 'nullable',
            'name' => 'required|string',
            'date' => 'required|date_format: Y-m-d',
            'check_in_start_time' => 'required|date_format: H:i:s',
            'check_in_end_time' => 'required|date_format:H:i:s',
            'check_out_start_time' => 'required|date_format:H:i:s',
            'check_out_end_time' => 'required|date_format:H:i:s'
        ]);

        $attendanceSchedule = AttendanceSchedule::findOrFail($id);
        $attendanceSchedule->update($validatedData);

        return response()->json([
            'status' => 'success',
            'message' => 'Attendance schedule updated successfully',
            'data' => $attendanceSchedule
        ]);
    }

    public function destroy($id)
    {
        $attendanceSchedule = AttendanceSchedule::find($id);
        if ($attendanceSchedule->type === 'holiday' || $attendanceSchedule->type === 'default') {
            abort(403, 'Cannot delete attendance schedule of type "holiday" or "default".');
        }

        $attendanceSchedule->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Attendance schedule deleted successfully'
        ]);
    }
}
