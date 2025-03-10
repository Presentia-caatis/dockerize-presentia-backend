<?php

namespace App\Http\Controllers;

use App\Filterable;
use App\Models\AttendanceWindow;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;

use App\Models\CheckInStatus;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;

class CheckInStatusController extends Controller
{
    use Filterable;
    public function index(Request $request)
    {
        $validatedData = $request->validate([
            'perPage' => 'sometimes|integer|min:1'
        ]);

        $perPage = $validatedData['perPage'] ?? 10;

        $query = $this->applyFilters(CheckInStatus::query(),  $request->input('filter', []), ['school_id']);

        $data = $query->orderBy('late_duration')->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'message' => 'Check in statuses retrieved successfully',
            'data' => $data
        ]);
    }


    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'status_name' => 'required|string',
                'description' => 'required|string',
                'late_duration' => 'required|integer'
            ]);

            $validatedData['is_active'] = true;
            $validatedData['school_id'] = config('school.id');

            $data = CheckInStatus::create($validatedData);

            return response()->json([
                'status' => 'success',
                'message' => 'Status check-in berhasil dibuat',
                'data' => $data
            ]);
        } catch (QueryException $e) {
            if ($e->getCode() == 23000) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Durasi keterlambatan tidak boleh sama dengan status yang lain'
                ], 400);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat menyimpan status check-in',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function getById($id)
    {
        $checkInStatus = CheckInStatus::findOrFail($id);
        return response()->json([
            'status' => 'success',
            'message' => 'Check in status retrieved successfully',
            'data' => $checkInStatus
        ]);
    }

    public function update(Request $request, $id)
    {
        $checkInStatus = CheckInStatus::findOrFail($id);

        $validatedData = $request->validate([
            'status_name' => 'string',
            'description' => 'string',
            'is_active' => 'boolean',
            'late_duration' => 'integer',
            'adjust_attendance' => 'required|boolean',
            'start_date' => 'required_with:end_date|date_format:Y-m-d',
            'end_date' => 'required_with:start_date|date_format:Y-m-d',
            'attendance_window_ids' => 'sometimes|array|min:1',
            'attendance_window_ids.*' => 'exists:attendance_windows,id'
        ]);

        $attendanceWindows = AttendanceWindow::query();

        if($request->boolean("adjust_attendance")){
            if(isset($validatedData['start_date'])){
                $attendanceWindows->whereBetween('date', [$validatedData['start_date'], $validatedData['end_date']]);
            } else if (isset($validatedData['attendance_window_ids'])){
                $attendanceWindows->whereIn('id', $validatedData['attendance_window_ids']);
            } else {
                throw ValidationException::withMessages([
                    'start_date' => [
                        "The start_date field is required when adjust_attendance is true and attendance_window_ids is not provided."
                    ],
                    'end_date' => [
                        "The end_date field is required when adjust_attendance is true and attendance_window_ids is not provided."
                    ], 
                    'attendance_window_ids' => [
                        "The attendance_window_ids field is required when adjust_attendance is true and startDate & endDate are missing."
                    ]
                ]);
            }
        }

        if (
            $request->has('late_duration') &&
            ($checkInStatus->late_duration == 0 || $checkInStatus->late_duration == -1) &&
            $request->late_duration != $checkInStatus->late_duration
        ) {
            abort(403, 'Durasi keterlambatan tidak boleh diubah');
        }

        if ($checkInStatus->late_duration == 0 || $checkInStatus->late_duration == -1) {
            $validatedData = $request->only(['status_name', 'description']);
        }

        $checkInStatus->update($validatedData);

        return response()->json([
            'status' => 'success',
            'message' => 'Check in status updated successfully',
            'data' => $checkInStatus
        ]);
    }

    public function destroy($id)
    {
        $checkInStatus = CheckInStatus::findOrFail($id);

        if ($checkInStatus->late_duration == 0 || $checkInStatus->late_duration == -1) {
            return response()->json([
                'status' => 'error',
                'message' => 'Status presensi ini tidak boleh dihapus'
            ], 403);
        }

        $checkInStatus->delete();
        return response()->json([
            'status' => 'success',
            'message' => 'Check in status deleted successfully'
        ]);
    }
}
