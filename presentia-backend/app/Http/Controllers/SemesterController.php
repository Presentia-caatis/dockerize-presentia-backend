<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SemesterService;
use Illuminate\Validation\ValidationException;

class SemesterController extends Controller
{
    protected $semesterService;

    public function __construct(SemesterService $semesterService)
    {
        $this->semesterService = $semesterService;
    }

    public function index(Request $request)
    {
        $validatedData = $request->validate([
            'perPage' => 'sometimes|integer|min:1'
        ]);

        $semesters = $this->semesterService->getAll($validatedData);

        return response()->json([
            'status' => 'success',
            'data' => $semesters,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'school_id'      => 'required|integer|exists:schools,id',
            'academic_year'  => 'required|string',
            'period'         => 'required|string',
            'start_date'     => 'required|date',
            'end_date'       => 'required|date|after_or_equal:start_date',
            'is_active'      => 'boolean',
        ]);

        try {
            $semester = $this->semesterService->create($validated);
            return response()->json([
                'status' => 'success',
                'data' => $semester,
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        }
    }

    public function show($id)
    {
        $semester = $this->semesterService->getById($id);
        return response()->json([
            'status' => 'success',
            'data' => $semester,
        ]);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'school_id'      => 'sometimes|required|integer|exists:schools,id',
            'academic_year'  => 'sometimes|required|string',
            'period'         => 'sometimes|required|string',
            'start_date'     => 'sometimes|required|date',
            'end_date'       => 'sometimes|required|date|after_or_equal:start_date',
            'is_active'      => 'boolean',
        ]);

        try {
            $semester = $this->semesterService->update($id, $validated);
            return response()->json([
                'status' => 'success',
                'data' => $semester,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        }
    }

    public function destroy($id)
    {
        $this->semesterService->delete($id);
        return response()->json([
            'status' => 'success',
            'message' => 'Semester deleted successfully',
        ]);
    }

    public function isActiveToogle($id){
        $semester = $this->semesterService->isActiveToogle($id);
        return response()->json([
            'status' => 'success',
            'message' => 'Semester is updated successfully',
            'data' => $semester,
        ]);
    }
}
