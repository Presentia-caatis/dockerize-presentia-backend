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
        foreach ($schools as $school) {
            (new UserSeeder($school->id))->run();
            (new AttendanceScheduleAndDaySeeder($school->id))->run();
            //(new ClassGroupSeeder($school->id))->run();
            //(new StudentSeeder($school->id))->run();
            (new CheckInStatusSeeder($school->id))->run();
            (new AttendanceWindowSeeder($school->id))->run();
        }
    }
}
