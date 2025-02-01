<?php

namespace Database\Seeders;

use App\Models\AttendanceWindow;
use App\Models\School;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AttendanceWindowSeeder extends Seeder
{
    private $school_id;

    public function __construct($school_id)
    {
        $this->school_id = $school_id;
    }


    public function run()
    {

        $school = School::findOrFail($this->school_id);
        $schoolTimeZone = $school->timezone ?? 'UTC';

        $currentDate = Carbon::now($schoolTimeZone);

        AttendanceWindow::create([
            'day_id' => 4,
            'school_id' => $this->school_id,
            'name' => 'Default Schedule ' . $currentDate->format('Y-m-d'),
            'total_present' => 0,
            'total_absent' => 0,
            'date' => $currentDate->format('Y-m-d'),
            'type' => 'default',
            'check_in_start_time' => $currentDate->copy()->setTime(7, 0)->utc(),
            'check_in_end_time' => $currentDate->copy()->setTime(8, 0)->utc(),
            'check_out_start_time' => $currentDate->copy()->setTime(16, 0)->utc(),
            'check_out_end_time' => $currentDate->copy()->setTime(15, 0)->utc(),
        ]);
    }

}
