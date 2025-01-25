<?php

namespace App\Http\Controllers;

use App\Models\CheckInStatus;
use App\Models\AttendanceWindow;
use App\Models\Scopes\SchoolScope;
use Carbon\Carbon;
use Illuminate\Http\Request;

use App\Models\Attendance;

class AttendanceController extends Controller
{
    public function index()
    {
        $data = Attendance::with('student', 'checkInStatus')->orderBy('check_in_time')->get();
        return response()->json([
            'status' => 'success',
            'message' => 'Attendances retrieved successfully',
            'data' => $data
        ]);
    }


    public function store(Request $request)
    {
        $jsonInput = $request->all(); 

        $firstDate = $jsonInput['0']['date']; 
        $formattedFirstDate = Carbon::parse($firstDate)->format('Y-m-d');

        $attendanceWindow = AttendanceWindow::whereDate('date', $formattedFirstDate)
            ->first();

        $attendanceLateTypes = CheckInStatus::where('is_active', true)
            ->where('late_duration', '!=', -1)
            ->orderBy('late_duration', 'asc')
            ->get();

        $checkInStart = Carbon::parse($attendanceWindow->check_in_start_time);
        $checkInEnd = Carbon::parse($attendanceWindow->check_in_end_time);
        $checkOutStart = Carbon::parse($attendanceWindow->check_out_start_time);
        $latestCheckInTypeId = CheckInStatus::where("late_duration", -1)->first()->id;
        $isType = false;

        foreach ($jsonInput as $student) {
            $studentId = $student['id'];
            $checkDate = Carbon::parse($student['date'])->utc();

            if ($checkDate->lt($checkInStart)) {
                continue;
            }

            
            $attendace = Attendance::where("student_id", $studentId)
                ->where("check_in_time", $checkDate)
                ->whereHas("attendanceWindow", function($query) use ($formattedFirstDate){
                    $query->where("date", $formattedFirstDate);
                })
                ->first();

            if(!$attendace){
                $attendace = Attendance::Create([
                    'school_id' => $attendanceWindow->school_id,
                    'student_id' => $studentId,
                    'attendance_window_id' => $attendanceWindow->id,
                    'check_in_status_id' => $latestCheckInTypeId,
                ]);
            }

            foreach ($attendanceLateTypes as $atc) {

                // \Log::info("Student ID: $studentId, Student Date: $checkDate");
                // \Log::info("Check-in Start: $checkInStart, Check-in End: " . $checkInEnd->addMinutes($atc->late_duration));
                // \Log::info("Check-out Start: $checkOutStart, Check-out End: " . $checkOutEnd->addMinutes($atc->late_duration));

                /* 
                    1. if its on time (check_in_start_time <= check_in_time <= check_in_end_time) and (check_out_start_time <= check_out_time <= check_out_end_time)
                    2. if its late (check_in_time > check_in_end_time) and (check_out_time > check_out_end_time)
                    2.1 if its late by the rules that has been set to the user
                    2.2 if its late by the rules that has been set to the sysytem
                    2.2.1 0 minutes late
                    2.2.2 check_out_start_time -  check_in_end_time late (all student will be considered as absence)
                    3. Edit or Delete existing CheckInStatus
                    3.1 if its already assign to student (for check_in_time > 0)
                    3.1.1 if the user will update all assign student -> read all connected attandance with the attendaceWindows -> recheck the attendance status
                    3.1.1.1 if the new late duration < the old late duration || delete the late duration -> update the user that in recent category
                    3.1.1.2 if the new late duration > the old late duration -> update the user that in above category
                    3.1.2 else make new CheckInStatus with false status and create a new one.
                    3.2 if its not assign to student -> directly edit or delete the CheckInStatus
                */

                if ($checkDate->between($checkInStart, $checkInEnd->addMinutes($atc->late_duration))) {
                    $isType = true;
                    $attendace->update([
                        'check_in_status_id' => $atc->id,
                        'check_in_time' => $checkDate->toISOString()
                    ]);
                    break;
                }
            }


            if (!$isType) {
                if($checkDate->between($checkInEnd, $checkOutStart)){
                    $attendace->update([
                        'check_in_status_id' => $latestCheckInTypeId,
                        'check_in_time' => $checkDate->toISOString()
                    ]);
                } else {
                    $attendace->update([
                        'check_out_time' => $checkDate->toISOString()
                    ]);
                }
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Attendance created successfully',
        ], 201);

    }

    public function show(Attendance $attendance)
    {

        return response()->json([
            'status' => 'success',
            'message' => 'Attendance retrieved successfully',
            'data' => $attendance
        ]);

    }

    public function update(Request $request, Attendance $attendance)
    {

        $attendance->update($request->all());
        return response()->json([
            'status' => 'success',
            'message' => 'Attendance updated successfully',
            'data' => $attendance
        ]);

    }

    public function destroy(Attendance $attendance)
    {

        $attendance->delete();
        return response()->json([
            'status' => 'success',
            'message' => 'Attendance deleted successfully'
        ]);

    }
}

