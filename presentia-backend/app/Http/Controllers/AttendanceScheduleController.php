<?php

namespace App\Http\Controllers;

use App\Filterable;
use App\Models\AttendanceSchedule;
use App\Models\Event;
use App\Models\School;
use App\Services\AttendanceWindowLoaderService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AttendanceScheduleController extends Controller
{
    use Filterable;

    public function index(Request $request)
    {
        $validatedData = $request->validate([
            'perPage' => 'sometimes|integer|min:1'
        ]);

        $perPage = $validatedData['perPage'] ?? 10;

        $query = $this->applyFilters(AttendanceSchedule::query(), $request->input('filter', []), ['school_id']);

        $data = $query->paginate($perPage);

        $modifiedData = $data->getCollection()->map(function ($item) {
            if (!$item->event_id) {
                unset($item->event_id);
            }
            return $item;
        });

        $data->setCollection($modifiedData);

        return response()->json([
            'status' => 'success',
            'message' => 'Attendance schedule retrieved successfully',
            'data' => $data
        ]);
    }

    public function getById($id)
    {
        $attendanceSchedule = AttendanceSchedule::findOrFail($id);
        return response()->json([
            'status' => 'success',
            'message' => 'Attendance windows retrieved successfully',
            'data' => $attendanceSchedule
        ]);
    }


    public function update(Request $request, $id)
    {
        $attendanceSchedule = AttendanceSchedule::findOrFail($id);
        $validatedData = $request->validate([
            'event_id' => 'sometimes|exists:events,id',
            'name' => 'sometimes|string',
            'check_in_start_time' => 'sometimes|date_format:H:i:s',
            'check_in_end_time' => 'sometimes|date_format:H:i:s',
            'check_out_start_time' => 'sometimes|date_format:H:i:s',
            'check_out_end_time' => 'required|date_format:H:i:s'
        ]);

        $attendanceSchedule = AttendanceSchedule::findOrFail($id);
        $attendanceSchedule->update($validatedData);

        return response()->json([
            'status' => 'success',
            'message' => 'Attendance schedule updated successfully',
            'data' => $attendanceSchedule
        ]);
    }

    public function destroy($id)
    {
        $attendanceSchedule = AttendanceSchedule::findOrFail($id);
        if ($attendanceSchedule->type === 'holiday' || $attendanceSchedule->type === 'default') {
            abort(403, 'Cannot delete attendance schedule of type "holiday" or "default".');
        }

        $attendanceSchedule->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Attendance schedule deleted successfully'
        ]);
    }
}
