<?php

namespace App\Http\Controllers;

use App\Filterable;
use App\Models\Scopes\SemesterScope;
use App\Models\Semester;
use App\Services\EnrollmentService;
use App\Sortable;
use DB;
use Illuminate\Http\Request;

use App\Models\Student;
use function App\Helpers\current_school_id;
use function App\Helpers\current_semester_id;

class StudentController extends Controller
{
    use Filterable, Sortable;

    public function getAll(Request $request)
    {
        $validatedData = $request->validate([
            'perPage' => 'sometimes|integer|min:1',
            'unfilteredSemester' => 'sometimes|boolean',
            'onlyWithoutEnrollment' => 'sometimes|boolean'
        ]);

        $perPage = $validatedData['perPage'] ?? 10;
        $query = Student::query();

        if ($validatedData['unfilteredSemester'] ?? false) {
            $query->withoutGlobalScope(SemesterScope::class);
        } else {
            $query->with([
                'enrollments' => function ($q) {
                    $q->select('id', 'student_id', 'semester_id', 'class_group_id')
                        ->with('classGroup:id,school_id,class_name,created_at,updated_at');
                }
            ]);
        }

        if (($validatedData['onlyWithoutEnrollment'] ?? false)) {
            $query->whereDoesntHave('enrollments');
        }

        $query = $this->applyFilters($query, $request->input('filter', []), ['school_id']);
        $query = $this->applySort($query, $request->input('sort', []), ['school_id']);

        $data = $query->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'message' => 'Students retrieved successfully',
            'data' => $data
        ]);
    }


    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'is_active' => 'nullable|boolean',
            'nis' => 'required|string',
            'nisn' => 'required|string',
            'class_group_id' => 'required|integer|exists:class_groups,id',
            'student_name' => 'required|string',
            'gender' => 'required|in:male,female',
        ]);

        $validatedData['school_id'] = current_school_id();
        DB::beginTransaction();
        try {
            $studentValidatedData = $validatedData;
            unset($studentValidatedData['class_group_id']);
            $data = Student::create($studentValidatedData);

            if(isset($validatedData['class_group_id'])){
                (new EnrollmentService())->create([
                    'class_group_id' => $validatedData['class_group_id'],
                    'student_id' => $data->id,
                    'school_id' => $validatedData['school_id'],
                    'semester_id' => current_semester_id()
                ]);
            }
            $data->load('classGroups');
            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Student created successfully',
                'data' => $data
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create student',
                'error' => $e->getMessage(),
            ], 500);
        }
        
       
    }

    public function exportStudents()
    {
        $students = Student::orderBy('id')->get(['id', 'student_name']);

        $output = "PIN,Name\n";

        foreach ($students as $student) {
            $name = '"' . str_replace('"', '""', $student->student_name) . '"';
            $output .= "{$student->id},$name\n";
        }

        return response($output, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="students.csv"',
        ]);
    }

    public function getById($id)
    {
        $student = Student::withoutGlobalScope(SemesterScope::class)
            ->with([
                'classGroups' => function ($q) {
                    $q->withoutGlobalScope(SemesterScope::class)
                        ->withPivot('semester_id');
                }
            ])
            ->findOrFail($id);

        $data = $student->toArray();

        $semesterIds = collect($data['class_groups'])->pluck('pivot.semester_id')->unique()->filter();

        $semesters = Semester::whereIn('id', $semesterIds)->get()->keyBy('id');

        foreach ($data['class_groups'] as &$cg) {
            $semesterId = $cg['pivot']['semester_id'] ?? null;
            $cg['semester'] = $semesterId ? $semesters[$semesterId] ?? null : null;
            unset($cg['pivot']);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Student retrieved successfully',
            'data' => $data
        ]);
    }

    public function update(Request $request, $id)
    {
        $student = Student::withoutGlobalScope(SemesterScope::class)
            ->findOrFail($id);

        $validatedData = $request->validate([
            'is_active' => 'nullable|boolean',
            'nis' => 'nullable|string',
            'nisn' => 'nullable|string',
            'student_name' => 'nullable|string',
            'gender' => 'nullable|in:male,female',
            'class_group_id' => 'required|integer|exists:class_groups,id',
        ]);

        DB::beginTransaction();

        try {
            $studentValidatedData = $validatedData;
            unset($studentValidatedData['class_group_id']);
            $student->update($studentValidatedData);
            if( isset($validatedData['class_group_id'])){
                $enrollmentService = new EnrollmentService();
                if($student->enrollments()->doesntExist()){
                    $enrollmentService->create([
                        'class_group_id' => $validatedData['class_group_id'],
                        'student_id' => $student->id,
                        'school_id' => current_school_id(),
                        'semester_id' => current_semester_id()
                    ]);
                }else {
                    $enrollmentService->update($student->enrollments()->first()->id, [
                        'class_group_id' => $validatedData['class_group_id'],
                    ]);
                }
            }
            $student->load('classGroups');
            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Student updated successfully',
                'data' => $student
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update student',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        $student = Student::withoutGlobalScope(SemesterScope::class)
            ->findOrFail($id);
        $student->delete();
        return response()->json([
            'status' => 'success',
            'message' => 'Student deleted successfully'
        ]);
    }
}
