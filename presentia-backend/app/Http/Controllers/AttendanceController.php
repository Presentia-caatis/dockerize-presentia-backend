<?php

namespace App\Http\Controllers;

use App\Exports\AttendanceExport;
use App\Jobs\StoreAttendanceJob;
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

        if (!$attendance) {
            return response()->json([
                'status' => 'error',
                'message' => 'Attendance not found'
            ], 404);
        }

        $validatedData = $request->validate([
            'check_in_time' => 'nullable|date',
            'check_out_time' => 'nullable|date',
            'check_in_status_id' => 'nullable|exists:check_in_statuses,id',
        ]);


        if (!empty($validatedData['check_in_time'])) {
            $validatedData['check_in_time'] = Carbon::parse($validatedData['check_in_time'])
                ->format('Y-m-d H:i:s');
        }

        if (!empty($validatedData['check_out_time'])) {
            $validatedData['check_out_time'] = Carbon::parse($validatedData['check_out_time'])
                ->format('Y-m-d H:i:s');
        }

        $attendance->update($validatedData);

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
