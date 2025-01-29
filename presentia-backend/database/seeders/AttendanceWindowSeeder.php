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

        $currentDate = Carbon::createFromFormat('d-m-Y', '29-01-2025', $schoolTimeZone);

        AttendanceWindow::create([
            'day_id' => 4,
            'school_id' => $this->school_id,
            'name' => 'Default Schedule ' . $currentDate->format('Y-m-d'),
            'total_present' => 0,
            'total_absent' => 0,
            'date' => $currentDate->format('Y-m-d'),
            'type' => 'default',
            'check_in_start_time' => $currentDate->copy()->setTime(6, 0),
            'check_in_end_time' => $currentDate->copy()->setTime(6, 30),
            'check_out_start_time' => $currentDate->copy()->setTime(16, 0),
            'check_out_end_time' => $currentDate->copy()->setTime(15, 0),
        ]);
    }

}
