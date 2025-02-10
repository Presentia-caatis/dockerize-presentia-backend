<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExcelFileRequest;
use App\Models\ClassGroup;
use Illuminate\Http\Request;

use App\Models\Student;
use Maatwebsite\Excel\Facades\Excel;

class StudentController extends Controller
{
    public function index()
    {

        $data = Student::with('classGroup')->get();
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
        $data->load(['classGroup', 'school']);
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
        $request->validate([
            'school_id' => 'required|exists:schools,id',
            'file' => 'required|file|mimes:xlsx,xls',
        ]);
    
        $schoolId = $request->school_id;
        $data = Excel::toArray([], $request->file('file'))[0];
        unset($data[0]); // Remove header row
    
        $chunks = array_chunk($data, 100);
        $totalRows = count($data);
        $successCount = 0;
        $failedCount = 0;
        $failedRows = [];
        $students = [];
    
        // Cache existing class groups to avoid unnecessary queries
        $existingClassGroups = ClassGroup::where('school_id', $schoolId)
            ->pluck('id', 'class_name') // ['class_name' => id]
            ->toArray();
    
        foreach ($chunks as $chunk) {
            foreach ($chunk as $row) {
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
    
                try {
                    // Check if class group exists in cache, otherwise create it
                    if (!isset($existingClassGroups[$row[4]])) {
                        $classGroup = ClassGroup::create([
                            'school_id' => $schoolId,
                            'class_name' => $row[4],
                            'amount_of_students' => 0,
                        ]);
    
                        // Update cache
                        $existingClassGroups[$row[4]] = $classGroup->id;
                    }
    
                    $students[] = [
                        'nisn' => $row[1],
                        'school_id' => $schoolId,
                        'class_group_id' => $existingClassGroups[$row[4]],
                        'nis' => $row[0],
                        'student_name' => $row[2],
                        'gender' => $gender,
                        'is_active' => true,
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
                Student::upsert($students, ['nisn'], ['school_id', 'class_group_id', 'nis', 'student_name', 'gender', 'is_active']);
            }
        }
    
        return response()->json([
            'status' => 'success',
            'message' => 'Students created successfully',
            'total_rows' => $totalRows,
            'success_count' => $successCount,
            'failed_count' => $failedCount,
            'failed_rows' => $failedRows,
        ], 201);
    }

    public function getById($id)
    {
        $student = Student::find($id);
        $student->load(['classGroup', 'school']);
        return response()->json([
            'status' => 'success',
            'message' => 'Student retrieved successfully',
            'data' => $student
        ]);

    }

    public function update(Request $request, $id)
    {
        $student = Student::find($id);
        $validatedData = $request->validate([
            'school_id' => 'required|exists:schools,id',
            'class_group_id' => 'nullable|exists:class_groups,id',
            'is_active' => 'nullable|boolean',
            'nis' => 'required|string',
            'nisn' => 'required|string',
            'student_name' => 'required|string',
            'gender' => 'required|in:male,female',
        ]);

        $student->update($validatedData);
        $student->load(['classGroup', 'school']);

        return response()->json([
            'status' => 'success',
            'message' => 'Student updated successfully',
            'data' => $student
        ]);

    }

    public function destroy($id)
    {
        $student = Student::find($id);
        $student->delete();
        return response()->json([
            'status' => 'success',
            'message' => 'Student deleted successfully'
        ]);

    }
}
