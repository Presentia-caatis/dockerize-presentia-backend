<?php

namespace App\Http\Controllers;

use App\Filterable;
use App\Http\Requests\ExcelFileRequest;
use App\Jobs\ImportStudentJob;
use App\Models\ClassGroup;
use Illuminate\Http\Request;

use App\Models\Student;
use Maatwebsite\Excel\Facades\Excel;

class StudentController extends Controller
{
    use Filterable;
    
    public function index(Request $request)
    {
        $validatedData = $request->validate([
            'perPage' => 'sometimes|integer|min:1',
            'class_group_id' => 'sometimes|exists:class_groups,id',
            'search' => 'nullable|string|min:1'
        ]);

        $perPage = $validatedData['perPage'] ?? 10;
        $search = $validatedData['search'] ?? null;
        $query = Student::with('classGroup');

        $query = $this->applyFilters($query,  $request->input('filter', []), ['school_id']);


        if ($request->has('class_group_id')) {
            $query->where('class_group_id', $request->class_group_id);
        }

        if ($search) {
            $query->where('student_name', 'like', "%$search%");
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
            'class_group_id' => 'nullable|exists:class_groups,id',
            'is_active' => 'nullable|boolean',
            'nis' => 'required|string',
            'nisn' => 'required|string',
            'student_name' => 'required|string',
            'gender' => 'required|in:male,female',
        ]);


        $data = Student::create($validatedData);
        $data->load(['classGroup']);
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
                    if (count($row) < 5 || empty($row[0]) || empty($row[1]) || empty($row[2]) || empty($row[3]) || empty($row[4])) {
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
                ImportStudentJob::dispatch($students, $schoolId)->onQueue('import_student');;
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
        $student = Student::findOrFail($id);
        $student->load(['classGroup', 'school']);
        return response()->json([
            'status' => 'success',
            'message' => 'Student retrieved successfully',
            'data' => $student
        ]);
    }

    public function update(Request $request, $id)
    {
        $student = Student::findOrFail($id);
        $validatedData = $request->validate([
            'class_group_id' => 'nullable|exists:class_groups,id',
            'is_active' => 'nullable|boolean',
            'nis' => 'nullable|string',
            'nisn' => 'nullable|string',
            'student_name' => 'nullable|string',
            'gender' => 'nullable|in:male,female',
        ]);

        $student->update($validatedData);
        $student->load(['classGroup']);

        return response()->json([
            'status' => 'success',
            'message' => 'Student updated successfully',
            'data' => $student
        ]);
    }

    public function destroy($id)
    {
        $student = Student::findOrFail($id);
        $student->delete();
        return response()->json([
            'status' => 'success',
            'message' => 'Student deleted successfully'
        ]);
    }
}
