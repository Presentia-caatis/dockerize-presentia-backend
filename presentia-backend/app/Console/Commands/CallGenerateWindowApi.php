<?php

namespace App\Console\Commands;

use App\Http\Controllers\AttendanceWindowController;
use App\Models\CheckInStatus;
use Illuminate\Console\Command;
use function App\Helpers\current_school_timezone;
use function App\Helpers\stringify_convert_utc_to_timezone;


class CallGenerateWindowApi extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'call:generate-window-api {school_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calls the /generate-window API endpoint';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        config(['school.id' => $this->argument('school_id')]);
        $request = new \Illuminate\Http\Request([
            'date' => stringify_convert_utc_to_timezone(\Carbon\Carbon::now(), current_school_timezone(), 'Y-m-d')
        ]);
        $controller = app(AttendanceWindowController::class);
        $controller->generateWindow($request);

        $this->info("Attendance window for school id : {$this->argument('school_id')} in {$request->date} has been created");
    }
}
