<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SemesterService;
use function App\Helpers\current_school_id;

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
            'academic_year'  => ['required', 'string', 'regex:/^\d{4}\/\d{4}$/'],
            'period'         => 'required|string|in:odd,even',
            'start_date'     => 'required|date',
            'end_date'       => 'required|date|after_or_equal:start_date',
            'is_active'      => 'boolean',
        ]);

        $validatedData["school_id"] = current_school_id();

        $semester = $this->semesterService->create($validatedData);
        return response()->json([
            'status' => 'success',
            'message' => 'Semester created successfully',
            'data' => $semester,
        ], 201);
    }

    public function getByCurrentTime()
    {
        return response()->json([
            'status' => 'success',
            'message' => 'Current semester retrieved successfully',
            'data' => $this->semesterService->getByCurrentTime(),
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
            'academic_year'  => ['sometimes', 'string', 'regex:/^\d{4}\/\d{4}$/'],
            'period'         => 'sometimes|required|string|in:odd,even',
            'start_date'     => 'sometimes|required|date|date_format:Y-m-d',
            'end_date'       => 'sometimes|required|date|date_format:Y-m-d|after_or_equal:start_date',
            'is_active'      => 'boolean',
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
