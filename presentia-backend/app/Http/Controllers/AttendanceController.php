<?php

namespace App\Http\Controllers;

use App\Exports\AttendanceExport;
use App\Filterable;
use App\Jobs\AdjustAttendanceJob;
use App\Jobs\StoreAttendanceJob;
use App\Models\CheckInStatus;
use App\Models\AttendanceWindow;
use App\Models\CheckOutStatus;
use App\Models\ClassGroup;
use App\Models\Event;
use App\Models\Student;
use App\Sortable;
use Carbon\Carbon;
use Illuminate\Http\Request;

use App\Models\Attendance;
use function App\Helpers\current_school_id;
use function App\Helpers\current_school_timezone;
use function App\Helpers\stringify_convert_timezone_to_utc;


class AttendanceController extends Controller
{
    use Filterable, Sortable;

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
            'isExcludeCheckInAbsentStudent' => 'nullable|boolean'
        ]);


        $perPage = $validatedData['perPage'] ?? 10;

        $simplify = $validatedData['simplify'] ?? false;
        $isExcludeCheckInAbsentStudent = $validatedData['isExcludeCheckInAbsentStudent'] ?? false;

        if ($simplify) {
            $query = Attendance::with([
                'student:id,student_name,nis,nisn,gender,class_group_id',
                'student.classGroup:id,class_name',
                'checkInStatus:id,status_name',
                'attendanceWindow:id,date',
                'absencePermit'
            ])->select([
                'attendances.id',
                'attendances.student_id',
                'attendances.check_in_status_id',
                'attendances.attendance_window_id',
                'attendances.check_in_time',
                'attendances.check_out_time'
            ]);
        } else {
            $query = Attendance::with('student', 'checkInStatus', 'student.classGroup', 'attendanceWindow', 'absencePermit.absencePermitType');
        }

        $query = $this->applyFilters($query, $request->input('filter', []), ['school_id']);
        $query = $this->applySort($query, $request->input('sort', []));

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

        if ($isExcludeCheckInAbsentStudent) {
            $query->where('check_in_status_id', '!=', CheckInStatus::where('late_duration', -1)->first()->id);
        }


        $data = $query->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'message' => 'Attendances retrieved successfully',
            'data' => $data
        ]);
    }

    public function adjustAttendance(Request $request)
    {

        $request->validate([
            'attendance_window_ids' => 'required|array|min:1',
            'attendance_window_ids.*' => 'exists:attendance_windows,id'
        ]);

        $attendanceWindowIds = AttendanceWindow::whereIn('id', $request->attendance_window_ids)->pluck('id')->toArray();
        $schoolId = current_school_id();

        AdjustAttendanceJob::dispatch($attendanceWindowIds, 0, $schoolId)->onQueue('adjust-attendance');

        return response()->json([
            'status' => 'success',
            'message' => 'Attendance adjustment has started'
        ], 201);
    }

    public function storeFromFile(Request $request)
    {
        $request->validate([
            'attendances' => 'file|required|mimes:json',
        ]);

        $json = file_get_contents($request->file('attendances'));
        $data = json_decode($json, true);

        $firstKey = array_key_first($data);
        $attendances = $data[$firstKey];

        $batches = array_chunk($attendances, 500);

        foreach ($batches as $attendanceBatch) {
            StoreAttendanceJob::dispatch($attendanceBatch)->onQueue('store-attendance');
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Attendance processing has started',
        ], 201);
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

        StoreAttendanceJob::dispatch($jsonInput)->onQueue('store-attendance');

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
            'check_in_time' => 'nullable|date_format:Y-m-d H:i:s',
            'check_out_time' => 'nullable|date_format:Y-m-d H:i:s',
            'check_out_status_id' => 'nullable|exists:check_out_statuses,id',
            'check_in_status_id' => 'nullable|exists:check_in_statuses,id',
        ]);

        $timeValidationResponse = $this->validateAttendanceTime($validatedData, $validatedData['attendance_window_id']);

        if ($timeValidationResponse) {
            abort(422, $timeValidationResponse);
        }

        $validatedData['school_id'] = current_school_id();

        $data = Attendance::create($validatedData);

        return response()->json([
            'status' => 'success',
            'message' => 'Attendance created successfully',
            'data' => $data
        ]);
    }

    public function storeManualAttendanceNisOnly(Request $request)
    {
        $request->validate([
            'nis' => 'required',
        ]);
        $studentId = Student::where('nis', $request->nis)->firstOrFail()?->id;

        $jsonFile = [
            [
                'id' => $studentId,
                'date' => now()
            ]
        ];

        $job = new StoreAttendanceJob($jsonFile);
        $job->handle();

        $job->response[$studentId]["nis"] = $request->nis;

        return response()->json([
            'status' => 'success',
            'message' => 'Attendance created successfully',
            'data' => $job->response
        ]);
    }

    public function update(Request $request, $id)
    {
        $attendance = Attendance::findOrFail($id);

        $validatedData = $request->validate([
            'attendance_window_id' => 'nullable|exists:attendance_windows,id',
            'absence_permit_id' => 'nullable|exists:absence_permits,id',
            'check_in_time' => 'nullable|date_format:Y-m-d H:i:s',
            'check_out_time' => 'nullable||date_format:Y-m-d H:i:s',
            'check_out_status_id' => 'nullable|exists:check_out_statuses,id',
            'check_in_status_id' => 'nullable|exists:check_in_statuses,id',
        ]);


        // Validate check-in and check-out time using the new function
        $timeValidationResponse = $this->validateAttendanceTime($validatedData, $attendance->attendance_window_id);

        if ($timeValidationResponse) {
            abort(422, $timeValidationResponse);
        }

        $attendance->update($validatedData);

        return response()->json([
            'status' => 'success',
            'message' => 'Attendance updated successfully',
            'data' => $attendance
        ]);
    }

    private function validateAttendanceTime(&$validatedData, $attendanceWindowId)
    {
        $checkOutTime = $validatedData['check_out_time'] ?? null;
        $checkInTime = $validatedData['check_in_time'] ?? null;
        $attendanceWindow = AttendanceWindow::findOrFail($attendanceWindowId);
        $checkInStatus = CheckInStatus::where('late_duration', '!=', -1)->orderBy('late_duration')->get();
        $checkInStart = Carbon::parse($attendanceWindow->date . ' ' . $attendanceWindow->check_in_start_time);
        $checkInEnd = Carbon::parse($attendanceWindow->date . ' ' . $attendanceWindow->check_in_end_time);
        $checkOutStart = Carbon::parse($attendanceWindow->date . ' ' . $attendanceWindow->check_out_start_time);
        $checkOutEnd = Carbon::parse($attendanceWindow->date . ' ' . $attendanceWindow->check_out_end_time);

        if ($checkOutTime && !isset($validatedData['check_out_status_id'])) {
            $checkOutTimeParsed = Carbon::parse($checkOutTime);
            if (!$checkOutTimeParsed->between($checkOutStart, $checkOutEnd)) {
                $validatedData['check_out_status_id'] = CheckOutStatus::where('late_duration', -1)->first()->id;
            } else {
                $validatedData['check_out_status_id'] = CheckOutStatus::where('late_duration', 0)->first()->id;
            }
        }

        if ($checkInTime && !isset($validatedData['check_in_status_id'])) {
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
                $validatedData['check_in_status_id'] = CheckInStatus::where('late_duration', -1)->first()->id;
            } else {
                $validatedData['absence_permit_id'] = null;
            }
        }

        return null;
    }

    public function markAbsentStudents(Request $request)
    {
        $request->validate([
            'attendance_window_ids' => 'required|array|min:1|exists:attendance_windows,id'
        ]);


        $absenceCheckOutStatusId = CheckOutStatus::where('late_duration', -1)->first()->id;
        $validAttendanceWindowIds = AttendanceWindow::whereIn('id', $request->attendance_window_ids)
            ->where('type', '!=', 'holiday')
            ->pluck('id')
            ->toArray();
        $absenceCheckInStatusId = CheckInStatus::where('late_duration', -1)->first()->id;

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
        set_time_limit(3600);
        ini_set('memory_limit', '1024M');

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
            'event' => [
                'sometimes',
                function ($attribute, $value, $fail) {
                    if ($value !== 'all') {
                        $ids = explode(',', $value);
                        $validIds = Event::whereIn('id', $ids)->pluck('id')->toArray();

                        if (array_diff($ids, $validIds)) {
                            $fail("The selected $attribute contains invalid event IDs.");
                        }
                    }
                },
            ],
            'isCheckOutStatusInluded' => 'sometimes|boolean',
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
        $attendance = Attendance::with(['student', 'checkInStatus', 'checkOutStatus', 'absencePermit', 'attendanceWindow'])->findOrFail($id);


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
