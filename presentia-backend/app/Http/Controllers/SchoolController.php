<?php

namespace App\Http\Controllers;

use App\Models\AttendanceSchedule;
use App\Models\CheckInStatus;
use App\Models\Day;
use App\Models\SubscriptionPlan;
use Carbon\Carbon;
use Illuminate\Http\Request;

use App\Models\School;
use Str;
use function App\Helpers\convert_utc_to_timezone;

class SchoolController extends Controller
{
    public function index()
    {

        $data = School::all();
        return response()->json([
            'status' => 'success',
            'message' => 'Schools retrieved successfully',
            'data' => $data
        ]);

    }

    public function taskSchedulerToogle($id){
        $school = School::findOrFail($id);
        $school->is_task_scheduling_active = !$school->is_task_scheduling_active;
        $school->save();
        return response()->json([
            'status' => 'success',
            'message' => "Schools Task Scheduler is " . ($school->is_task_scheduling_active ? 'active' : 'inactive'),
        ]);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string',
            'address' => 'required|string',
            'timezone' => 'required|timezone'
        ]);
        $validatedData['subscription_plan_id'] = SubscriptionPlan::where('billing_cycle_month', 0)->first()->id;
        $validatedData['school_token'] = Str::uuid();
        $validatedData['latest_subscription'] = convert_utc_to_timezone(Carbon::now(), $validatedData['timezone']);
        
        $school = School::create($validatedData);


        $defaultAttendanceSchedule = AttendanceSchedule::create([
            'event_id' => null,
            'type' => 'default',
            'name' => 'Default Schedule',
            'check_in_start_time' => '06:00:00',
            'check_in_end_time' => '06:30:00',
            'check_out_start_time' => '16:00:00',
            'check_out_end_time' => '17:00:00',
        ]);

        $holidayAttendanceSchedule = AttendanceSchedule::create([
            'event_id' => null,
            'type' => 'holiday',
            'name' => 'Holiday Schedule',
        ]);

        $weekdays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
        foreach ($weekdays as $day) {
            Day::create([
                'attendance_schedule_id' => $defaultAttendanceSchedule->id,
                'school_id' => $school->id,
                'name' => $day,
            ]);
        }

        $weekends = ['saturday', 'sunday'];
        foreach ($weekends as $day) {
            Day::create([
                'attendance_schedule_id' => $holidayAttendanceSchedule->id,
                'school_id' => $school->id,
                'name' => $day,
            ]);
        }

        CheckInStatus::create([
            'status_name' => 'Late',
            'description' => 'Late',
            'late_duration' => 15,
            'is_active' => true,
            'school_id' => $school->id,
        ]);

        CheckInStatus::create([
            'status_name' => 'On Time',
            'description' => 'On Time',
            'late_duration' => 0,
            'is_active' => true,
            'school_id' => $school->id,
        ]);

        CheckInStatus::create([
            'status_name' => 'Absence',
            'description' => 'Absence',
            'late_duration' => -1,
            'is_active' => true,
            'school_id' => $school->id,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'School created successfully',
            'data' => $school
        ],201);

    }

    public function getById(School $School)
    {
        return response()->json([
            'status' => 'success',
            'message' => 'School retrieved successfully',
            'data' => $School
        ]);

    }

    public function update(Request $request, School $School)
    {

        $validatedData = $request->validate([
            'subscription_plan_id' => 'required|exists:subscription_plans,id',
            'school_name' => 'required|string',
            'address' => 'required|string',
            'latest_subscription' => 'required|date',
            'end_subscription' => 'required|date',
        ]);

        $School->update($validatedData);
        return response()->json([
            'status' => 'success',
            'message' => 'School updated successfully',
            'data' => $School
        ]);

    }

    public function destroy(School $School)
    {
        $School->delete();
        return response()->json([
            'status' => 'success',
            'message' => 'School deleted successfully'
        ]);

    }
}
