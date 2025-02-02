<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSchedulerRequest
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $schedulerToken = $request->header('X-Scheduler-Token');

        if ($schedulerToken !== config('app.scheduler_token')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized access.',
            ], 401);
        }
        
        return $next($request);
    }
}
