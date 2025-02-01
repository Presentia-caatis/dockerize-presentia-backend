<?php

namespace App\Http\Middleware;

use App\Models\School;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use function App\Helpers\validate_school_access;

class SchoolMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $schoolId = auth()->user()->school_id;

        School::findOrFail($schoolId);
        validate_school_access($schoolId, auth()->user());
        
        config(['school.id' => $schoolId]);
        return $next($request);
    }
}
