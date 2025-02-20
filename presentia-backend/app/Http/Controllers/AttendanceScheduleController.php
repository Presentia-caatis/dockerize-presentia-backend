<?php

namespace App\Http\Controllers;

use App\Filterable;
use App\Models\AttendanceSchedule;
use App\Models\Event;
use App\Models\School;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use function App\Helpers\current_school;
use function App\Helpers\current_school_timezone;

class AttendanceScheduleController extends Controller
{
    use Filterable;

    public function index(Request $request)
    {
        $validatedData = $request->validate([
            'perPage' => 'sometimes|integer|min:1'
        ]);

        $perPage = $validatedData['perPage'] ?? 10;

        $query = $this->applyFilters(AttendanceSchedule::query(),  $request->input('filter', []), ['school']);

        $data = $query->paginate($perPage);

        $modifiedData = $data->getCollection()->map(function ($item) {
            if (!$item->event_id) {
                unset($item->event_id);
            }
            return $item;
        });
        
        $data->setCollection($modifiedData);

        return response()->json([
            'status' => 'success',
            'message' => 'Attendance schedule retrieved successfully',
            'data' => $data
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
            'event_id' => 'sometimes|exists:events,id',
            'name' => 'sometimes|string',
            'date' => [
                Rule::requiredIf($request->type === 'event'),
                'date_format:Y-m-d'
            ],
            'check_in_start_time' => 'sometimes|date_format:H:i:s',
            'check_in_end_time' => 'sometimes|date_format:H:i:s',
            'check_out_start_time' => 'sometimes|date_format:H:i:s',
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
