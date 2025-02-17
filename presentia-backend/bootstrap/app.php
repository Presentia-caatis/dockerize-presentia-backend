<?php

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Support\Facades\Route;
use Illuminate\Console\Scheduling\Schedule;
use function App\Helpers\convert_time_timezone_to_utc;

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
        ]);
        
        $middleware->alias([
            'verified' => \App\Http\Middleware\EnsureEmailIsVerified::class,
            'school' => \App\Http\Middleware\SchoolMiddleware::class,
            'valid-adms' => \App\Http\Middleware\ADMSMiddleware::class,
            'scheduler' => \App\Http\Middleware\EnsureSchedulerRequest::class
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
                'status' => 'error',
                'message' => 'Validation failed',
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

        $exceptions->renderable(function (AuthorizationException $e, $request) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Action is not permissible or you are not authorized to perform this action.',
                'error' => $e->getMessage(),
            ], 403);  // 403 Forbidden
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
        if (!Schema::hasTable('schools')) {
            return;
        }
    
        $schools = App\Models\School::where('is_task_scheduling_active', true)->get();
        foreach ($schools as $school) {
            $schedule->command("call:generate-window-api {$school->id}")
                ->timezone($school->timezone)
                ->dailyAt('00:00');
        }
    })
    ->create();
