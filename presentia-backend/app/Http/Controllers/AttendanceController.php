<?php

namespace App\Http\Controllers;

use App\Exports\AttendanceExport;
use App\Filterable;
use App\Jobs\StoreAttendanceJob;
use App\Models\CheckInStatus;
use App\Models\AttendanceWindow;
use App\Models\CheckOutStatus;
use App\Models\ClassGroup;
use App\Models\Scopes\SchoolScope;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;

use App\Models\Attendance;
use function App\Helpers\current_school_id;
use function App\Helpers\current_school_timezone;
use function App\Helpers\stringify_convert_timezone_to_utc;


class AttendanceController extends Controller
{
    use Filterable;
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

        $simplify = $validatedData['simplify'] ?? false;
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
                        'attendance_window_id',
                        'check_in_time',
                        'check_out_time'
                    ]);
        } else {
            $query = Attendance::with('student', 'checkInStatus');
        }

        $query = $this->applyFilters($query, $request->input('filter', []), ['school']);

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
        $jsonInput = $request->all();

        if (empty($jsonInput)) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Data is null'
            ], 201);
        }
        
        StoreAttendanceJob::dispatch($jsonInput)->onQueue('attendance');

        return response()->json([
            'status' => 'success',
            'message' => 'Attendance processing has started'
        ], 201);
    }

    public function storeManualAttendance(Request $request)
    {
        $validatedData = $request->validate([
            'attendance_window_id' => 'required|exists:attendance_windows,id',
            'student_id' => 'required|exists:students,id',
            'absence_permit_id' => 'nullable|exists:absence_permits,id',
            'check_in_time' => 'required|date_format:Y-m-d H:i:s',
            'check_out_time' => 'nullable|date_format:Y-m-d H:i:s',
        ]);

        $timeValidationResponse = $this->validateAttendanceTime(
            $validatedData['check_in_time'] ?? null,
            $validatedData['check_out_time'] ?? null,
            $validatedData['attendance_window_id'],
            $validatedData
        );

        if ($timeValidationResponse) {
            return $timeValidationResponse;
        }

        $validatedData['school_id'] = current_school_id();

        $data = Attendance::create($validatedData);

        return response()->json([
            'status' => 'success',
            'message' => 'Attendance created successfully',
            'data' => $data
        ]);
    }

    public function update(Request $request, $id)
    {
        $attendance = Attendance::findOrFail($id);

        $validatedData = $request->validate([
            'attendance_window_id' => 'required|exists:attendance_windows,id',
            'absence_permit_id' => 'required_without_all:check_in_time,check_out_time|exists:absence_permits,id',
            'check_in_time' => 'required_without_all:absence_permit_id,check_out_time|date_format:Y-m-d H:i:s',
            'check_out_time' => 'required_without_all:absence_permit_id,check_in_time||date_format:Y-m-d H:i:s',
        ]);


        // Validate check-in and check-out time using the new function
        $timeValidationResponse = $this->validateAttendanceTime(
            $validatedData['check_in_time'] ?? null,
            $validatedData['check_out_time'] ?? null,
            $validatedData['attendance_window_id'] ?? null,
            $validatedData
        );

        if ($timeValidationResponse) {
            return $timeValidationResponse; // Return the error response if validation fails
        }

        $attendance->update($validatedData);

        return response()->json([
            'status' => 'success',
            'message' => 'Attendance updated successfully',
            'data' => $attendance
        ]);
    }

    private function validateAttendanceTime($checkInTime, $checkOutTime, $attendanceWindowId, &$validatedData)
    {
        $attendanceWindow = AttendanceWindow::findOrFail($attendanceWindowId);
        $checkInStatus = CheckInStatus::where('late_duration', '!=', -1)->orderBy('late_duration')->get();

        $checkInStart = Carbon::parse($attendanceWindow->date . ' ' . $attendanceWindow->check_in_start_time);
        $checkInEnd = Carbon::parse($attendanceWindow->date . ' ' . $attendanceWindow->check_in_end_time);
        $checkOutStart = Carbon::parse($attendanceWindow->date . ' ' . $attendanceWindow->check_out_start_time);
        $checkOutEnd = Carbon::parse($attendanceWindow->date . ' ' . $attendanceWindow->check_out_end_time);

        if ($checkOutTime) {
            $checkOutTimeParsed = Carbon::parse($checkOutTime);
            if (!$checkOutTimeParsed->between($checkOutStart, $checkOutEnd)) {
                return response()->json([
                    'status' => 'failed',
                    'message' => "Attendance's check out time is outside attendance window range",
                ], 422);
            }
        }

        if ($checkInTime) {
            $checkInTimeParsed = Carbon::parse($checkInTime);
            $isInsideCheckInTimeRange = false;

            foreach ($checkInStatus as $cit) {
                if ($checkInTimeParsed->between($checkInStart, $checkInEnd->copy()->addMinutes($cit->late_duration))) {
                    $validatedData['check_in_status_id'] = $cit->id;
                    $isInsideCheckInTimeRange = true;
                    break;
                }
            }

            if (!$isInsideCheckInTimeRange) {
                return response()->json([
                    'status' => 'failed',
                    'message' => "Attendance's check in time is outside attendance window range",
                ], 422);
            }
        }

        return null;
    }

    public function markAbsentStudents(Request $request)
    {
        $request->validate([
            'attendance_window_ids' => 'required|array|min:1|exists:attendance_windows,id'
        ]);

        $absenceCheckInStatusId = CheckInStatus::where('late_duration', -1)->first()->id;
        $absenceCheckOutStatusId = CheckOutStatus::where('late_duration', -1)->first()->id;
        $validAttendanceWindowIds = AttendanceWindow::whereIn('id', $request->attendance_window_ids)
            ->where('type', '!=', 'holiday')
            ->pluck('id')
            ->toArray();

        foreach ($validAttendanceWindowIds as $attendanceWindowId) {

            $existingAttendance = Attendance::where("attendance_window_id", $attendanceWindowId)
                ->pluck('student_id')
                ->toArray();

            $studentIds = Student::pluck('id')->toArray();

            $missingStudentIds = array_diff($studentIds, $existingAttendance);

            $absentRecords = [];
            foreach ($missingStudentIds as $studentId) {
                $absentRecords[] = [
                    'school_id' => config('school.id'),
                    'student_id' => $studentId,
                    'attendance_window_id' => $attendanceWindowId,
                    'check_in_status_id' => $absenceCheckInStatusId,
                    'check_out_status_id' => $absenceCheckOutStatusId,
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            }

            if (!empty($absentRecords)) {
                Attendance::insert($absentRecords);
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Absent students marked successfully'
        ]);

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

    public function clearAttendanceRecords($attendanceWindowId)
    {
        $deletedCount = Attendance::where('attendance_window_id', $attendanceWindowId)->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Attendance deleted successfully',
            'deleted_records' => $deletedCount
        ]);
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

    public function destroy($id)
    {
        $attendance = Attendance::findOrFail($id);
        $attendance->delete();
        return response()->json([
            'status' => 'success',
            'message' => 'Attendance deleted successfully'
        ]);
    }
}
