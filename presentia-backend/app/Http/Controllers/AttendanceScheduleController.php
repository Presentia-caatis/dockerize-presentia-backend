<?php

namespace App\Http\Controllers;

use App\Filterable;
use App\Models\AttendanceSchedule;
use App\Models\Event;
use App\Models\School;
use App\Services\AttendanceWindowPreLoaderService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
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

        $query = $this->applyFilters(AttendanceSchedule::query(), $request->input('filter', []), ['school_id']);

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
        $attendanceSchedule = AttendanceSchedule::findOrFail($id);
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
            'name' => 'required|string',
            'school_id' => 'required|exists:schools,id',
            'is_active' => 'boolean',
            'is_scheduler_active' => 'boolean',
            'occurrences' => 'nullable|integer', // -1 for infinite, 0 for end_date, n for occurrences
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'nullable|date_format:Y-m-d|after_or_equal:start_date',
            'recurring_frequency' => 'required|in:daily,daily_exclude_holiday,weekly,monthly,yearly,none',
            'days_of_month' => 'nullable|array',
            'days_of_month.*' => 'integer', // Ensuring each item is an integer (supports negative values)
            'days_of_week' => 'nullable|array',
            'days_of_week.*' => 'string|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'interval' => 'required|integer|min:1', // Must be at least 1
            'weeks_of_month' => 'nullable|array',
            'weeks_of_month.*' => 'integer|min:1|max:5', // Supports 1st to 5th week of the month
    
            'type' => 'required|in:event,event_holiday',
            'date' => 'required|date_format:Y-m-d',
            'check_in_start_time' => 'required|date_format:H:i:s',
            'check_in_end_time' => 'required|date_format:H:i:s|after:check_in_start_time',
            'check_out_start_time' => 'required|date_format:H:i:s|after:check_in_end_time',
            'check_out_end_time' => 'required|date_format:H:i:s|after:check_out_start_time',
        ]);
    
        if (!current_school_timezone()) {
            return response()->json([
                'status' => 'error',
                'message' => 'School timezone is not set'
            ], 400);
        }
        DB::beginTransaction();

        try {
            $event = Event::create([
                'name' => $validatedData['name'],
                'school_id' => $validatedData['school_id'],
                'is_active' => $validatedData['is_active'] ?? true,
                'is_scheduler_active' => $validatedData['is_scheduler_active'] ?? true,
                'start_date' => $validatedData['start_date'],
                'end_date' => $validatedData['end_date'],
                'occurrences' => $validatedData['occurrences'] ?? 1,
                'recurring_frequency' => $validatedData['recurring_frequency'],
                'days_of_month' => json_encode($validatedData['days_of_month'] ?? []),
                'days_of_week' => json_encode($validatedData['days_of_week'] ?? []),
                'interval' => $validatedData['interval'],
                'weeks_of_month' => json_encode($validatedData['weeks_of_month'] ?? [])
            ]);
            $validatedData['event_id'] = $event->id;
    
            $attendanceScheduleData = Arr::only($validatedData, [
                'event_id',
                'name',
                'type',
                'date',
                'check_in_start_time',
                'check_in_end_time',
                'check_out_start_time',
                'check_out_end_time'
            ]);
        
            $attendanceSchedule = AttendanceSchedule::create($attendanceScheduleData);
        
            (new AttendanceWindowPreLoaderService($event, $attendanceSchedule))->apply();
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    
        return response()->json([
            'status' => 'success',
            'message' => 'Attendance schedule created successfully',
            'data' => $attendanceSchedule
        ], 201);

    }



    public function update(Request $request, $id)
    {
        $attendanceSchedule = AttendanceSchedule::findOrFail($id);
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
        $attendanceSchedule = AttendanceSchedule::findOrFail($id);
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
