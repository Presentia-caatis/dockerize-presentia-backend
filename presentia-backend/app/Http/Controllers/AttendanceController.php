<?php

namespace App\Http\Controllers;

use App\Exports\AttendanceExport;
use App\Models\CheckInStatus;
use App\Models\AttendanceWindow;
use App\Models\ClassGroup;
use App\Models\Scopes\SchoolScope;
use App\Models\Student;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;

use App\Models\Attendance;
use function App\Helpers\convert_timezone_to_utc;
use function App\Helpers\convert_utc_to_timezone;
use function App\Helpers\current_school_timezone;
use function App\Helpers\stringify_convert_timezone_to_utc;
use function App\Helpers\stringify_convert_utc_to_timezone;

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
            'perPage' => 'sometimes|integer|min:1',
            'simplify' => 'sometimes|boolean',
            'attendanceWindowId' => 'sometimes|exists:attendance_windows,id',
            'type' => 'sometimes|in:in,out'
        ]);

        $perPage = $validatedData['perPage'] ?? 10;

        $simplify = $validatedData['simplify'] ?? true;
        $type = $validatedData['type'] ?? null;
        if ($simplify) {
            $query = Attendance::with([
                'student:id,student_name,nis,nisn,gender,class_group_id',
                'student.classGroup:id,class_name',
                'checkInStatus:id,status_name',
            ])->select([
                        'id',
                        'student_id',
                        'check_in_status_id',
                        'check_in_time',
                        'check_out_time'
                    ]);
        } else {
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

        if ($type === 'in') {
            $query->orderBy('check_in_time', 'desc');
        } else if ($type === 'out') {
            $query->whereNotNull('check_out_time')->where('check_out_time', '!=', '')
                ->orderBy('check_out_time', 'desc');
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
            Batching rules:
            1. The date of all data must be the same.
            2. The source of all data should come from the same school.
        */

        set_time_limit(7200);

        $jsonInput = $request->all();

        if (empty($jsonInput)) {
            return response()->json([
                'status' => 'failed',
                'message' => 'data is null',
                'failed_records' => []
            ], 201);
        }

        $studentId = $jsonInput[0]['id']; // Get the student ID
        $schoolId = Student::withoutGlobalScope(SchoolScope::class)->find($studentId)?->school_id;

        if (!$schoolId) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Invalid student ID',
                'failed_records' => []
            ], 400);
        }

        config(['school.id' => $schoolId]);

        $schoolTimeZone = current_school_timezone() ?? 'Asia/Jakarta';

        // Extract unique dates from input to minimize queries
        $inputDates = array_unique(array_map(function ($item) use ($schoolTimeZone) {
            return Carbon::parse($item['date'])
                ->setTimezone($schoolTimeZone)  // Convert to school timezone
                ->format('Y-m-d');              // Format as Y-m-d
        }, $jsonInput));

        // Cache attendance windows for the given dates
        $attendanceWindows = AttendanceWindow::whereIn('date', $inputDates)
            ->get()
            ->keyBy('date'); // Store in a collection with `date` as the key

        // Cache CheckInStatus to avoid repeated queries
        $checkInTypes = CheckInStatus::where('is_active', true)
            ->where('late_duration', '!=', -1)
            ->orderBy('late_duration', 'asc')
            ->get();

        $absenceCheckInStatus = CheckInStatus::where('late_duration', -1)->first();

        // Store failed records
        $failedRecords = [];

        foreach ($jsonInput as $student) {
            $isInCheckInTimeRange = false;
            $studentId = $student['id'];
            $checkTime = Carbon::parse($student['date']);
            $formattedDate = Carbon::parse($student['date'])->setTimezone($schoolTimeZone)->format('Y-m-d');

            // Retrieve attendance window from cache
            $attendanceWindow = $attendanceWindows[$formattedDate] ?? null; 

            if (!$attendanceWindow) {
                $failedRecords[] = [
                    'student_id' => $studentId,
                    'reason' => 'No attendance window found for date ' . $formattedDate
                ];
                continue; // Skip if attendance window is not found
            }

            // Convert times for comparison
            $checkInStart = convert_timezone_to_utc($attendanceWindow->date . ' ' . $attendanceWindow->check_in_start_time, $schoolTimeZone);
            $checkInEnd = convert_timezone_to_utc($attendanceWindow->date . ' ' . $attendanceWindow->check_in_end_time, $schoolTimeZone);
            $checkOutStart = convert_timezone_to_utc($attendanceWindow->date . ' ' . $attendanceWindow->check_out_start_time, $schoolTimeZone);
            $checkOutEnd = convert_timezone_to_utc($attendanceWindow->date . ' ' . $attendanceWindow->check_out_end_time, $schoolTimeZone);

            // Base invalid case
            if (
                !Student::find($studentId) || // If the student ID is invalid
                $checkTime->lt($checkInStart) || // If the attendance record session hasn't started
                $checkTime->gt($checkOutEnd) || // If the attendance session has ended
                $checkTime->between($checkInEnd->copy()->addMinutes($checkInTypes->max('late_duration')), $checkOutStart) // If it's in an intolerant lateness period
            ) {
                $failedRecords[] = [
                    'student_id' => $studentId,
                    'attendance_window_id' => $attendanceWindow->id ?? null,
                    'attendance_window_date' => $attendanceWindow->date ?? null,
                    'check_in_start' => $attendanceWindow->check_in_start_time ?? null,
                    'check_in_end' => $attendanceWindow->check_in_end_time ?? null,
                    'check_out_start' => $attendanceWindow->check_out_start_time ?? null,
                    'check_out_end' => $attendanceWindow->check_out_end_time ?? null,
                    'failed_check_time' => $checkTime->toDateTimeString(),
                    'reason' => 'Invalid check-in time or unrecognized student ID'
                ];
                continue;
            }

            // Get the attendance record for the student
            $attendance = Attendance::where("student_id", $studentId)
                ->where('attendance_window_id', $attendanceWindow->id)
                ->first();

            // If no attendance record exists, create one
            if (!$attendance) {
                if ($checkTime->gt($checkOutStart)) { // The student has not checked in yet
                    $failedRecords[] = [
                        'student_id' => $studentId,
                        'reason' => 'Student has not checked in yet'
                    ];
                    continue;
                }
                try{
                    $attendance = Attendance::create([
                        'school_id' => $schoolId,
                        'student_id' => $studentId,
                        'attendance_window_id' => $attendanceWindow->id,
                        'check_in_status_id' => $absenceCheckInStatus->id
                    ]);
                } catch(Exception $e){
                    $failedRecords[] = [
                        'student_id' => $studentId,
                        'reason' => 'Something went wrong: ' . $e->getMessage() 
                    ];
                    continue;
                }
            } else {
                // Prevent duplicate check-ins
                if (
                    ($attendance->check_in_time && $checkTime->between($checkInStart, $checkInEnd->copy()->addMinutes($checkInTypes->max('late_duration')))) ||
                    ($attendance->check_out_time && $checkTime->between($checkOutStart, $checkOutEnd))
                ) {
                    $failedRecords[] = [
                        'student_id' => $studentId,
                        'reason' => 'Duplicate check-in or check-out attempt'
                    ];
                    continue;
                }
            }

            // Determine check-in status
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

            // If the student is not in check-in range, mark check-out time
            if (!$isInCheckInTimeRange) {
                $attendance->update([
                    'check_out_time' => convert_utc_to_timezone($checkTime, $schoolTimeZone)
                ]);
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Attendance processed successfully',
            'failed_records' => $failedRecords
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
        $attendance = Attendance::findOrFail($id);
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
