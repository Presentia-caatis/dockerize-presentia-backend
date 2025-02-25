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
    AdmsCredentialController,
    SocialiteController,
    JobController,
    AuthController
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
    // Time Routes
    Route::prefix('time')->group(function () {
        Route::get('/current', [TimeController::class, 'getCurrentTime']);
    });

    // User Routes
    Route::prefix('user')->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::post('/', [UserController::class, 'store']);
        Route::post('/link-to-school/{id}', [UserController::class, 'linkToSchool']);
        Route::get('/get-by-token', [UserController::class, 'getByToken']);
        Route::get('/{id}', [UserController::class, 'getById']);
        Route::put('/{id}', [UserController::class, 'update']);
        Route::delete('/{id}', [UserController::class, 'destroy']);
    });

    // Feature Routes
    Route::prefix('feature')->group(function () {
        Route::get('/', [FeatureController::class, 'index']);
        Route::post('/', [FeatureController::class, 'store']);
        Route::get('/{id}', [FeatureController::class, 'getById']);
        Route::put('/{id}', [FeatureController::class, 'update']);
        Route::delete('/{id}', [FeatureController::class, 'destroy']);
    });

    // Subscription Plan Routes
    Route::prefix('subscription-plan')->group(function () {
        Route::get('/', [SubscriptionPlanController::class, 'index']);
        Route::post('/', [SubscriptionPlanController::class, 'store']);
        Route::get('/{id}', [SubscriptionPlanController::class, 'getById']);
        Route::put('/{id}', [SubscriptionPlanController::class, 'update']);
        Route::delete('/{id}', [SubscriptionPlanController::class, 'destroy']);
    });

    // Subscription Feature Routes
    Route::prefix('subscription-feature')->group(function () {
        Route::get('/', [SubscriptionFeatureController::class, 'index']);
        Route::post('/', [SubscriptionFeatureController::class, 'store']);
        Route::get('/{id}', [SubscriptionFeatureController::class, 'getById']);
        Route::put('/{id}', [SubscriptionFeatureController::class, 'update']);
        Route::delete('/{id}', [SubscriptionFeatureController::class, 'destroy']);
    });

    // School Routes
    Route::prefix('school')->group(function () {
        Route::get('/', [SchoolController::class, 'index']);
        Route::post('/', [SchoolController::class, 'store']);
        Route::put('/task-scheduler-toogle/{id}', [SchoolController::class, 'taskSchedulerToogle']);
        Route::get('/{id}', [SchoolController::class, 'getById']);
        Route::post('/{id}', [SchoolController::class, 'update']);
        Route::delete('/{id}', [SchoolController::class, 'destroy']);
    });

    // School Feature Routes
    Route::prefix('school-feature')->group(function () {
        Route::get('/', [SchoolFeatureController::class, 'index']);
        Route::post('/', [SchoolFeatureController::class, 'store']);
        Route::get('/{id}', [SchoolFeatureController::class, 'getById']);
        Route::put('/{id}', [SchoolFeatureController::class, 'update']);
        Route::delete('/{id}', [SchoolFeatureController::class, 'destroy']);
    });

    // Subscription History Routes
    Route::prefix('subscription-history')->group(function () {
        Route::get('/', [SubscriptionHistoryController::class, 'index']);
        Route::post('/', [SubscriptionHistoryController::class, 'store']);
        Route::get('/{id}', [SubscriptionHistoryController::class, 'getById']);
        Route::put('/{id}', [SubscriptionHistoryController::class, 'update']);
        Route::delete('/{id}', [SubscriptionHistoryController::class, 'destroy']);
    });

    // Payment Routes
    Route::prefix('payment')->group(function () {
        Route::get('/', [PaymentController::class, 'index']);
        Route::post('/', [PaymentController::class, 'store']);
        Route::get('/{id}', [PaymentController::class, 'getById']);
        Route::put('/{id}', [PaymentController::class, 'update']);
        Route::delete('/{id}', [PaymentController::class, 'destroy']);
    });

    // Jobs Route
    Route::prefix('job')->group(function () {
        Route::get('/failed', [JobController::class, 'failedJobs']);
        Route::post('/retry/{id}', [JobController::class, 'retryJob']);
        Route::post('/flush', [JobController::class, 'flushJobs']);
        Route::post('/restart', [JobController::class, 'restartQueue']);
        Route::get('/pending', [JobController::class, 'pendingJobs']);
        Route::delete('/pending', [JobController::class, 'flushPendingJobs']);
    });

    Route::middleware('school')->group(function () {
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
            Route::post('/', [StudentController::class, 'store']);
            Route::get('/csv', [StudentController::class, 'exportStudents']);
            Route::post('/store-via-file', [StudentController::class, 'storeViaFile']);
            Route::get('/{id}', [StudentController::class, 'getById']);
            Route::put('/{id}', [StudentController::class, 'update']);
            Route::delete('/{id}', [StudentController::class, 'destroy']);
        });

        // Attendance Late Type Routes
        Route::prefix('check-in-status')->group(function () {
            Route::get('/', [CheckInStatusController::class, 'index']);
            Route::post('/', [CheckInStatusController::class, 'store']);
            Route::get('/{id}', [CheckInStatusController::class, 'getById']);
            Route::put('/{id}', [CheckInStatusController::class, 'update']);
            Route::delete('/{id}', [CheckInStatusController::class, 'destroy']);
        });

        // Attendance Routes
        Route::prefix('attendance')->group(function () {
            Route::get('/', [AttendanceController::class, 'index']);
            Route::get('/export-attendance', [AttendanceController::class, 'exportAttendance']);
            Route::get('/{id}', [AttendanceController::class, 'getById']);
            Route::post('/manual', [AttendanceController::class, 'storeManualAttendance']);
            Route::post('/mark-absent', [AttendanceController::class, 'markAbsentStudents']);
            Route::put('/{id}', [AttendanceController::class, 'update']);
            Route::delete('/{id}', [AttendanceController::class, 'destroy']);
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
            Route::post('/generate-window', [AttendanceWindowController::class, 'generateWindow']);
            Route::get('/', [AttendanceWindowController::class, 'index']);
            Route::get('/get-utc', [AttendanceWindowController::class, 'getAllInUtcFormat']);
            Route::get('/{id}', [AttendanceWindowController::class, 'getById']);
            Route::put('/{id}', [AttendanceWindowController::class, 'update']);
            Route::delete('/{id}', [AttendanceWindowController::class, 'destroy']);
        });

        Route::prefix('attendance-schedule')->group(function () {
            Route::get('/', [AttendanceScheduleController::class, 'index']);
            Route::post('/get-by-type', [AttendanceScheduleController::class, 'showByType']);
            Route::post('/', [AttendanceScheduleController::class, 'storeEvent']);
            Route::get('/{id}', [AttendanceScheduleController::class, 'getById']);
            Route::put('/{id}', [AttendanceScheduleController::class, 'update']);
            Route::delete('/{id}', [AttendanceScheduleController::class, 'destroy']);
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
