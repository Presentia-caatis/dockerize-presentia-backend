<?php

namespace App\Http\Controllers;

use Artisan;
use DB;
use Illuminate\Http\Request;
use Queue;

class JobController extends Controller
{
    /**
     * Get a list of failed jobs.
     */
    public function failedJobs()
    {
        $failedJobs = DB::table('failed_jobs')->get();
        
        return response()->json([
            'status' => 'success',
            'failed_jobs' => $failedJobs
        ]);
    }

    /**
     * Retry a specific failed job.
     */
    public function retryJob($id)
    {
        Artisan::call("queue:retry $id");
        
        return response()->json([
            'status' => 'success',
            'message' => "Retried job ID: $id"
        ]);
    }

    /**
     * Flush (delete) all failed jobs.
     */
    public function flushJobs()
    {
        Artisan::call('queue:flush');

        return response()->json([
            'status' => 'success',
            'message' => 'All failed jobs have been flushed.'
        ]);
    }

    /**
     * Restart the queue worker.
     */
    public function restartQueue()
    {
        Artisan::call('queue:restart');

        return response()->json([
            'status' => 'success',
            'message' => 'Queue workers restarted successfully.'
        ]);
    }

    /**
     * Get the number of pending jobs in the queue.
     */
    public function pendingJobs()
    {
        $pendingJobs = Queue::size(); 

        return response()->json([
            'status' => 'success',
            'pending_jobs' => $pendingJobs
        ]);
    }
}