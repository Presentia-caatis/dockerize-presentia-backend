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
        ini_set('memory_limit', '2G');

        DB::beginTransaction();
        try {
            \Log::info("Seeder started");
            $schoolId = 1;

            // FIRST SEMESTER: 2025-01-01 to 2025-07-01
            $semester1 = Semester::create([
                'school_id' => $schoolId,
                'academic_year' => '2024/2025',
                'period' => 'even',
                'start_date' => '2025-01-01',
                'end_date' => '2025-07-01',
                'is_active' => true,
            ]);

            // SECOND SEMESTER: 2025-07-07 to 2025-12-30
            $semester2 = Semester::create([
                'school_id' => $schoolId,
                'academic_year' => '2025/2026',
                'period' => 'odd',
                'start_date' => '2025-07-07',
                'end_date' => '2025-12-30',
                'is_active' => true,
            ]);

            // Enroll students for first semester only (as before)
            Student::withoutGlobalScopes([SchoolScope::class, SemesterScope::class])
                ->where('school_id', $schoolId)
                ->whereDate('created_at', '<', '2025-07-01')
                ->whereNotNull('class_group_id')
                ->chunk(100, function ($students) use ($semester1, $semester2) {
                    foreach ($students as $student) {
                        Enrollment::create([
                            'student_id' => $student->id,
                            'class_group_id' => $student->class_group_id,
                            'school_id' => $student->school_id,
                            'semester_id' => $semester1->id,
                        ]);
                        Enrollment::create([
                            'student_id' => $student->id,
                            'class_group_id' => $student->class_group_id,
                            'school_id' => $student->school_id,
                            'semester_id' => $semester2->id,
                        ]);
                    }
                });

            Student::withoutGlobalScopes([SchoolScope::class, SemesterScope::class])
                ->where('school_id', $schoolId)
                ->whereDate('created_at', '>', '2025-07-01')
                ->whereNotNull('class_group_id')
                ->chunk(100, function ($students) use ($semester2) {
                    foreach ($students as $student) {
                        Enrollment::create([
                            'student_id' => $student->id,
                            'class_group_id' => $student->class_group_id,
                            'school_id' => $student->school_id,
                            'semester_id' => $semester2->id,
                        ]);
                    }
                });

            //Data which will be duplicated into next semester
            $tablesToDuplicate = [
                'check_in_statuses',
                'check_out_statuses',
                'absence_permit_types',
                'days',
            ];

            //Assign all the data with semester 1 id
            foreach ($tablesToDuplicate as $table) {
                DB::table($table)
                    ->where('school_id', $schoolId)
                    ->update(['semester_id' => $semester1->id]);
            }

            //Assign all previouse semester with semester 1 id
            DB::table('attendance_schedules')
                ->join('days', 'attendance_schedules.id', '=', 'days.attendance_schedule_id')
                ->where('days.school_id', $schoolId)
                ->update(['attendance_schedules.semester_id' => $semester1->id]);

            DB::table('absence_permits')->where('school_id', $schoolId)
                ->update(['semester_id' => $semester1->id]);


            //Duplicate all the data from previous semester to now
            foreach ($tablesToDuplicate as $table) {
                $rows = DB::table($table)
                    ->where('school_id', $schoolId)
                    ->where('semester_id', $semester1->id)
                    ->get();

                foreach ($rows as $row) {
                    $newRow = (array) $row;
                    unset($newRow['id']);
                    $newRow['semester_id'] = $semester2->id;
                    DB::table($table)->insert($newRow);
                }
            }

            $schedules = DB::table('attendance_schedules')
                ->join('days', 'attendance_schedules.id', '=', 'days.attendance_schedule_id')
                ->where('days.school_id', $schoolId)
                ->where('attendance_schedules.semester_id', $semester1->id)
                ->select('attendance_schedules.*')
                ->distinct()
                ->get();

            foreach ($schedules as $schedule) {
                $newSchedule = (array) $schedule;
                unset($newSchedule['id']);
                $newSchedule['semester_id'] = $semester2->id;
                $newId = DB::table('attendance_schedules')->insertGetId($newSchedule);
                $newScheduleIds[$schedule->type] = $newId; // assuming 'type' is unique (e.g., 'default', 'holiday')
            }

            DB::table('days')
                ->where('school_id', $schoolId)
                ->where('semester_id', $semester2->id)
                ->whereIn('name', ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'])
                ->update(['attendance_schedule_id' => $newScheduleIds['default']]);

            DB::table('days')
                ->where('school_id', $schoolId)
                ->where('semester_id', $semester2->id)
                ->whereIn('name', ['saturday', 'sunday'])
                ->update(['attendance_schedule_id' => $newScheduleIds['holiday']]);


            DB::table('attendance_windows')
                ->where('school_id', $schoolId)
                ->whereBetween('date', ['2025-01-01', '2025-07-01'])
                ->update(['semester_id' => $semester1->id]);

            DB::table('attendance_windows')
                ->where('school_id', $schoolId)
                ->whereBetween('date', ['2025-07-07', '2025-12-30'])
                ->update(['semester_id' => $semester2->id]);


            DB::table('attendance_windows')
                ->where('school_id', $schoolId)
                ->where(function ($q) {
                    $q->whereNotBetween('date', ['2025-01-01', '2025-07-01'])
                        ->whereNotBetween('date', ['2025-07-07', '2025-12-30']);
                })
                ->delete();


            DB::table('attendances')
                ->where('school_id', $schoolId)
                ->whereBetween('created_at', ['2025-01-01 00:00:00', '2025-07-01 23:59:59'])
                ->update(['semester_id' => $semester1->id]);

            DB::table('attendances')
                ->where('school_id', $schoolId)
                ->whereBetween('created_at', ['2025-07-07 00:00:00', '2025-12-30 23:59:59'])
                ->update(['semester_id' => $semester2->id]);

            $inStatusesNew = DB::table('check_in_statuses')
                ->where('school_id', $schoolId)
                ->where('semester_id', $semester2->id)
                ->pluck('id', 'late_duration')
                ->toArray();

            $outStatusesNew = DB::table('check_out_statuses')
                ->where('school_id', $schoolId)
                ->where('semester_id', $semester2->id)
                ->pluck('id', 'late_duration')
                ->toArray();

            $newAttendances = DB::table('attendances')
                ->where('school_id', $schoolId)
                ->where('semester_id', $semester2->id)
                ->get();

            foreach ($newAttendances as $att) {

                $oldCheckInStatus = DB::table('check_in_statuses')->where('id', $att->check_in_status_id)->first();
                $oldCheckOutStatus = DB::table('check_out_statuses')->where('id', $att->check_out_status_id)->first();

                // Find new status id with the same late_duration (if exists)
                $newCheckInStatusId = $oldCheckInStatus && isset($inStatusesNew[$oldCheckInStatus->late_duration])
                    ? $inStatusesNew[$oldCheckInStatus->late_duration] : $att->check_in_status_id;

                $newCheckOutStatusId = $oldCheckOutStatus && isset($outStatusesNew[$oldCheckOutStatus->late_duration])
                    ? $outStatusesNew[$oldCheckOutStatus->late_duration] : $att->check_out_status_id;

                DB::table('attendances')
                    ->where('id', $att->id)
                    ->update([
                        'check_in_status_id' => $newCheckInStatusId,
                        'check_out_status_id' => $newCheckOutStatusId,
                    ]);
            }


            DB::table('attendances')
                ->where('school_id', $schoolId)
                ->where(function ($q) {
                    $q->whereNotBetween('check_in_time', ['2025-01-01 00:00:00', '2025-07-01 23:59:59'])
                        ->whereNotBetween('check_in_time', ['2025-07-07 00:00:00', '2025-12-30 23:59:59']);
                })
                ->delete();

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            \Log::error('Seeder failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        }
    }
}