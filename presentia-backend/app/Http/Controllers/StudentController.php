<?php

namespace App\Http\Controllers;

use App\Filterable;
use App\Jobs\ImportStudentJob;
use App\Models\Scopes\SemesterScope;
use App\Models\Semester;
use App\Sortable;
use Illuminate\Http\Request;

use App\Models\Student;
use Maatwebsite\Excel\Facades\Excel;

class StudentController extends Controller
{
    use Filterable, Sortable;


    public function getAll(Request $request)
    {
        $validatedData = $request->validate([
            'perPage' => 'sometimes|integer|min:1',
            'unfilteredSemester' => 'sometimes|boolean'
        ]);

        $perPage = $validatedData['perPage'] ?? 10;
        $query = Student::query();

        $query = $this->applyFilters($query, $request->input('filter', []), ['school_id']);
        $query = $this->applySort($query, $request->input('sort', []), ['school_id']);

        if ($validatedData['unfilteredSemester'] ?? false) {
            $query->withoutGlobalScope(SemesterScope::class);
        }

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
            'school_id' => 'required|exists:schools,id',
            'is_active' => 'nullable|boolean',
            'nis' => 'required|string',
            'nisn' => 'required|string',
            'student_name' => 'required|string',
            'gender' => 'required|in:male,female',
        ]);


        $data = Student::create($validatedData);
        return response()->json([
            'status' => 'success',
            'message' => 'Student created successfully',
            'data' => $data
        ], 201);
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

    public function storeViaFile(Request $request)
    {
        set_time_limit(600);
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls',
        ]);

        $schoolId = config('school.id');
        $data = Excel::toArray([], $request->file('file'))[0];
        unset($data[0]); // Remove header row

        $chunks = array_chunk($data, 500);
        $totalRows = count($data);
        $successCount = 0;
        $failedCount = 0;
        $failedRows = [];
        $students = [];

        foreach ($chunks as $chunk) {
            foreach ($chunk as $row) {
                try {
                    // Basic validation
                    if (count($row) < 5 || empty($row[0]) || empty($row[1]) || empty($row[2]) || empty($row[3])) {
                        $failedCount++;
                        $failedRows[] = ['row' => $row, 'error' => 'Incomplete or missing data'];
                        continue;
                    }

                    // Convert gender
                    $gender = strtolower($row[3]) === 'l' ? 'male' : (strtolower($row[3]) === 'p' ? 'female' : null);
                    if (!$gender) {
                        $failedCount++;
                        $failedRows[] = ['row' => $row, 'error' => 'Invalid gender value'];
                        continue;
                    }

                    $students[] = [
                        'nisn' => $row[1],
                        'school_id' => $schoolId,
                        'nis' => $row[0],
                        'student_name' => $row[2],
                        'gender' => $gender,
                        'is_active' => true,
                        'class_group_name' => $row[4],
                        'updated_at' => now(),
                        'created_at' => now(),
                    ];

                    $successCount++;
                } catch (\Exception $e) {
                    $failedCount++;
                    $failedRows[] = ['row' => $row, 'error' => $e->getMessage()];
                }
            }
            if (!empty($students)) {
                ImportStudentJob::dispatch($students, $schoolId)->onQueue('import-student');
                ;
            }
        }

        return response()->json([
            'status' => 'processing',
            'message' => 'Student import has started and is being processed in the background.',
            'total_records' => $totalRows,
            'queued_records' => $successCount,
            'skipped_records' => $failedCount,
            'skipped_details' => $failedRows,
        ], 202);
    }

    public function getById($id)
    {
        $student = Student::withoutGlobalScope(SemesterScope::class)
            ->with([
                'classGroups' => function ($q) {
                    $q->withPivot('semester_id');
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
        ]);

        $student->update($validatedData);

        return response()->json([
            'status' => 'success',
            'message' => 'Student updated successfully',
            'data' => $student
        ]);
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
