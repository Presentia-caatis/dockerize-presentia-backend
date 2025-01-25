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
        $request->validate([
            'school_id' => 'required|exists:schools,id',
            'class_group_id' => 'nullable|exists:class_groups,id',
            'is_active' => 'nullable|boolean',
            'nis' => 'required|string',
            'nisn' => 'required|string',
            'student_name' => 'required|string',
            'gender' => 'required|in:male,female',
        ]);


        $data = Student::create($request->all());
        $data->load(['classGroup', 'school']);
        return response()->json([
            'status' => 'success',
            'message' => 'Student created successfully',
            'data' => $data
        ], 201);

    }

    public function storeViaFile(Request $request)
    {
        $request->validate([
            'school_id' => 'required|exists:schools,id',
            'file' => 'required|file|mimes:xlsx,xls',
        ]);

        $schoolId = $request->school_id;
        $data = Excel::toArray([], $request->file('file'))[0];
        unset($data[0]);

        // Batch processing
        $chunks = array_chunk($data, 100);

        $totalRows = count($data);
        $successCount = 0;
        $failedCount = 0;
        $failedRows = [];

        foreach ($chunks as $chunk) {
            $students = [];

            foreach ($chunk as $index => $row) {
                if (count($row) < 5 || empty($row[0]) || empty($row[1]) || empty($row[2]) || empty($row[3]) || empty($row[4])) {
                    $failedCount++;
                    $failedRows[] = [
                        'row' => $row,
                        'error' => 'Incomplete or missing data',
                    ];
                    continue;
                }

                $gender = strtolower($row[3]) === 'l' ? 'male' : (strtolower($row[3]) === 'p' ? 'female' : null);

                if (!$gender) {
                    $failedCount++;
                    $failedRows[] = [
                        'row' => $row,
                        'error' => 'Invalid gender value',
                    ];
                    continue;
                }

                try {

                    $classGroup = ClassGroup::firstOrCreate(
                        [
                            'school_id' => $schoolId,
                            'class_name' => $row[4],
                        ],
                        [
                            'amount_of_students' => 0,
                        ]
                    );

                    $students[] = [
                        'school_id' => $schoolId,
                        'class_group_id' => $classGroup->id,
                        'nis' => $row[0],
                        'nisn' => $row[1],
                        'student_name' => $row[2],
                        'gender' => $gender,
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    $successCount++;
                    $classGroup->increment('amount_of_students');
                } catch (\Exception $e) {
                    $failedCount++;
                    $failedRows[] = [
                        'row' => $row,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            if (!empty($students)) {
                Student::insert($students);
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

    public function show(Student $student)
    {
        $student->load(['classGroup', 'school']);
        return response()->json([
            'status' => 'success',
            'message' => 'Student retrieved successfully',
            'data' => $student
        ]);

    }

    public function update(Request $request, Student $student)
    {
        $request->validate([
            'school_id' => 'required|exists:schools,id',
            'class_group_id' => 'nullable|exists:class_groups,id',
            'is_active' => 'nullable|boolean',
            'nis' => 'required|string',
            'nisn' => 'required|string',
            'student_name' => 'required|string',
            'gender' => 'required|in:male,female',
        ]);

        $student->update($request->all());
        $student->load(['classGroup', 'school']);

        return response()->json([
            'status' => 'success',
            'message' => 'Student updated successfully',
            'data' => $student
        ]);

    }

    public function destroy(Student $student)
    {

        $student->delete();
        return response()->json([
            'status' => 'success',
            'message' => 'Student deleted successfully'
        ]);

    }
}
