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
    public function handle(Request $request, Closure $next, bool $skipChecks = false): Response
    {
        $schoolId = 0;
        if(!$skipChecks){
            dd(auth()->user()->hasRole('super_admin'));
            if(!auth()->user()->hasRole('super_admin')){
                $schoolId = auth()->user()?->school_id;
            } else {
                $schoolId = $request->header('School-Id');
            }

            if($schoolId == null){
                abort(403, 'You dont have access to this school data.');
            }
        } else {
            $schoolId = $request->school_id;

            if($schoolId == null){
                abort(422, 'school_id is required');
            }
        }
        
        if(!School::where('id', $schoolId)->exists()){
            abort(404, 'School not found.');
        }
        config(['school.id' => $schoolId]);
        return $next($request);
    }
}
