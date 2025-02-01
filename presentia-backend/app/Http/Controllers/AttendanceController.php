<?php

namespace App\Http\Controllers;

use App\Models\CheckInStatus;
use App\Models\AttendanceWindow;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;

use App\Models\Attendance;
use function App\Helpers\convert_timezone_to_utc;
use function App\Helpers\convert_utc_to_timezone;

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
        /*
            Batching rules
            1. the date of all data is same
            2. the source of all date should come from same school
        */
        $jsonInput = $request->all(); //get the data
        $schoolTimeZone = $attendanceWindow->school->timezone ?? 'Asia/Jakarta'; //set the time zone
        $schoolId = $jsonInput[0]['id']; //get the school id
        config(['school.id' => $schoolId]); // config the global variable
        $firstDate = convert_utc_to_timezone(Carbon::parse($jsonInput[0]['date']), $schoolTimeZone); //get the current date by taking first data
        $formattedFirstDate = Carbon::parse($firstDate)->format('Y-m-d'); //format it into like: 29-01-2025 
        
        $attendanceWindow = AttendanceWindow::where('date', $formattedFirstDate)
            ->first(); //get the coressponding window as the input date

        if(!$attendanceWindow){
            abort(404, "Attendance window not found");
        }
        $checkInTypes = CheckInStatus::where('is_active', true)
            ->where('late_duration', '!=', -1)
            ->orderBy('late_duration', 'asc')
            ->get(); //get all check in status without the default -1 CheckInType and asc order by late_duration

        //get and parse the time limit
        $checkInStart = convert_timezone_to_utc($attendanceWindow->date . ' ' .$attendanceWindow->check_in_start_time, $schoolTimeZone);
        $checkInEnd = convert_timezone_to_utc($attendanceWindow->date . ' ' .$attendanceWindow->check_in_end_time, $schoolTimeZone);
        $checkOutStart = convert_timezone_to_utc($attendanceWindow->date . ' ' .$attendanceWindow->check_out_start_time, $schoolTimeZone);
        $checkOutEnd = convert_timezone_to_utc($attendanceWindow->date . ' ' .$attendanceWindow->check_out_end_time, $schoolTimeZone);

        foreach ($jsonInput as $student) {
            $isInCheckInTimeRange = false; //the boolean value that chech if the student present in check in time duration  
            $studentId = $student['id'];
            $checkTime = Carbon::parse($student['date']);

            // base invalid case
            if (
                !Student::find($studentId) || // if the id is invalid
                $checkTime->lt($checkInStart) || //if the attendance record session has not started
                $checkTime->gt($checkOutEnd) || //if the attendance record session has ended
                $checkTime->between($checkInEnd->addMinutes($checkInTypes->max('late_duration')), $checkOutStart) //if is in intolerant lateness time
            ) {
                continue; 
            }

            // get the attendance data for desired student
            $attendance = Attendance::where("student_id", $studentId)
                ->whereHas("attendanceWindow", function ($query) use ($formattedFirstDate) {
                    $query->where("date", $formattedFirstDate);
                })
                ->first();

            //create new one if its not exist
            if (!$attendance) {
                if ($checkTime->gt($checkOutStart)) { //the student has not check in yet
                    continue;
                }

                $attendance = Attendance::Create([
                    'school_id' => $attendanceWindow->school_id,
                    'student_id' => $studentId,
                    'attendance_window_id' => $attendanceWindow->id,
                ]);
            } else {
                //check if the student is a fucking uneducated orphan attention seeker wannabe  with no friends
                if (
                    $attendance->check_in_time && $checkTime->between($checkInStart, $checkInEnd->addMinutes($checkInTypes->max('late_duration'))) ||
                    $attendance->check_out_time && $checkTime->between($checkOutStart, $checkOutEnd)
                ) {
                    continue;
                }
            }

            //iterate each $checkInTypes late duration to decide whether the students is on time or not
            foreach ($checkInTypes as $cit) {
                if ($checkTime->between($checkInStart, $checkInEnd->addMinutes($cit->late_duration))) {
                    $isInCheckInTimeRange = true;
                    $attendance->update([
                        'check_in_status_id' => $cit->id,
                        'check_in_time' => convert_utc_to_timezone($checkTime, $schoolTimeZone)
                    ]);
                    break;
                }
            }

            if (!$isInCheckInTimeRange) {
                $attendance->update([
                    'check_out_time' => convert_utc_to_timezone($checkTime, $schoolTimeZone)
                ]);
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Attendance created successfully',
        ], 201);

    }

    public function exportAttendance(){
        
    }

    public function getById($id)
    {
        $attendance = Attendance::find($id);
        return response()->json([
            'status' => 'success',
            'message' => 'Attendance retrieved successfully',
            'data' => $attendance
        ]);
    }

    public function update(Request $request, $id)
    {
        $attendance = Attendance::find($id);
        $attendance->update($request->all());
        return response()->json([
            'status' => 'success',
            'message' => 'Attendance updated successfully',
            'data' => $attendance
        ]);

    }

    public function destroy($id)
    {
        $attendance = Attendance::find($id);
        $attendance->delete();
        return response()->json([
            'status' => 'success',
            'message' => 'Attendance deleted successfully'
        ]);
    }
}

