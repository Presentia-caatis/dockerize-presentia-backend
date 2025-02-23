<?php

namespace App\Http\Controllers;

use App\Models\AttendanceSchedule;
use App\Models\CheckInStatus;
use App\Models\Day;
use App\Models\SubscriptionPlan;
use Carbon\Carbon;
use Illuminate\Http\Request;

use App\Models\School;
use Illuminate\Support\Facades\Storage;
use Str;
use function App\Helpers\convert_utc_to_timezone;

class SchoolController extends Controller
{
    public function index(Request $request)
    {
        $validatedData = $request->validate([
            'perPage' => 'sometimes|integer|min:1' 
        ]);

        $perPage = $validatedData['perPage'] ?? 10;

        $data = School::paginate($perPage);

        $data->getCollection()->transform(function ($school) {
            if($school->logo_image_path){
                $school->logo_image_path =  asset('storage/' . $school->logo_image_path);
            }
            return $school;
        });
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
            'message' => $school->name." Task Scheduler is " . ($school->is_task_scheduling_active ? 'active' : 'inactive'),
        ]);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string',
            'address' => 'required|string',
            'timezone' => 'required|timezone',
            'logo_image' => 'nullable|file|mimes:jpg,jpeg,png'
        ]);

        if($request->hasFile('logo_image')){
            $validatedData['logo_image_path'] = $request->file('logo_image')->store($request->file('logo_image')->extension(),'public');
        };
        
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
            'status_name' => 'Absent',
            'description' => 'Absent',
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

    public function getById($id)
    {
        $school = School::findOrFail($id); 
        if($school->logo_image_path){
            $school->logo_image_path =  asset('storage/' . $school->logo_image_path);
        }
        return response()->json([
            'status' => 'success',
            'message' => 'School retrieved successfully',
            'data' => $school
        ]);

    }

    public function update(Request $request, $id)
    {
        $school=School::findOrFail($id);

        $validatedData = $request->validate([
            'subscription_plan_id' => 'nullable|exists:subscription_plans,id',
            'school_name' => 'nullable|string',
            'address' => 'nullable|string',
            'remove_image' => 'sometimes|boolean',
            'logo_image' => 'nullable|file|mimes:jpg,jpeg,png'
        ]);


        if(isset($validatedData['remove_image']) && $validatedData['remove_image']){
            if ($school->logo_image_path) {
                Storage::disk('public')->delete($school->logo_image_path);
            }
            $school->logo_image_path = null;
        } else if ($request->hasFile('logo_image')) {
            if ($school->logo_image_path) {
                Storage::disk('public')->delete($school->logo_image_path);
            }

            $school->logo_image_path = $request->file('logo_image')->store($request->file('logo_image')->extension(),'public');
        }


        $school->update($validatedData);
        return response()->json([
            'status' => 'success',
            'message' => 'School updated successfully',
            'data' => $school
        ]);

    }

    public function destroy($id)
    {
        $school=School::findOrFail($id);
        if ($school->logo_image_path) {
            Storage::disk('public')->delete($school->logo_image_path);
        }
        $school->delete();
        return response()->json([
            'status' => 'success',
            'message' => 'School deleted successfully'
        ]);

    }
}
