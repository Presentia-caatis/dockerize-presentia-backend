<?php

namespace App\Services;

use App\Filterable;
use App\Models\AbsencePermitType;
use App\Models\AttendanceSchedule;
use App\Models\CheckInStatus;
use App\Models\CheckOutStatus;
use App\Models\Day;
use App\Models\Scopes\SemesterScope;
use App\Models\Semester;
use App\Sortable;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use function App\Helpers\current_school_id;
use function App\Helpers\current_school_timezone;

class SemesterService
{
    use Filterable, Sortable;
    public function getAll(array $data)
    {
        $perPage = $data['perPage'] ?? 10;
        return Semester::paginate($perPage);
    }

    public function getById($id)
    {
        return Semester::findOrFail($id);
    }

    public function create(array $data, array $migrationConfiguration)
    {
        DB::beginTransaction();
        try {
            $schoolId = current_school_id();
            $data["school_id"] = $schoolId;
            $this->checkDateOverlap($data['start_date'], $data['end_date']);

            $closestSemester = Semester::where('start_date', '<=', Carbon::parse($data['start_date'])->toDateString())
                ->orderBy('start_date', 'desc')
                ->first();
            
            config(['semester.id' => $closestSemester->id]);

            $semester = Semester::create($data);
            $defaultAttendanceSchedule = null;
            $holidayAttendanceSchedule = null;

            if ($closestSemester) {
                if ($migrationConfiguration['copy_all_check_in_status'] ?? true) {
                    $checkInStatuses = CheckInStatus::get();

                    foreach ($checkInStatuses as $status) {
                        $newStatus = $status->replicate();
                        $newStatus->semester_id = $semester->id;
                        $newStatus->save();
                    }
                }

                if ($migrationConfiguration['copy_all_check_out_status'] ?? true) {
                    $checkOutStatuses = CheckOutStatus::get();

                    foreach ($checkOutStatuses as $status) {
                        $newStatus = $status->replicate();
                        $newStatus->semester_id = $semester->id;
                        $newStatus->save();
                    }
                }

                if ($migrationConfiguration['copy_all_absence_permit_type'] ?? true) {
                    $absencePermitTypes = AbsencePermitType::get();

                    foreach ($absencePermitTypes as $type) {
                        $newType = $type->replicate();
                        $newType->semester_id = $semester->id;
                        $newType->save();
                    }
                }

                $attendanceSchedule = AttendanceSchedule::withoutGlobalScope(SemesterScope::class)->where('semester_id', $closestSemester->id)
                    ->whereIn('type', ['default', 'holiday'])
                    ->get();
    
            } else {
                CheckInStatus::insert([
                    [
                        'status_name' => 'Late',
                        'description' => 'Student checked in after the allowed time with a grace period of 15 minutes.',
                        'late_duration' => 15,
                        'school_id' => $schoolId,
                        'semester_id' => $semester->id
                    ],
                    [
                        'status_name' => 'On Time',
                        'description' => 'Student checked in within the designated time frame.',
                        'late_duration' => 0,
                        'school_id' => $schoolId,
                        'semester_id' => $semester->id
                    ],
                    [
                        'status_name' => 'Absent',
                        'description' => 'Student did not check in and is considered absent for the day.',
                        'late_duration' => -1,
                        'school_id' => $schoolId,
                        'semester_id' => $semester->id
                    ],
                ]);
    
                CheckOutStatus::insert([
                    [
                        'status_name' => 'absent',
                        'description' => 'Student did not check out, indicating absence for the day.',
                        'late_duration' => -1,
                        'school_id' => $schoolId,
                        'semester_id' => $semester->id
                    ],
                    [
                        'status_name' => 'present',
                        'description' => 'Student successfully checked out within the allowed time.',
                        'late_duration' => 0,
                        'school_id' => $schoolId,
                        'semester_id' => $semester->id
                    ],
                ]);
    
                AbsencePermitType::insert([
                    [
                        'school_id' => $schoolId,
                        'permit_name' => 'Sick',
                        'is_active' => true,
                        'semester_id' => $semester->id
                    ],
                    [
                        'school_id' => $schoolId,
                        'permit_name' => 'Dispensation',
                        'is_active' => true,
                        'semester_id' => $semester->id
                    ],
                ]);
            }
            
            if ($attendanceSchedule) {
                $newAttendanceSchedules = [];
                foreach ($attendanceSchedule as $as) {
                    $newAttendanceSchedule = $as->replicate();
                    $newAttendanceSchedule->semester_id = $semester->id;
                    $newAttendanceSchedule->save();
                    $newAttendanceSchedules[$as->type] = $newAttendanceSchedule;
                }
                $defaultAttendanceSchedule = $newAttendanceSchedules['default'] ?? null;
                $holidayAttendanceSchedule = $newAttendanceSchedules['holiday'] ?? null;
            } else {
                $defaultAttendanceSchedule = AttendanceSchedule::create([
                    'event_id' => null,
                    'type' => 'default',
                    'name' => 'Default Schedule',
                    'check_in_start_time' => '06:00:00',
                    'check_in_end_time' => '06:30:00',
                    'check_out_start_time' => '16:00:00',
                    'check_out_end_time' => '17:00:00',
                    'semester_id' => $semester->id,
                ]);

                $holidayAttendanceSchedule = AttendanceSchedule::create([
                    'event_id' => null,
                    'type' => 'holiday',
                    'name' => 'Holiday Schedule',
                    'semester_id' => $semester->id
                ]);
            }

            $weekdays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
            foreach ($weekdays as $day) {
                Day::create([
                    'attendance_schedule_id' => $defaultAttendanceSchedule->id,
                    'school_id' => $schoolId,
                    'name' => $day,
                    'semester_id' => $semester->id
                ]);
            }

            $weekends = ['saturday', 'sunday'];
            foreach ($weekends as $day) {
                Day::create([
                    'attendance_schedule_id' => $holidayAttendanceSchedule->id,
                    'school_id' => $schoolId,
                    'name' => $day,
                    'semester_id' => $semester->id
                ]);
            }

            DB::commit();
            return $semester;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e; // Or handle exception as you wish
        }
    }

    public function update($id, array $data)
    {
        $semester = Semester::findOrFail($id);

        $startDate = $data['start_date'] ?? $semester->start_date;
        $endDate = $data['end_date'] ?? $semester->end_date;

        $this->checkDateOverlap($startDate, $endDate, $semester->id);

        $semester->update($data);

        return $semester;
    }

    public function delete($id)
    {
        $semester = Semester::findOrFail($id);
        $semester->delete();
        return true;
    }

    public function getByCurrentTime()
    {
        $now = now()->timezone(current_school_timezone())->toDateString();
        
        $semester = Semester::where('start_date', '<=', $now)
            ->where('end_date', '>=', $now)
            ->where('is_active', true)
            ->first();

        return $semester;
    }

    protected function checkDateOverlap($startDate, $endDate, $ignoreId = null)
    {
        $query = Semester::
            where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate])
                    ->orWhere(function ($q2) use ($startDate, $endDate) {
                        $q2->where('start_date', '<', $startDate)
                            ->where('end_date', '>', $endDate);
                    });
            });

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'date_range' => 'Semester date range overlaps with another semester in this school.'
            ]);
        }
    }

    public function isActiveToogle($id)
    {
        $semester = Semester::findOrFail($id);
        $semester->is_active = !$semester->is_active;
        $semester->save();
        return $semester;
    }
}