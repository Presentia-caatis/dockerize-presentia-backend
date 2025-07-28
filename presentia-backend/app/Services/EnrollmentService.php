<?php

namespace App\Services;

use App\Jobs\ImportEnrollmentJob;
use App\Models\Enrollment;
use Maatwebsite\Excel\Facades\Excel;
use function App\Helpers\current_school_id;
use function App\Helpers\current_semester_id;

class EnrollmentService
{
    public function getAll(array $data)
    {
        $perPage = $data['perPage'] ?? 10;
        return Enrollment::with(['student', 'classGroup', 'semester'])->paginate($perPage);
    }

    public function getById($id)
    {
        // Use with() before findOrFail()
        return Enrollment::with(['student', 'classGroup', 'semester'])->findOrFail($id);
    }

    public function create(array $data)
    {
        $enrollment = Enrollment::create($data);
        // Use load() to eager load relations
        return $enrollment->load(['student', 'classGroup', 'semester']);
    }

    public function createFromFile($file)
    {
        set_time_limit(600);

        $schoolId = current_school_id();
        $semesterId = current_semester_id();

        $data = Excel::toArray([], $file)[0];
        unset($data[0]); 

        $chunks = array_chunk($data, 500);
        $totalRows = count($data);
        $successCount = 0;
        $failedCount = 0;
        $failedRows = [];
        $students = [];

        foreach ($chunks as $chunk) {
            $students = [];
            foreach ($chunk as $row) {
                try {

                    if (count($row) < 5 || empty($row[0]) || empty($row[1]) || empty($row[2]) || empty($row[3])) {
                        $failedCount++;
                        $failedRows[] = ['row' => $row, 'error' => 'Incomplete or missing data'];
                        continue;
                    }

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
                ImportEnrollmentJob::dispatch($students, $schoolId, $semesterId)->onQueue('import-enrollment');
            }
        }

        $result = [
            'status' => 'processing',
            'message' => 'Student import has started and is being processed in the background.',
            'total_records' => $totalRows,
            'queued_records' => $successCount,
            'skipped_records' => $failedCount,
            'skipped_details' => $failedRows,
        ];
        return [$result, 202];
    }

    public function update($id, array $data)
    {
        $enrollment = Enrollment::findOrFail($id);
        $enrollment->update($data);
        return $enrollment->load(['student', 'classGroup', 'semester']);
    }

    public function delete($id)
    {
        $enrollment = Enrollment::findOrFail($id);
        $enrollment->delete();
        return true;
    }
}