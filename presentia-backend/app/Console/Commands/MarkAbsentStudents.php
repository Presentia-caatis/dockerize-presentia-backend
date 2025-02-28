<?php

namespace App\Console\Commands;

use App\Http\Controllers\AttendanceController;
use App\Models\AttendanceWindow;
use App\Models\School;
use Illuminate\Console\Command;
use function App\Helpers\current_school_timezone;
use function App\Helpers\stringify_convert_utc_to_timezone;

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
        $currentDate =stringify_convert_utc_to_timezone(\Carbon\Carbon::now(), current_school_timezone(), 'Y-m-d');
        $request = new \Illuminate\Http\Request([
            'attendance_window_ids' => AttendanceWindow::where('date', $currentDate)->pluck('id')->toArray()
        ]);
        

        $controller = app(AttendanceController::class);
        $controller->markAbsentStudents($request);

        $this->info("Absent students marked for School ID: {$this->argument('school_id')}");
    }
}
