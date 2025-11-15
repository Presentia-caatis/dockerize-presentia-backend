<?php

namespace App\Http\Controllers;

use App\Models\Semester;
use Illuminate\Http\Request;
use App\Services\SemesterService;
use function App\Helpers\current_school_id;
use function App\Helpers\current_school_timezone;

class SemesterController extends Controller
{
    protected $semesterService;

    public function __construct(SemesterService $semesterService)
    {
        $this->semesterService = $semesterService;
    }

    public function getAll(Request $request)
    {
        $validatedData = $request->validate([
            'perPage' => 'sometimes|integer|min:1'
        ]);

        $semesters = $this->semesterService->getAll($validatedData);

        return response()->json([
            'status' => 'success',
            'message' => 'Semesters retrieved successfully',
            'data' => $semesters,
        ]);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'academic_year' => ['required', 'string', 'regex:/^\d{4}\/\d{4}$/'],
            'period' => 'required|string|in:odd,even',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $migrationConfiguration = $request->validate([
            'copy_all_check_in_status' => 'boolean',
            'copy_all_check_out_status' => 'boolean',
            'copy_all_absence_permit_type' => 'boolean'
        ]);

        $now = now()->timezone(current_school_timezone())->toDateString();

        $semester = Semester::where('start_date', '<=', $now)
            ->where('end_date', '>=', $now)
            ->where('is_active', true)
            ->first();

        if ($semester) {
            $validatedData["is_active"] = false;
        }

        $semester = $this->semesterService->create($validatedData, $migrationConfiguration);
        return response()->json([
            'status' => 'success',
            'message' => 'Semester created successfully',
            'data' => $semester,
        ], 201);
    }

    public function getByCurrentTime()
    {
        $semester = $this->semesterService->getByCurrentTime();
        if (!$semester) {
            abort(422, "There is no active semester in current date");
        }
        return response()->json([
            'status' => 'success',
            'message' => 'Current semester retrieved successfully',
            'data' => $semester,
        ]);
    }

    public function getById($id)
    {
        $semester = $this->semesterService->getById($id);
        return response()->json([
            'status' => 'success',
            'message' => 'Semester retrieved successfully',
            'data' => $semester,
        ]);
    }

    public function update(Request $request, $id)
    {
        $validatedData = $request->validate([
            'academic_year' => ['sometimes', 'string', 'regex:/^\d{4}\/\d{4}$/'],
            'period' => 'sometimes|required|string|in:odd,even',
            'start_date' => 'sometimes|required|date|date_format:Y-m-d',
            'end_date' => 'sometimes|required|date|date_format:Y-m-d|after_or_equal:start_date',
            'is_active' => 'boolean',
        ]);

        $semester = $this->semesterService->update($id, $validatedData);
        return response()->json([
            'status' => 'success',
            'message' => 'Semester updated successfully',
            'data' => $semester,
        ]);
    }

    public function destroy($id)
    {
        $this->semesterService->delete($id);
        return response()->json([
            'status' => 'success',
            'message' => 'Semester deleted successfully',
        ]);
    }

    public function isActiveToogle($id)
    {
        $semester = $this->semesterService->isActiveToogle($id);
        return response()->json([
            'status' => 'success',
            'message' => 'Semester active status toggled successfully',
            'data' => $semester,
        ]);
    }
}
