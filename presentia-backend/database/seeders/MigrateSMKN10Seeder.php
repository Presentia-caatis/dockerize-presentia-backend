<?php

namespace Database\Seeders;

use App\Models\Enrollment;
use App\Models\Scopes\SchoolScope;
use App\Models\Scopes\SemesterScope;
use App\Models\Semester;
use App\Models\Student;
use Carbon\Carbon;
use DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Throwable;

class MigrateSMKN10Seeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::beginTransaction();

        try {
            $start = Carbon::parse('2025-01-01')->toDateString();
            $end = Carbon::parse('2025-07-13')->toDateString();
            $semester = null;
            $schoolId = 1;

            $semester = Semester::create([
                'school_id' => $schoolId,
                'academic_year' => '2024/2025',
                'period' => 'even',
                'start_date' => $start,
                'end_date' => $end,
                'is_active' => true,
            ]);

            $student = Student::withoutGlobalScopes([SchoolScope::class, SemesterScope::class])
                ->where('school_id', $schoolId)
                ->whereNotNull('class_group_id')
                ->chunk(100, function ($students) use ($semester) {
                    foreach ($students as $student) {
                        Enrollment::create([
                            'student_id' => $student->id,
                            'class_group_id' => $student->class_group_id,
                            'school_id' => $student->school_id,
                            'semester_id' => $semester->id,
                        ]);
                    }
                });

            DB::table('check_in_statuses')->where('school_id', $schoolId)->update(['semester_id' => $semester?->id]);
            DB::table('check_out_statuses')->where('school_id', $schoolId)->update(['semester_id' => $semester?->id]);
            DB::table('attendances')->where('school_id', $schoolId)->update(['semester_id' => $semester?->id]);
            DB::table('attendance_windows')->where('school_id', $schoolId)->update(['semester_id' => $semester?->id]);
            DB::table('absence_permit_types')->where('school_id', $schoolId)->update(['semester_id' => $semester?->id]);
            DB::table('absence_permits')->where('school_id', $schoolId)->update(['semester_id' => $semester?->id]);
            DB::table('attendance_schedules')
                ->whereIn('id', function ($query) use ($schoolId){
                    $query->select('attendance_schedule_id')
                        ->from('days')
                        ->where('school_id', $schoolId);
                })
                ->update(['semester_id' => $semester?->id]);
            DB::table('days')->where('school_id', $schoolId)->update(['semester_id' => $semester?->id]);
            DB::table('events')->where('school_id', $schoolId)->update(['semester_id' => $semester?->id]);

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e; // Rethrow the error so you know why it failed
        }
    }
}
