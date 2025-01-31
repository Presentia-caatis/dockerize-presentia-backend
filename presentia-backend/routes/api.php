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
    DayController
};


Route::middleware('valid-adms')->group(function() {
    Route::post('/attendance', [AttendanceController::class, 'store']);
});



Route::middleware(['auth:sanctum'])->group(function () {

    // Time Routes
    Route::prefix('time')->group(function () {
        Route::get('/', [TimeController::class, 'getCurrentTime']);
    });

    // User Routes
    Route::prefix('user')->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::post('/', [UserController::class, 'store']);
        Route::post('/link-to-school/{user}', [UserController::class, 'linkToSchool']);
        Route::get('/get-by-token', [UserController::class, 'getByToken']);
        Route::get('/{user}', [UserController::class, 'show']);
        Route::put('/{user}', [UserController::class, 'update']);
        Route::delete('/{user}', [UserController::class, 'destroy']);
    });

    // Feature Routes
    Route::prefix('feature')->group(function () {
        Route::get('/', [FeatureController::class, 'index']);
        Route::post('/', [FeatureController::class, 'store']);
        Route::get('/{feature}', [FeatureController::class, 'show']);
        Route::put('/{feature}', [FeatureController::class, 'update']);
        Route::delete('/{feature}', [FeatureController::class, 'destroy']);
    });

    // Subscription Plan Routes
    Route::prefix('subscription-plan')->group(function () {
        Route::get('/', [SubscriptionPlanController::class, 'index']);
        Route::post('/', [SubscriptionPlanController::class, 'store']);
        Route::get('/{subscriptionPlan}', [SubscriptionPlanController::class, 'show']);
        Route::put('/{subscriptionPlan}', [SubscriptionPlanController::class, 'update']);
        Route::delete('/{subscriptionPlan}', [SubscriptionPlanController::class, 'destroy']);
    });

    // Subscription Feature Routes
    Route::prefix('subscription-feature')->group(function () {
        Route::get('/', [SubscriptionFeatureController::class, 'index']);
        Route::post('/', [SubscriptionFeatureController::class, 'store']);
        Route::get('/{subscriptionFeature}', [SubscriptionFeatureController::class, 'show']);
        Route::put('/{subscriptionFeature}', [SubscriptionFeatureController::class, 'update']);
        Route::delete('/{subscriptionFeature}', [SubscriptionFeatureController::class, 'destroy']);
    });

    // School Routes
    Route::prefix('school')->group(function () {
        Route::get('/', [SchoolController::class, 'index']);
        Route::post('/', [SchoolController::class, 'store']);
        Route::get('/{School}', [SchoolController::class, 'show']);
        Route::put('/{School}', [SchoolController::class, 'update']);
        Route::delete('/{School}', [SchoolController::class, 'destroy']);
    });

    // School Feature Routes
    Route::prefix('school-feature')->group(function () {
        Route::get('/', [SchoolFeatureController::class, 'index']);
        Route::post('/', [SchoolFeatureController::class, 'store']);
        Route::get('/{schoolFeature}', [SchoolFeatureController::class, 'show']);
        Route::put('/{schoolFeature}', [SchoolFeatureController::class, 'update']);
        Route::delete('/{schoolFeature}', [SchoolFeatureController::class, 'destroy']);
    });

    // Subscription History Routes
    Route::prefix('subscription-history')->group(function () {
        Route::get('/', [SubscriptionHistoryController::class, 'index']);
        Route::post('/', [SubscriptionHistoryController::class, 'store']);
        Route::get('/{subscriptionHistory}', [SubscriptionHistoryController::class, 'show']);
        Route::put('/{subscriptionHistory}', [SubscriptionHistoryController::class, 'update']);
        Route::delete('/{subscriptionHistory}', [SubscriptionHistoryController::class, 'destroy']);
    });

    // Payment Routes
    Route::prefix('payment')->group(function () {
        Route::get('/', [PaymentController::class, 'index']);
        Route::post('/', [PaymentController::class, 'store']);
        Route::get('/{payment}', [PaymentController::class, 'show']);
        Route::put('/{payment}', [PaymentController::class, 'update']);
        Route::delete('/{payment}', [PaymentController::class, 'destroy']);
    });

    Route::middleware('school')->group(function () {
        // Class Group Routes
        Route::prefix('class-group')->group(function () {
            Route::get('/', [ClassGroupController::class, 'index']);
            Route::post('/', [ClassGroupController::class, 'store']);
            Route::get('/{classGroup}', [ClassGroupController::class, 'show']);
            Route::put('/{classGroup}', [ClassGroupController::class, 'update']);
            Route::delete('/{classGroup}', [ClassGroupController::class, 'destroy']);
        });

        // Student Routes
        Route::prefix('student')->group(function () {
            Route::get('/', [StudentController::class, 'index']);
            Route::post('/', [StudentController::class, 'store']);
            Route::get('/csv', [StudentController::class, 'exportStudents']);
            Route::post('/store-via-file', [StudentController::class, 'storeViaFile']);
            Route::get('/{student}', [StudentController::class, 'show']);
            Route::put('/{student}', [StudentController::class, 'update']);
            Route::delete('/{student}', [StudentController::class, 'destroy']);
        });

        // Attendance Late Type Routes
        Route::prefix('check-in-status')->group(function () {
            Route::get('/', [CheckInStatusController::class, 'index']);
            Route::post('/', [CheckInStatusController::class, 'store']);
            Route::get('/{attendanceLateType}', [CheckInStatusController::class, 'show']);
            Route::put('/{attendanceLateType}', [CheckInStatusController::class, 'update']);
            Route::delete('/{attendanceLateType}', [CheckInStatusController::class, 'destroy']);
        });

        // Attendance Routes
        Route::prefix('attendance')->group(function () {
            Route::get('/', [AttendanceController::class, 'index']);
            Route::get('/{attendance}', [AttendanceController::class, 'show']);
            Route::put('/{attendance}', [AttendanceController::class, 'update']);
            Route::delete('/{attendance}', [AttendanceController::class, 'destroy']);
        });

        // Document Routes
        Route::prefix('document')->group(function () {
            Route::get('/', [DocumentController::class, 'index']);
            Route::post('/', [DocumentController::class, 'store']);
            Route::get('/{document}', [DocumentController::class, 'show']);
            Route::put('/{document}', [DocumentController::class, 'update']);
            Route::delete('/{document}', [DocumentController::class, 'destroy']);
        });

        // Absence Permit Type Routes
        Route::prefix('absence-permit-type')->group(function () {
            Route::get('/', [AbsencePermitTypeController::class, 'index']);
            Route::post('/', [AbsencePermitTypeController::class, 'store']);
            Route::get('/{absencePermitType}', [AbsencePermitTypeController::class, 'show']);
            Route::put('/{absencePermitType}', [AbsencePermitTypeController::class, 'update']);
            Route::delete('/{absencePermitType}', [AbsencePermitTypeController::class, 'destroy']);
        });

        // Absence Permit Routes
        Route::prefix('absence-permit')->group(function () {
            Route::get('/', [AbsencePermitController::class, 'index']);
            Route::post('/', [AbsencePermitController::class, 'store']);
            Route::get('/{absencePermit}', [AbsencePermitController::class, 'show']);
            Route::put('/{absencePermit}', [AbsencePermitController::class, 'update']);
            Route::delete('/{absencePermit}', [AbsencePermitController::class, 'destroy']);
        });

        Route::prefix('attendance-window')->group(function () {
            Route::get('/', [AttendanceWindowController::class, 'index']);
            Route::post('/generate-window', [AttendanceWindowController::class, 'generateWindow']);
        });

        Route::prefix('attendance-schedule')->group(function () {
            Route::get('/', [AttendanceScheduleController::class, 'index']);
            Route::post('/show-by-type', [AttendanceScheduleController::class, 'showByType']); 
            Route::post('/', [AttendanceScheduleController::class, 'storeEvent']);
            Route::get('/{attendanceSchedule}', [AttendanceScheduleController::class, 'show']);
            Route::put('/{attendanceSchedule}', [AttendanceScheduleController::class, 'update']);
            Route::delete('/{attendanceSchedule}', [AttendanceScheduleController::class, 'destroy']);
        });

        Route::prefix('day')->group(function() {
            Route::get('/', [DayController::class, 'index']);
            Route::get('/{day}', [DayController::class, 'show']);
            Route::get('/all-by-school', [DayController::class, 'showAllBySchool']);
            Route::put('/{day}', [DayController::class, 'update']);
        });

        Route::prefix('dashboard-statistic')->group(function () {
            Route::get('/static', [DashboardStatistic::class, 'StaticStatistic']);
            Route::post('/daily', [DashboardStatistic::class, 'DailyStatistic']);
        });
    });




});

