<?php

namespace App\Http\Controllers;

use App\Models\AttendanceSchedule;
use App\Models\AttendanceWindow;
use App\Models\Event;
use App\Services\AttendanceWindowLoaderService;
use DB;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use function App\Helpers\current_school_id;
use function App\Helpers\current_school_timezone;

class EventController extends Controller
{
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string',
            'is_active' => 'boolean',
            'is_scheduler_active' => 'boolean',
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => [
                'nullable',
                'date_format:Y-m-d',
                'after_or_equal:start_date',
                Rule::prohibitedIf(fn() => $request->input('occurrences') !== null),
            ],
            'occurrences' => [
                'nullable',
                'integer',
                'min:0',
                Rule::prohibitedIf(fn() => $request->input('end_date') !== null),
            ],
            'recurring_frequency' => 'required|in:daily,daily_exclude_holiday,weekly,monthly,yearly,none',
            'days_of_month' => 'nullable|array',
            'days_of_month.*' => 'integer', // Ensuring each item is an integer (supports negative values)
            'days_of_week' => 'nullable|array',
            'days_of_week.*' => 'string|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'interval' => 'required|integer|min:1', // Must be at least 1
            'weeks_of_month' => 'nullable|array',
            'weeks_of_month.*' => 'integer|min:-5|max:5', // Supports 1st to 5th week of the month
            'yearly_dates' => 'nullable|array|min:1',
            'yearly_dates.*' =>'date_format:m-d' ,
    
            'type' => 'required|in:event,event_holiday',
            'date' => 'required|date_format:Y-m-d',
            'check_in_start_time' => 'required|date_format:H:i:s',
            'check_in_end_time' => 'required|date_format:H:i:s|after:check_in_start_time',
            'check_out_start_time' => 'required|date_format:H:i:s|after:check_in_end_time',
            'check_out_end_time' => 'required|date_format:H:i:s|after:check_out_start_time',
            'isPreview' => 'nullable|boolean'
        ],[
            'end_date.prohibited' => 'The end date cannot be set if occurrences is provided.',
            'occurrences.prohibited' => 'The occurrences field cannot be set if end date is provided.',
        ]);
    
        if (!current_school_timezone()) {
            return response()->json([
                'status' => 'error',
                'message' => 'School timezone is not set'
            ], 400);
        }

        $isPreview = $request->query('isPreview') ?? false;
        $allProcessedDates = [];
        $validatedData['school_id'] = current_school_id();
        
        DB::beginTransaction();

        try {
            $event = Event::create($validatedData);
            
            $validatedData['event_id'] = $event->id;
            $attendanceSchedule = AttendanceSchedule::create($validatedData);
        
            $allProcessedDates = (new AttendanceWindowLoaderService($event, $attendanceSchedule))->apply($isPreview);
        
            $isPreview ? DB::rollBack() : DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    
        return response()->json([
            'status' => 'success',
            'message' => 'Attendance schedule created successfully',
            'data' => [
                'attendance_schedule'=>$attendanceSchedule, 
                'event'=>$event,
            ],
            'processed_dates' => $allProcessedDates
        ], 201);

    }

    public function destroy($id){
            $event = Event::findOrFail($id);

            DB::beginTransaction();

            try {
                $event->attendanceSchedule->delete();
                $event->attendanceWindows->delete();
                $event->delete();
                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                throw $e;
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Event deleted successfully'
            ]);
    }
}
