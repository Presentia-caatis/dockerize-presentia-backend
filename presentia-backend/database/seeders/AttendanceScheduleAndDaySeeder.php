<?php

namespace Database\Seeders;

use App\Models\AttendanceSchedule;
use App\Models\Day;
use App\Models\School;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AttendanceScheduleAndDaySeeder extends Seeder
{
    private $school_id;

    public function __construct($school_id)
    {
        $this->school_id = $school_id;
    }
    
    public function run(): void
    {
        
        $school = School::findOrFail($this->school_id);
        $schoolTimeZone = $school->timezone ?? 'UTC';

        $currentDate = Carbon::createFromFormat('d-m-Y', '29-01-2025', $schoolTimeZone);
        
        $defaultAttendanceSchedule = AttendanceSchedule::create([
            'event_id' => null,
            'type' => 'default',
            'name' => 'Default Schedule',
            'check_in_start_time' => $currentDate->copy()->setTime(6,0),
            'check_in_end_time' => $currentDate->copy()->setTime(6,30),
            'check_out_start_time' => $currentDate->copy()->setTime(16,0),
            'check_out_end_time' => $currentDate->copy()->setTime(15,0),
        ]);

        $holidayAttendanceSchedule = AttendanceSchedule::create([
            'event_id' => null,
            'type' => 'holiday',
            'name' => 'Default Schedule',
        ]);

        $weekdays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
        foreach ($weekdays as $day) {
            Day::create([
                'attendance_schedule_id' => $defaultAttendanceSchedule->id,
                'school_id' => $this->school_id,
                'name' => $day,
            ]);
        }

        $weekends = ['saturday', 'sunday'];
        foreach ($weekends as $day) {
            Day::create([
                'attendance_schedule_id' => $holidayAttendanceSchedule->id,
                'school_id' => $this->school_id,
                'name' => $day,
            ]);
        }
    }
}
