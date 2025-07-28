<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\EnrollmentService;
use function App\Helpers\current_school_id;
use function App\Helpers\current_semester_id;

class EnrollmentController extends Controller
{
    protected $enrollmentService;

    public function __construct(EnrollmentService $enrollmentService)
    {
        $this->enrollmentService = $enrollmentService;
    }

    public function getAll(Request $request)
    {
        $validatedDataData = $request->validate([
            'perPage' => 'sometimes|integer|min:1'
        ]);

        $enrollments = $this->enrollmentService->getAll($validatedDataData);

        return response()->json([
            'status' => 'success',
            'message' => 'Enrollments retrieved successfully',
            'data' => $enrollments,
        ]);
    }

    public function getById($id)
    {
        $enrollment = $this->enrollmentService->getById($id);
        return response()->json([
            'status' => 'success',
            'message' => 'Enrollment retrieved successfully',
            'data' => $enrollment,
        ]);
    }
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'class_group_id' => 'required|integer|exists:class_groups,id',
            'student_id' => 'required|integer|exists:students,id',
        ]);

        $validatedData['school_id'] = current_school_id();
        $validatedData['semester_id'] = current_semester_id();

        $enrollment = $this->enrollmentService->create($validatedData);

        return response()->json([
            'status' => 'success',
            'message' => 'Enrollment created successfully',
            'data' => $enrollment,
        ], 201);
    }

    public function storeViaFile(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls',
        ]);

        [$result, $statusCode] = $this->enrollmentService->createFromFile($request->file('file'));

        return response()->json($result, $statusCode);
    }

    public function update(Request $request, $id)
    {
        $validatedData = $request->validate([
            'semester_id' => 'sometimes|required|integer|exists:semesters,id',
            'class_group_id' => 'sometimes|required|integer|exists:class_groups,id',
            'student_id' => 'sometimes|required|integer|exists:students,id',
        ]);


        $enrollment = $this->enrollmentService->update($id, $validatedData);

        return response()->json([
            'status' => 'success',
            'message' => 'Enrollment updated successfully',
            'data' => $enrollment,
        ]);
    }

    public function destroy($id)
    {
        $this->enrollmentService->delete($id);
        return response()->json([
            'status' => 'success',
            'message' => 'Enrollment deleted successfully',
        ]);
    }
}
