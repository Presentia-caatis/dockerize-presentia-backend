<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{

    public function run()
    {
        $this->call([
            SubscriptionPlanSeeder::class,
            SchoolSeeder::class,
        ]);

        $schools = \App\Models\School::all();
        (new UserSeeder())->run();
        foreach ($schools as $school) {
            (new AttendanceScheduleAndDaySeeder($school->id))->run();
            (new CheckInStatusSeeder($school->id))->run();
            (new AttendanceWindowSeeder($school->id))->run();
            (new AbsencePermitTypeSeeder($school->id))->run();
        }
    }
}
