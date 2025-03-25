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
        $schoolId = 0;
        if(!auth()->user()->hasRole('super_admin')){
            $schoolId = auth()->user()?->school_id;
            
            validate_school_access(config('school.id'), auth()->user()); //unnecessary need to change
        } else {
            $schoolId = $request->header('School-Id') ?? auth()->user()->school_id;
        }

        if($schoolId == null){
            abort(403, 'You dont have access to this school data.');
        }

        if( !School::where('id', $schoolId)->exists()){
            abort(404, 'School not found.');
        }
        
        config(['school.id' => $schoolId]);
        return $next($request);
    }
}
