<?php

namespace App\Http\Controllers;

use App\Exports\AttendanceExport;
use App\Models\CheckInStatus;
use App\Models\AttendanceWindow;
use App\Models\ClassGroup;
use App\Models\Scopes\SchoolScope;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;

use App\Models\Attendance;
use function App\Helpers\convert_timezone_to_utc;
use function App\Helpers\convert_utc_to_timezone;
use function App\Helpers\current_school_timezone;
use function App\Helpers\stringify_convert_timezone_to_utc;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $validatedData = $request->validate([
            'startDate' => ['required_with:endDate', 'date_format:Y-m-d'],
            'endDate' => ['required_with:startDate', 'date_format:Y-m-d', 'after_or_equal:startDate'],
            'classGroup' => [
                'sometimes',
                function ($attribute, $value, $fail) {
                    if ($value !== 'all') {
                        $ids = explode(',', $value);
                        $validIds = ClassGroup::whereIn('id', $ids)->pluck('id')->toArray();
                        if (array_diff($ids, $validIds)) {
                            $fail("The selected $attribute contains invalid class group IDs.");
                        }
                    }
                },
            ],
            'checkInStatusId' => [
                'sometimes',
                function ($attribute, $value, $fail) {
                    if ($value !== 'all') {
                        $ids = explode(',', $value);
                        $validIds = CheckInStatus::whereIn('id', $ids)->pluck('id')->toArray();
                        if (array_diff($ids, $validIds)) {
                            $fail("The selected $attribute contains invalid check in status IDs.");
                        }
                    }
                },
            ],
            'checkInTimeOrderType' => 'sometimes|in:asc,desc',
            'checkOutTimeOrderType' => 'sometimes|in:asc,desc',
            'perPage' => 'sometimes|integer|min:1',
            'simplify' => 'sometimes|boolean',
        ]);

        $perPage = $validatedData['perPage'] ?? 10;

        $simplify = $validatedData['simplify'] ?? true;
        if($simplify){
            $query = Attendance::with([
                'student:id,student_name,nis,nisn,gender,class_group_id',
                'student.classGroup:id,class_name',  
                'checkInStatus:id,status_name',
            ])->select([
                'id', 'student_id', 'check_in_status_id', 'check_in_time', 'check_out_time'
            ]);
        } else{
            $query = Attendance::with('student', 'checkInStatus');
        }

        

        if (!empty($validatedData['startDate']) && !empty($validatedData['endDate'])) {
            $query->whereHas('attendanceWindow', function ($q) use ($validatedData) {
                $q->whereBetween('date', [$validatedData['startDate'], $validatedData['endDate']]);
            });
        }

        if (!empty($validatedData['classGroup']) && $validatedData['classGroup'] !== 'all') {
            $classGroupIds = explode(',', $validatedData['classGroup']);
            $query->whereHas('student', function ($q) use ($classGroupIds) {
                $q->whereIn('class_group_id', $classGroupIds);
            });
        }

        if (!empty($validatedData['checkInStatusId']) && $validatedData['checkInStatusId'] !== 'all') {
            $checkInStatusIds = explode(',', $validatedData['checkInStatusId']);
            $query->whereIn('check_in_status_id', $checkInStatusIds);
        }

        if (!empty($validatedData['checkInTimeOrderType'])) {
            $query->orderBy('check_in_time', $validatedData['checkInTimeOrderType']);
        } elseif (!empty($validatedData['checkOutTimeOrderType'])) {
            $query->orderBy('check_out_time', $validatedData['checkOutTimeOrderType']);
        }

        $data = $query->paginate($perPage);

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

        if (empty($jsonInput)) {
            return response()->json([
                'status' => 'failed',
                'message' => 'data is null',
            ], 201);
        }

        $studentId = $jsonInput[0]['id']; //get the student id
        config(['school.id' => Student::withoutGlobalScope(SchoolScope::class)->find($studentId)->school_id]); // config the global variable

        $schoolTimeZone = current_school_timezone() ?? 'Asia/Jakarta'; //set the time zone
        $firstDate = convert_utc_to_timezone(Carbon::parse($jsonInput[0]['date']), $schoolTimeZone); //get the current date by taking first data
        $formattedFirstDate = Carbon::parse($firstDate)->format('Y-m-d'); //format it into like: 29-01-2025 

        $attendanceWindow = AttendanceWindow::where('date', $formattedFirstDate)
            ->first(); //get the coressponding window as the input date

        if (!$attendanceWindow) {
            abort(404, "Attendance window not found");
        }
        $checkInTypes = CheckInStatus::where('is_active', true)
            ->where('late_duration', '!=', -1)
            ->orderBy('late_duration', 'asc')
            ->get(); //get all check in status without the default -1 CheckInType and asc order by late_duration

        //get and parse the time limit
        $checkInStart = convert_timezone_to_utc($attendanceWindow->date . ' ' . $attendanceWindow->check_in_start_time, $schoolTimeZone);
        $checkInEnd = convert_timezone_to_utc($attendanceWindow->date . ' ' . $attendanceWindow->check_in_end_time, $schoolTimeZone);
        $checkOutStart = convert_timezone_to_utc($attendanceWindow->date . ' ' . $attendanceWindow->check_out_start_time, $schoolTimeZone);
        $checkOutEnd = convert_timezone_to_utc($attendanceWindow->date . ' ' . $attendanceWindow->check_out_end_time, $schoolTimeZone);

        foreach ($jsonInput as $student) {
            $isInCheckInTimeRange = false; //the boolean value that chech if the student present in check in time duration
            $studentId = $student['id'];
            $checkTime = Carbon::parse($student['date']);

            // base invalid case
            if (
                !Student::find($studentId) || // if the id is invalid
                $checkTime->lt($checkInStart) || //if the attendance record session has not started
                $checkTime->gt($checkOutEnd) || //if the attendance record session has ended
                $checkTime->between($checkInEnd->copy()->addMinutes($checkInTypes->max('late_duration')), $checkOutStart) //if is in intolerant lateness time
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
                    'check_in_status_id' => CheckInStatus::where('late_duration', -1)->first()->id
                ]);
            } else {
                //check if the student is a fucking uneducated orphan attention seeker wannabe  with no friends
                if (
                    $attendance->check_in_time && $checkTime->between($checkInStart, $checkInEnd->copy()->addMinutes($checkInTypes->max('late_duration'))) ||
                    $attendance->check_out_time && $checkTime->between($checkOutStart, $checkOutEnd)
                ) {
                    continue;
                }
            }
            //iterate each $checkInTypes late duration to decide whether the students is on time or not
            foreach ($checkInTypes as $cit) {
                if ($checkTime->between($checkInStart, $checkInEnd->copy()->addMinutes($cit->late_duration))) {
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

    public function exportAttendance(Request $request)
    {
        $validated = $request->validate([
            'startDate' => ['sometimes', 'date_format:Y-m-d'],
            'endDate' => ['sometimes', 'date_format:Y-m-d', 'after_or_equal:startDate'],
            'classGroup' => [
                'sometimes',
                function ($attribute, $value, $fail) {
                    if ($value !== 'all') {
                        $ids = explode(',', $value);
                        $validIds = ClassGroup::whereIn('id', $ids)->pluck('id')->toArray();

                        if (array_diff($ids, $validIds)) {
                            $fail("The selected $attribute contains invalid class group IDs.");
                        }
                    }
                },
            ],
        ]);


        $startDate = $validated['startDate'] ?? AttendanceWindow::min('date');
        $endDate = $validated['endDate'] ?? AttendanceWindow::max('date');
        $classGroup = $validated['classGroup'] ?? 'all';

        $classCounter = $classGroup == 'all' ? 'all' : count(explode(',', $classGroup));
        $now = stringify_convert_timezone_to_utc(now(), current_school_timezone());

        $filename = sprintf(
            "%s_attendance_record_%s_%s%s.xlsx",
            $now,
            $startDate,
            $endDate,
            $classCounter == 'all' ? '_all' : "_{$classCounter}_class"
        );

        return (new AttendanceExport($startDate, $endDate, $classGroup))->download($filename);
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

