<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

use App\Http\Controllers\{
    DashboardStatistic,
    TimeController,
    FeatureController,
    SubscriptionPlanController,
    SubscriptionFeatureController,
    SchoolController,
    SchoolFeatureController,
    SubscriptionHistoryController,
    PaymentController,
    ClassGroupController,
    StudentController,
    CheckInStatusController,
    UserController,
    AttendanceWindowController,
    AttendanceController,
    DocumentController,
    AbsencePermitTypeController,
    AbsencePermitController,
    AttendanceScheduleController,
    DayController,
    RoleController,
    EventController,
    SocialiteController,
    JobController,
    AuthController,
    PermissionController
};

//AUTH API
Route::controller(SocialiteController::class)->group(function () {
    Route::get('auth-google', 'googleLogin');
    Route::get('auth-google-callback', 'googleAuthentication');
});

Route::post('/register', [AuthController::class, 'register'])->name('register');
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::middleware(['auth:sanctum'])->post('/logout', [AuthController::class, 'logout'])->name('logout');

//ADMS API
Route::post('/attendance', [AttendanceController::class, 'store'])->middleware('valid-adms');

//USER API
Route::middleware(['auth:sanctum'])->group(function () {

    Route::prefix('time')->group(function () {
        Route::get('/current', [TimeController::class, 'getCurrentTime']);
    });

    Route::middleware('role:super_admin')->prefix('role')->group(function () {
        Route::get('/', [RoleController::class, 'index']);
        Route::post('/', [RoleController::class, 'store']);
        Route::get('/{id}', [RoleController::class, 'show']);
        Route::put('/{id}', [RoleController::class, 'update']);
        Route::delete('/destroy-all', [RoleController::class, 'destroyAll']);
        Route::delete('/{id}', [RoleController::class, 'destroy']);
        Route::post('/assign', [RoleController::class, 'assignRoleToUser']);
        Route::post('/remove', [RoleController::class, 'removeRoleToUser']);
    });

    Route::middleware('role:super_admin')->prefix('permission')->group(function () {
        Route::get('/', [PermissionController::class, 'index']);
        Route::post('/', [PermissionController::class, 'store']);
        Route::get('/{id}', [PermissionController::class, 'show']);
        Route::put('/{id}', [PermissionController::class, 'update']);
        Route::delete('/destroy-all', [PermissionController::class, 'destroyAll']);
        Route::delete('/{id}', [PermissionController::class, 'destroy']);
        Route::post('/assign', [PermissionController::class, 'assignPermissionToRole']);
        Route::post('/remove', [PermissionController::class, 'removePermissionFromRole']);
    });

    // User Routes
    Route::prefix('user')->group(function () {
        // Allow all authenticated users to access get-by-token
        Route::get('/get-by-token', [UserController::class, 'getByToken']);

        // Allow only super_admin and school_admin to manage link-to-school
        Route::middleware('permission:manage_school_users')->group(function () {
            Route::post('/assign-to-school/{id}', [UserController::class, 'assignToSchool']);
        });

        // Restrict all other routes to users with 'super_admin' role
        Route::middleware('role:super_admin')->group(function () {
            Route::get('/', [UserController::class, 'index']);
            Route::post('/', [UserController::class, 'store']);
            Route::get('/{id}', [UserController::class, 'getById']);
            Route::put('/{id}', [UserController::class, 'update']);
            Route::delete('/{id}', [UserController::class, 'destroy']);
        });
    });

    Route::prefix('feature')->group(function () {
        Route::get('/', [FeatureController::class, 'index']);
        Route::get('/{id}', [FeatureController::class, 'getById']);

        Route::middleware('role:super_admin')->group(function () {
            Route::post('/', [FeatureController::class, 'store']);
            Route::put('/{id}', [FeatureController::class, 'update']);
            Route::delete('/{id}', [FeatureController::class, 'destroy']);
        });
    });

    Route::prefix('subscription-plan')->group(function () {
        Route::get('/', [SubscriptionPlanController::class, 'index']);
        Route::get('/{id}', [SubscriptionPlanController::class, 'getById']);

        Route::middleware('role:super_admin')->group(function () {
            Route::post('/', [SubscriptionPlanController::class, 'store']);
            Route::put('/{id}', [SubscriptionPlanController::class, 'update']);
            Route::delete('/{id}', [SubscriptionPlanController::class, 'destroy']);
        });
    });

    Route::prefix('subscription-feature')->group(function () {
        Route::get('/', [SubscriptionFeatureController::class, 'index']);
        Route::get('/{id}', [SubscriptionFeatureController::class, 'getById']);

        Route::middleware('role:super_admin')->group(function () {
            Route::post('/', [SubscriptionFeatureController::class, 'store']);
            Route::put('/{id}', [SubscriptionFeatureController::class, 'update']);
            Route::delete('/{id}', [SubscriptionFeatureController::class, 'destroy']);
        });
    });

    Route::prefix('school')->group(function () {
        Route::get('/', [SchoolController::class, 'index']);
        Route::get('/{id}', [SchoolController::class, 'getById']);

        Route::middleware('role:super_admin')->group(function () {
            Route::post('/', [SchoolController::class, 'store']);
            Route::put('/task-scheduler-toogle/{id}', [SchoolController::class, 'taskSchedulerToogle']);
            Route::put('/{id}', [SchoolController::class, 'update']);
            Route::delete('/{id}', [SchoolController::class, 'destroy']);
        });
    });

    // Jobs Route
    Route::middleware('role:super_admin')->prefix('job')->group(function () {
        Route::get('/failed', [JobController::class, 'failedJobs']);
        Route::post('/retry/{id}', [JobController::class, 'retryJob']);
        Route::post('/flush', [JobController::class, 'flushJobs']);
        Route::post('/restart', [JobController::class, 'restartQueue']);
        Route::get('/pending', [JobController::class, 'pendingJobs']);
        Route::delete('/pending', [JobController::class, 'flushPendingJobs']);
    });

    Route::middleware(['school', 'permission:basic_school'])->group(function () {
        // Class Group Routes
        Route::prefix('class-group')->group(function () {
            Route::get('/', [ClassGroupController::class, 'index']);
            Route::post('/', [ClassGroupController::class, 'store']);
            Route::get('/{id}', [ClassGroupController::class, 'getById']);
            Route::put('/{id}', [ClassGroupController::class, 'update']);
            Route::delete('/{id}', [ClassGroupController::class, 'destroy']);
        });

        // Student Routes
        Route::prefix('student')->group(function () {
            Route::get('/', [StudentController::class, 'index']);
            Route::get('/{id}', [StudentController::class, 'getById']);

            Route::get('/csv', [StudentController::class, 'exportStudents'])->middleware('role:super_admin');

            Route::middleware('permission:manage_students')->group(function () {
                Route::post('/', [StudentController::class, 'store']);
                Route::post('/store-via-file', [StudentController::class, 'storeViaFile']);
                Route::put('/{id}', [StudentController::class, 'update']);
                Route::delete('/{id}', [StudentController::class, 'destroy']);
            });
        });

        // Attendance Routes
        Route::middleware('permission:manage_attendance')->prefix('attendance')->group(function () {
            Route::get('/', [AttendanceController::class, 'index']);
            Route::get('/export', [AttendanceController::class, 'exportAttendance']);
            Route::put('/adjust', [AttendanceController::class, 'adjustAttendance']);
            Route::post('/manual', [AttendanceController::class, 'storeManualAttendance']);
            Route::post('/mark-absent', [AttendanceController::class, 'markAbsentStudents']);
            Route::delete('/clear-records/{attendanceWindowId}', [AttendanceController::class, 'clearAttendanceRecords']);
            Route::get('/{id}', [AttendanceController::class, 'getById']);
            Route::put('/{id}', [AttendanceController::class, 'update']);
            Route::delete('/{id}', [AttendanceController::class, 'destroy']);
        });

        Route::middleware('permission:manage_schools')->group(function () {
            // Attendance Late Type Routes
            Route::prefix('check-in-status')->group(function () {
                Route::get('/', [CheckInStatusController::class, 'index']);
                Route::post('/', [CheckInStatusController::class, 'store']);
                Route::get('/{id}', [CheckInStatusController::class, 'getById']);
                Route::put('/{id}', [CheckInStatusController::class, 'update']);
                Route::delete('/{id}', [CheckInStatusController::class, 'destroy']);
            });

            // Document Routes
            Route::prefix('document')->group(function () {
                Route::get('/', [DocumentController::class, 'index']);
                Route::post('/', [DocumentController::class, 'store']);
                Route::get('/{id}', [DocumentController::class, 'getById']);
                Route::put('/{id}', [DocumentController::class, 'update']);
                Route::delete('/{id}', [DocumentController::class, 'destroy']);
            });

            // Absence Permit Type Routes
            Route::prefix('absence-permit-type')->group(function () {
                Route::get('/', [AbsencePermitTypeController::class, 'index']);
                Route::post('/', [AbsencePermitTypeController::class, 'store']);
                Route::get('/{id}', [AbsencePermitTypeController::class, 'getById']);
                Route::put('/{id}', [AbsencePermitTypeController::class, 'update']);
                Route::delete('/{id}', [AbsencePermitTypeController::class, 'destroy']);
            });

            // Absence Permit Routes
            Route::prefix('absence-permit')->group(function () {
                Route::get('/', [AbsencePermitController::class, 'index']);
                Route::post('/', [AbsencePermitController::class, 'store']);
                Route::get('/{id}', [AbsencePermitController::class, 'getById']);
                Route::put('/{id}', [AbsencePermitController::class, 'update']);
                Route::delete('/{id}', [AbsencePermitController::class, 'destroy']);
            });

            Route::prefix('attendance-window')->group(function () {
                Route::get('/', [AttendanceWindowController::class, 'index']);
                Route::get('/get-utc', [AttendanceWindowController::class, 'getAllInUtcFormat']);
                Route::get('/{id}', [AttendanceWindowController::class, 'getById']);
                Route::put('/{id}', [AttendanceWindowController::class, 'update']);
                Route::delete('/{id}', [AttendanceWindowController::class, 'destroy']);
            });

            Route::prefix('attendance-schedule')->group(function () {
                Route::get('/', [AttendanceScheduleController::class, 'index']);
                Route::get('/{id}', [AttendanceScheduleController::class, 'getById']);
                Route::put('/{id}', [AttendanceScheduleController::class, 'update']);
                Route::delete('/{id}', [AttendanceScheduleController::class, 'destroy']);
            });

            Route::prefix('event')->group(function () {
                Route::post('/', [EventController::class, 'store']);
                Route::delete('/{id}', [EventController::class, 'destroy']);
            });

            Route::prefix('day')->group(function () {
                Route::get('/', [DayController::class, 'index']);
                Route::get('/{id}', [DayController::class, 'getById']);
                Route::get('/all-by-school', [DayController::class, 'showAllBySchool']);
                Route::put('/{id}', [DayController::class, 'update']);
            });

            Route::prefix('dashboard-statistic')->group(function () {
                Route::get('/static', [DashboardStatistic::class, 'StaticStatistic']);
                Route::get('/daily', [DashboardStatistic::class, 'DailyStatistic']);
            });
        });
    });
});
