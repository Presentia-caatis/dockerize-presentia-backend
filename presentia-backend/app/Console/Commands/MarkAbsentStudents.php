<?php

namespace App\Console\Commands;

use App\Http\Controllers\AttendanceController;
use Illuminate\Console\Command;

class MarkAbsentStudents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'call:mark-absent-students {school_id} {attendance_window_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        config(['school.id' => $this->argument('school_id')]);

        $request = new \Illuminate\Http\Request([
            'attendance_window_ids' => [$this->argument('attendance_window_id')]
        ]);
        
        $controller = app(AttendanceController::class);
        $controller->markAbsentStudents($request);

        $this->info("Absent students marked for School ID: {$this->argument('school_id')}");
    }
}
