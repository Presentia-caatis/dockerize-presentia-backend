<?php

namespace App\Http\Controllers;

use App\Filterable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;

use App\Models\CheckInStatus;
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

        $query = $this->applyFilters(CheckInStatus::query(),  $request->input('filter', []), ['school']);

        $data = $query->orderBy('late_duration')->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'message' => 'Check in statuses retrieved successfully',
            'data' => $data
        ]);
    }


    public function store(Request $request)
    {
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
            'message' => 'Check in status created successfully',
            'data' => $data
        ]);
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
            'adjust_attendance' => 'nullable|boolean',
            'startDate' => 'required_with:adjust_attendance|date_format:Y-m-d',
            'endDate' => 'required_with:adjust_attendance|date_format:Y-m-d',
            'attendance_window_id' => 'required_with:adjust_attendance|'
        ]);

        if (isset($validatedData['late_duration']) && ($checkInStatus->late_duration == 0 || $checkInStatus->late_duration == -1)) {
            abort(403, 'You are not allowed to update late_duration column for this id');
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
            abort(403, 'You are not allowed to delete check in status from this id');
        }
        
        $checkInStatus->delete();
        return response()->json([
            'status' => 'success',
            'message' => 'Check in status deleted successfully'
        ]);
    }
}
