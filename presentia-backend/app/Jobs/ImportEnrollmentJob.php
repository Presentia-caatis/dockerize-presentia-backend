<?php

namespace App\Jobs;

use App\Models\Enrollment;
use App\Models\FailedImportEnrollmentJob;
use App\Models\Scopes\SemesterScope;
use App\Models\Student;
use App\Models\ClassGroup;
use DB;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
class ImportEnrollmentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $students, $schoolId, $semesterId;

    public function __construct($students, $schoolId, $semesterId)
    {
        $this->students = $students;
        $this->schoolId = $schoolId;
        $this->semesterId = $semesterId;
    }

    public function handle()
    {
        config(['school.id' => $this->schoolId , 'semester.id' => $this->semesterId]);
        $existingClassGroups = ClassGroup::withoutGlobalScope(SemesterScope::class)
            ->pluck('id', 'class_name')
            ->toArray();

        $existingStudents = Student::withoutGlobalScope(SemesterScope::class)
            ->pluck('id', 'nisn')
            ->toArray();

        $existingEnrollments = Enrollment::pluck('class_group_id', 'student_id')
            ->toArray();

        foreach ($this->students as $student) {
            $rawStudent = $student;
            try {
                DB::beginTransaction();
                if (!array_key_exists($student['class_group_name'], $existingClassGroups)) {
                    $classGroup = ClassGroup::create([
                        'school_id' => $student['school_id'],
                        'class_name' => $student['class_group_name'],
                    ]);
                    $existingClassGroups[$student['class_group_name']] = $classGroup->id;
                }
                unset($student['class_group_name']);

                if (array_key_exists($student['nisn'], $existingStudents)) {
                    Student::where('id', $existingStudents[$student['nisn']])->update($student);
                } else {
                    $createdStudent = Student::create($student);
                    $existingStudents[$student['nisn']] = $createdStudent->id;
                }

                if (array_key_exists($existingStudents[$student['nisn']], $existingEnrollments)) {
                    if ($existingEnrollments[$existingStudents[$student['nisn']]] != $existingClassGroups[$rawStudent['class_group_name']]) {
                        Enrollment::where('student_id', $existingStudents[$rawStudent['nisn']])->update([
                            'class_group_id' => $existingClassGroups[$rawStudent['class_group_name']]
                        ]);
                    }
                } else {
                    Enrollment::create([
                        'student_id' => $existingStudents[$student['nisn']],
                        'class_group_id' => $existingClassGroups[$rawStudent['class_group_name']],
                        'school_id' => $this->schoolId,
                        'semester_id' => $this->semesterId,
                    ]);
                }

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();

                FailedImportEnrollmentJob::create([
                    'nisn' => $rawStudent['nisn'] ?? null,
                    'student_id' => $existingStudents[$rawStudent['nisn']] ?? null,
                    'school_id' => $rawStudent['school_id'] ?? $this->schoolId,
                    'semester_id' => $this->semesterId,
                    'class_group_name' => $rawStudent['class_group_name'] ?? null,
                    'row_data' => json_encode($rawStudent),
                    'error_message' => $e->getMessage(),
                ]);

                continue;
            }
        }
    }

}
