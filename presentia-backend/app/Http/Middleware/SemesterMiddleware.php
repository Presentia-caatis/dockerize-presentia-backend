<?php

namespace App\Http\Middleware;

use App\Models\Semester;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use function App\Helpers\current_school_timezone;

class SemesterMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $semester = null;

        if ($request->header('Semester-Id')) {
            $semesterId = Semester::findOrfail($request->header('Semester-Id'))->id;
        } else {
            $now = now()->timezone(current_school_timezone())->toDateString();

            $semester = Semester::where('start_date', '<=', $now)
                ->where('end_date', '>=', $now)
                ->where('is_active', true)
                ->first();

            if (!$semester) {
                $semester = Semester::where('start_date', '>', $now)
                    ->where('is_active', true)
                    ->orderBy('start_date', 'asc')
                    ->first();
            }

            if (!$semester) {
                abort(422, "There is no active semester in the current or upcoming dates.");
            }

            $semesterId = $semester->id;
        }

        config(['semester.id' => $semesterId]);
        return $next($request);
    }
}
