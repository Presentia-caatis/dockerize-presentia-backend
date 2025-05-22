<?php

use App\Models\AttendanceWindow;
use App\Models\CheckInStatus;
use App\Models\Scopes\SchoolScope;
use Carbon\Carbon;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Support\Facades\Route;
use Illuminate\Console\Scheduling\Schedule;
use function App\Helpers\stringify_convert_utc_to_timezone;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('web')
                ->prefix('auth')
                ->group(base_path('routes/auth.php'));
        }
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\Session\Middleware\AuthenticateSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);

        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);


        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'verified' => \App\Http\Middleware\EnsureEmailIsVerified::class,
            'school' => \App\Http\Middleware\SchoolMiddleware::class,
            'valid-adms' => \App\Http\Middleware\ADMSMiddleware::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            '*',
        ]);

        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->renderable(function (\Laravel\Socialite\Two\InvalidStateException $e, $request) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid state during authentication.',
            ], 400);
        });

        $exceptions->renderable(function (ValidationException $e, $request) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Unprocessable Content',
                'errors' => $e->errors(),
            ], 422);
        });

        $exceptions->renderable(function (NotFoundHttpException $e, $request) {
            return response()->json([
                'status' => 'error',
                'message' => 'Resource not found',
                'error' => $e->getMessage(),
            ], 404);  // 404 Not Found status
        });

        $exceptions->renderable(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, $request) {
            if ($e->getStatusCode() === 403) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'You are not allowed to perform this action.',
                    'error' => $e->getMessage(),
                ], 403);
            }
        });

        $exceptions->renderable(function (AuthenticationException $e, $request) {
            return response()->json([
                'status' => 'failed',
                'message' => 'You are not logged in or your session has expired.',
            ], 401); // Unauthorized
        });

        $exceptions->renderable(function (Throwable $e, $request) {
            return response()->json([
                'status' => 'error',
                'message' => 'Something went wrong',
                'error' => $e->getMessage(),
            ], 500);  // 500 Internal Server Error
        });
    })
    ->withSchedule(function (Schedule $schedule) {
        if (!Schema::hasTable('schools') || DB::table('schools')->count() === 0) {
            return;
        }

        $schoolsQuery = App\Models\School::where('is_task_scheduling_active', true);

        $maxLateDurations = CheckInStatus::withoutGlobalScope(SchoolScope::class)
            ->selectRaw('school_id, MAX(late_duration) as max_late_duration')
            ->whereIn('school_id', (clone $schoolsQuery)->pluck('id')->toArray())
            ->groupBy('school_id')
            ->pluck('max_late_duration', 'school_id');

        foreach ($schoolsQuery->get() as $school) {
            $maxLateDuration = $maxLateDurations[$school->id] ?? null;

            /**
             * @Schedule Generate window API for the school
             * */
            $schedule->command("call:generate-window-api $school->id")
                ->timezone($school->timezone)
                ->dailyAt('00:00');

            /**
             * @Schedule mark absent students
             * */
            //Get check in end times for today
            $checkInEnds = AttendanceWindow::withoutGlobalScope(SchoolScope::class)
                ->where('date', stringify_convert_utc_to_timezone(now(), $school->timezone, 'Y-m-d'))
                ->where('type', '!=', 'holiday')
                ->pluck('check_in_end_time', 'id')
                ->toArray();

            foreach ($checkInEnds as $attendanceWindowId => $checkInEnd) {
                $scheduleTime = Carbon::parse($checkInEnd, $school->timezone)
                    ->addMinutes($maxLateDuration)
                    ->format('H:i');
                $schedule->command("call:mark-absent-students $school->id $attendanceWindowId")
                    ->timezone($school->timezone)
                    ->dailyAt($scheduleTime);
            }

        }
    })
    ->create();
