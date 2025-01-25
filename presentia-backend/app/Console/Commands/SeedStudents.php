<?php

namespace App\Console\Commands;

use App\Models\ClassGroup;
use Database\Seeders\StudentSeeder;
use Illuminate\Console\Command;

class SeedStudents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seed:students {school_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed class groups and students for a specific school';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $schoolId = $this->argument('school_id');

        if (!is_numeric($schoolId)) {
            $this->error('The school_id must be a number.');
            return 1;
        }

        if (!ClassGroup::where('school_id', $schoolId)->exists()) {
            $this->info("No ClassGroups found for school ID: {$schoolId}. Seeding default ClassGroups...");
            ClassGroup::factory(3)->create(['school_id' => $schoolId]);
        }

        $this->info("Seeding students for school ID: {$schoolId}...");
        $seeder = new StudentSeeder($schoolId);
        $seeder->run();

        $this->info("Students and ClassGroups seeded successfully for school ID: {$schoolId}");
        return 0;
    }
}