<?php

namespace App\Http\Controllers;

use App\Filterable;
use App\Models\Attendance;
use App\Models\AttendanceSchedule;
use App\Models\AttendanceWindow;
use App\Models\CheckInStatus;
use App\Models\CheckOutStatus;
use App\Models\Day;
use App\Models\Event;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;
use function App\Helpers\current_school_timezone;

class AttendanceWindowController extends Controller
{
    use Filterable;
    public function index(Request $request)
    {
        $validatedData = $request->validate([
            'perPage' => 'sometimes|integer|min:1'
        ]);

        $perPage = $validatedData['perPage'] ?? 10;

        $query = $this->applyFilters(AttendanceWindow::query(), $request->input('filter', []), ['school_id']);

        $data = $query->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'message' => 'Attendance windows retrieved successfully',
            'data' => $data
        ]);
    }
    public function getAllInUtcFormat()
    {
        $currentSchoolTimezone = current_school_timezone();
        $data = AttendanceWindow::all()->map(function ($record) use ($currentSchoolTimezone) {
            return [
                'id' => $record->id,
                'day_id' => $record->day_id,
                'name' => $record->name,
                'school_id' => $record->school_id,
                'total_present' => $record->total_present,
                'total_absent' => $record->total_absent,
                'type' => $record->type,
                'check_in_start_time' => Carbon::parse($record->date . ' ' . $record->check_in_start_time, $currentSchoolTimezone)->utc()->toDateTimeString(),
                'check_in_end_time' => Carbon::parse($record->date . ' ' . $record->check_in_end_time, $currentSchoolTimezone)->utc()->toDateTimeString(),
                'check_out_start_time' => Carbon::parse($record->date . ' ' . $record->check_out_start_time, $currentSchoolTimezone)->utc()->toDateTimeString(),
                'check_out_end_time' => Carbon::parse($record->date . ' ' . $record->check_out_end_time, $currentSchoolTimezone)->utc()->toDateTimeString(),
            ];
        });
        return response()->json([
            'status' => 'success',
            'message' => 'Attendance windows retrieved successfully',
            'data' => $data
        ]);
    }


    public function generateWindow(Request $request)
    {
        $request->validate([
            'date' => 'required|date_format:Y-m-d',
        ]);

        $day = strtolower(Carbon::parse($request->date)->format('l'));

        $dayData = Day::where('name', $day)
            ->first();

        $dataSchedule = $dayData->attendanceSchedule;

        $attendanceWindow = AttendanceWindow::create([
            'day_id' => $dayData->id,
            'name' => $dataSchedule->name . ' ' . Carbon::parse($request->date)->format('d-m-Y'),
            'school_id' => $dayData->school_id,
            'total_present' => 0,
            'total_absent' => 0,
            'date' => $request->date,
            'type' => $dataSchedule->type,
            'check_in_start_time' => $dataSchedule->check_in_start_time,
            'check_in_end_time' => $dataSchedule->check_in_end_time,
            'check_out_start_time' => $dataSchedule->check_out_start_time,
            'check_out_end_time' => $dataSchedule->check_out_end_time
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Attendance window generated successfully',
            'data' => $attendanceWindow
        ]);
    }


    public function getById($id)
    {
        $attendanceWindow = AttendanceWindow::findOrFail($id)->toArray();

        $checkInStatuses = CheckInStatus::orderBy('late_duration')->pluck('id', 'status_name')->toArray();

        $checkInStatusesData = Attendance::where('attendance_window_id', $id)
            ->selectRaw('check_in_status_id, COUNT(*) as total')
            ->groupBy('check_in_status_id')
            ->pluck('total', 'check_in_status_id')
            ->toArray();

        $checkOutStatuses = CheckOutStatus::orderBy('late_duration')->pluck('id', 'status_name')->toArray();

        $checkOutStatusesData = Attendance::where('attendance_window_id', $id)
            ->selectRaw('check_out_status_id, COUNT(*) as total')
            ->groupBy('check_out_status_id')
            ->pluck('total', 'check_out_status_id')
            ->toArray();

        $totalAll = 0;

        $attendanceWindow["check_in_status"] = [];
        foreach ($checkInStatuses as $name => $statusId) {
            $attendanceWindow["check_in_status"][$name] = $checkInStatusesData[$statusId] ?? 0;
            $totalAll += $attendanceWindow['check_in_status'][$name];
        }

        $attendanceWindow["check_out_status"] = [];
        foreach ($checkOutStatuses as $name => $statusId) {
            $attendanceWindow["check_out_status"][$name] = $checkOutStatusesData[$statusId] ?? 0;
        }

        
        $attendanceWindow["total_all"] = $totalAll;

        return response()->json([
            'status' => 'success',
            'message' => 'Attendance window retrieved successfully',
            'data' => $attendanceWindow
        ]);
    }

    public function update(Request $request, $id)
    {
        $attendanceWindow = AttendanceWindow::findOrFail($id);

        $validatedData = $validatedData = $request->validate([
            'name' => 'nullable|string',
            'date' => 'nullable|date_format:Y-m-d',
            'check_in_start_time' => 'required_with:check_in_end_time|date_format:H:i:s',
            'check_in_end_time' => 'required_with:check_in_start_time|date_format:H:i:s|after:check_in_start_time',
            'check_out_start_time' => 'required_with:check_out_end_time|date_format:H:i:s|after:check_in_end_time',
            'check_out_end_time' => 'required_with:check_out_start_time|date_format:H:i:s|after:check_out_start_time',
        ]);

        $attendanceWindow->update($validatedData);

        return response()->json([
            'status' => 'success',
            'message' => 'Attendance window updated successfully',
            'data' => $attendanceWindow
        ]);
    }

    public function destroy($id)
    {
        $attendanceWindow = AttendanceWindow::findOrFail($id);
        $attendanceWindow->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Attendance window deleted successfully'
        ]);
    }
}
