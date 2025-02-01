<?php

namespace App\Http\Controllers;

use App\Models\AttendanceSchedule;
use App\Models\Event;
use App\Models\School;
use Carbon\Carbon;
use Illuminate\Http\Request;

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

    public function show($id)
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
        $request->validate([
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
        $request->validate([
            'event_id' => 'nullable',
            'name' => 'required|string',
            'type' => 'required|in:event',
            'check_in_start_time' => 'required|date_format:Y-m-d H:i:s',
            'check_in_end_time' => 'required|date_format:Y-m-d H:i:s',
            'check_out_start_time' => 'required|date_format:Y-m-d H:i:s',
            'check_out_end_time' => 'required|date_format:Y-m-d H:i:s'
        ]);

        $data = $request->all();

        $school = School::findOrFail($request->route('school_id'));

        if (!$school->timezone) {
            return response()->json([
                'status' => 'error',
                'message' => 'School timezone is not set'
            ], 400);
        }

        $data['check_in_start_time'] = Carbon::parse($data['check_in_start_time'], $school->timezone)
            ->utc()
            ->format('Y-m-d\TH:i:s.u\Z');

        $data['check_in_end_time'] = Carbon::parse($data['check_in_end_time'], $school->timezone)
            ->utc()
            ->format('Y-m-d\TH:i:s.u\Z');

        $data['check_out_start_time'] = Carbon::parse($data['check_out_start_time'], $school->timezone)
            ->utc()
            ->format('Y-m-d\TH:i:s.u\Z');

        $data['check_out_end_time'] = Carbon::parse($data['check_out_end_time'], $school->timezone)
            ->utc()
            ->format('Y-m-d\TH:i:s.u\Z');

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



    public function update(Request $request, $school, $id)
    {
        $attendanceSchedule = AttendanceSchedule::find($id);
        $request->validate([
            'event_id' => 'nullable',
            'name' => 'required|string',
            'type' => 'required|in:event,default,holiday',
            'check_in_start_time' => 'required|date_format:Y-m-d H:i:s',
            'check_in_end_time' => 'required|date_format:Y-m-d H:i:s',
            'check_out_start_time' => 'required|date_format:Y-m-d H:i:s',
            'check_out_end_time' => 'required|date_format:Y-m-d H:i:s'
        ]);

        $school = School::findOrFail($school);
        $timezone = $school->timezone;

        $checkInStartTime = Carbon::createFromFormat('Y-m-d H:i:s', $request->check_in_start_time, $timezone)->utc();
        $checkInEndTime = Carbon::createFromFormat('Y-m-d H:i:s', $request->check_in_end_time, $timezone)->utc();
        $checkOutStartTime = Carbon::createFromFormat('Y-m-d H:i:s', $request->check_out_start_time, $timezone)->utc();
        $checkOutEndTime = Carbon::createFromFormat('Y-m-d H:i:s', $request->check_out_end_time, $timezone)->utc();

        $attendanceSchedule = AttendanceSchedule::findOrFail($id);
        $attendanceSchedule->update([
            'event_id' => $request->event_id,
            'name' => $request->name,
            'type' => $request->type,
            'check_in_start_time' => $checkInStartTime,
            'check_in_end_time' => $checkInEndTime,
            'check_out_start_time' => $checkOutStartTime,
            'check_out_end_time' => $checkOutEndTime,
        ]);

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
