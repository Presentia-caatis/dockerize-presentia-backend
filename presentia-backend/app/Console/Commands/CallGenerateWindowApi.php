<?php

namespace App\Console\Commands;

use App\Http\Controllers\AttendanceWindowController;
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
        // Log::info("api hit {$this->argument('school_id')}");
        // $url = config('app.url') . '/api/attendance-window/generate-window';
        config(['school.id' => $this->argument('school_id')]);
        $request = new \Illuminate\Http\Request([
            'date' => stringify_convert_utc_to_timezone(\Carbon\Carbon::now(), current_school_timezone(), 'Y-m-d')
        ]);
    
        // Call the controller with the request object
        $controller = app(AttendanceWindowController::class);
        $controller->generateWindow($request);
        
        // $response = Http::withHeaders([
        //     'X-Scheduler-Token' => config('app.scheduler_token'),
        // ])->post($url, [
        //     'date' => stringify_convert_utc_to_timezone(\Carbon\Carbon::now(), current_school_timezone(), 'Y-m-d'),
        //     'school_id' => $this->argument('school_id') 
        // ]);



        // Log the response
        // if ($response->successful()) {
        //     Log::info('Scheduling task for school', [
        //         'response' => 'API called successfully: ' . $response->body()
        //     ]);
        //     $this->info('API called successfully: ' . $response->body());
        // } else {
        //     $this->error('Failed to call the API: ' . $response->body());
        // }
    }
}
