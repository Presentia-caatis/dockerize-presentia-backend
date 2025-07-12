<?php

namespace App\Http\Controllers;

use App\Models\AbsencePermitType;
use App\Models\AttendanceSchedule;
use App\Models\CheckInStatus;
use App\Models\CheckOutStatus;
use App\Models\Day;
use App\Models\SubscriptionPlan;
use Carbon\Carbon;
use Illuminate\Http\Request;

use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Str;
use function App\Helpers\convert_utc_to_timezone;

class SchoolController extends Controller
{
    public function getAll(Request $request)
    {
        $validatedData = $request->validate([
            'perPage' => 'sometimes|integer|min:1'
        ]);

        $perPage = $validatedData['perPage'] ?? 10;

        $data = School::paginate($perPage);

        // $data->getCollection()->transform(function ($school) {
        //     if ($school->logo_image_path) {
        //         $school->logo_image_path = asset('storage/' . $school->logo_image_path);
        //     }
        //     return $school;
        // });
        return response()->json([
            'status' => 'success',
            'message' => 'Schools retrieved successfully',
            'data' => $data
        ]);
    }

    public function getById($id)
    {
        $school = School::findOrFail($id);
        // if ($school->logo_image_path) {
        //     $school->logo_image_path = asset('storage/' . $school->logo_image_path);
        // }

        unset($school->school_token);

        return response()->json([
            'status' => 'success',
            'message' => 'School retrieved successfully',
            'data' => $school
        ]);
    }

    public function taskSchedulerToogle($id)
    {
        $school = School::findOrFail($id);
        $school->is_task_scheduling_active = !$school->is_task_scheduling_active;
        $school->save();
        return response()->json([
            'status' => 'success',
            'message' => $school->name . " Task Scheduler is " . ($school->is_task_scheduling_active ? 'active' : 'inactive'),
        ]);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string',
            'address' => 'required|string',
            'timezone' => 'required|timezone',
            'logo_image' => 'nullable|file|mimes:jpg,jpeg,png',
            'user_id' => 'required|exists:users,id',
        ]);

        try {
            \DB::beginTransaction();

            if ($request->hasFile('logo_image')) {
                $validatedData['logo_image_path'] = $request->file('logo_image')->store($request->file('logo_image')->extension(), 'public');
            }

            $validatedData['subscription_plan_id'] = SubscriptionPlan::where('billing_cycle_month', 0)->first()->id;
            do {
                $token = Str::random(10);
            } while (School::where('school_token', $token)->exists());

            $validatedData['school_token'] = $token;
            $validatedData['latest_subscription'] = convert_utc_to_timezone(Carbon::now(), $validatedData['timezone']);

            $school = School::create($validatedData);

            $user = User::findOrFail($validatedData['user_id']);
            if ($user->school_id !== null) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User already assigned to another school',
                ], 422);
            }

            $user->school_id = $school->id;
            $user->assignRole('school_admin');
            $user->save();

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

            CheckInStatus::insert([
                [
                    'status_name' => 'Late',
                    'description' => 'Student checked in after the allowed time with a grace period of 15 minutes.',
                    'late_duration' => 15,
                    'is_active' => true,
                    'school_id' => $school->id,
                ],
                [
                    'status_name' => 'On Time',
                    'description' => 'Student checked in within the designated time frame.',
                    'late_duration' => 0,
                    'is_active' => true,
                    'school_id' => $school->id,
                ],
                [
                    'status_name' => 'Absent',
                    'description' => 'Student did not check in and is considered absent for the day.',
                    'late_duration' => -1,
                    'is_active' => true,
                    'school_id' => $school->id,
                ],
            ]);

            CheckOutStatus::insert([
                [
                    'status_name' => 'absent',
                    'description' => 'Student did not check out, indicating absence for the day.',
                    'late_duration' => -1,
                    'school_id' => $school->id,
                ],
                [
                    'status_name' => 'present',
                    'description' => 'Student successfully checked out within the allowed time.',
                    'late_duration' => 0,
                    'school_id' => $school->id,
                ],
            ]);

            AbsencePermitType::insert([
                [
                    'school_id' => $school->id,
                    'permit_name' => 'Sick',
                    'is_active' => true,
                ],
                [
                    'school_id' => $school->id,
                    'permit_name' => 'Dispensation',
                    'is_active' => true,
                ],
            ]);
            // if($request->logo_image){
            //     $school->logo_image_path = asset('storage/' . $school->logo_image_path);
            // }


            \DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'School created successfully',
                'data' => $school
            ], 201);
        } catch (\Exception $e) {
            \DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create school',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $school = School::findOrFail($id);

        $validatedData = $request->validate([
            'subscription_plan_id' => 'nullable|exists:subscription_plans,id',
            'name' => 'nullable|string',
            'address' => 'nullable|string',
            'remove_image' => 'sometimes|boolean',
            'logo_image' => 'nullable|file|mimes:jpg,jpeg,png'
        ]);


        if (isset($validatedData['remove_image']) && $validatedData['remove_image']) {
            if ($school->logo_image_path) {
                Storage::disk('public')->delete($school->logo_image_path);
            }
            $school->logo_image_path = null;
        } else if ($request->hasFile('logo_image')) {
            if ($school->logo_image_path) {
                Storage::disk('public')->delete($school->logo_image_path);
            }

            $school->logo_image_path = $request->file('logo_image')->store($request->file('logo_image')->extension(), 'public');
        }


        $school->update($validatedData);

        return response()->json([
            'status' => 'success',
            'message' => 'School updated successfully',
            'data' => $school
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $expectedConfirmation = 'I acknowledge that this action cannot be undone. Delete the school.';

        $request->validate([
            'delete_confirmation' => [
                'required',
                'string',
                function ($attribute, $value, $fail) use ($expectedConfirmation) {
                    if (trim(strtolower($value)) !== strtolower($expectedConfirmation)) {
                        $fail('The confirmation sentence should be: ' . $expectedConfirmation);
                    }
                },
            ]
        ], [
            'delete_confirmation.required' => 'The delete confirmation field is required. Please fill in with: ' . $expectedConfirmation,
        ]);

        $school = School::findOrFail($id);

        User::where('school_id', $school->id)->update(['school_id' => null]);


        $attendanceScheduleIds = Day::pluck('attendance_schedule_id');
        AttendanceSchedule::whereIn('id', $attendanceScheduleIds)->delete();

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
