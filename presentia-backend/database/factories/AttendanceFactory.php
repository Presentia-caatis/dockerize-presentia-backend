<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\CheckOutStatus;
use App\Models\Student;
use App\Models\School;
use App\Models\AttendanceWindow;
use App\Models\CheckInStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;

    public function definition()
    {
        $school = School::factory()->create(); // Buat 1 sekolah

        return [
            'school_id' => $school->id,
            'student_id' => Student::factory()->create(['school_id' => $school->id])->id,
            'check_in_status_id' => CheckInStatus::factory()->create(['school_id' => $school->id])->id,
            'check_out_status_id' => CheckOutStatus::factory()->create(['school_id' => $school->id])->id,
            'attendance_window_id' => AttendanceWindow::factory()->create(['school_id' => $school->id])->id,
            'check_in_time' => $this->faker->dateTime(),
            'check_out_time' => $this->faker->dateTime(),
        ];
    }
}
