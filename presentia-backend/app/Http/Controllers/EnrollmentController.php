<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\EnrollmentService;

class EnrollmentController extends Controller
{
    protected $enrollmentService;

    public function __construct(EnrollmentService $enrollmentService)
    {
        $this->enrollmentService = $enrollmentService;
    }

    // GET /enrollments
    public function getAll()
    {
        $enrollments = $this->enrollmentService->getAll();
        return response()->json([
            'status' => 'success',
            'data' => $enrollments,
        ]);
    }

    // GET /enrollments/{id}
    public function getById($id)
    {
        $enrollment = $this->enrollmentService->getById($id);
        return response()->json([
            'status' => 'success',
            'data' => $enrollment,
        ]);
    }

    // POST /enrollments
    public function store(Request $request)
    {
        $validated = $request->validate([
            'semester_id'    => 'required|integer|exists:semesters,id',
            'class_group_id' => 'required|integer|exists:class_groups,id',
            'student_id'     => 'required|integer|exists:students,id',
            'school_id'      => 'required|integer|exists:schools,id',
        ]);

        $enrollment = $this->enrollmentService->create($validated);

        return response()->json([
            'status' => 'success',
            'data' => $enrollment,
        ], 201);
    }

    // PUT/PATCH /enrollments/{id}
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'semester_id'    => 'sometimes|required|integer|exists:semesters,id',
            'class_group_id' => 'sometimes|required|integer|exists:class_groups,id',
            'student_id'     => 'sometimes|required|integer|exists:students,id',
            'school_id'      => 'sometimes|required|integer|exists:schools,id',
        ]);

        $enrollment = $this->enrollmentService->update($id, $validated);

        return response()->json([
            'status' => 'success',
            'data' => $enrollment,
        ]);
    }

    // DELETE /enrollments/{id}
    public function destroy($id)
    {
        $this->enrollmentService->delete($id);
        return response()->json([
            'status' => 'success',
            'message' => 'Enrollment deleted successfully',
        ]);
    }
}
