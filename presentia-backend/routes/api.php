<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API
|--------------------------------------------------------------------------
|
| HERE IS WHERE YOU CAN REGISTER API  for your application. These
|  are loaded by the RouteServiceProvider within a group which
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
    PermissionController,
    ForgotPasswordController,
    EmailVerificationController,
    AttendanceSourceController,
    AttendanceSourceConnectionController,
    AttendanceSourceAuthController,
    AttendanceReferenceController,
    SchoolInvitationController,
    SemesterController
};

//AUTH API
Route::controller(SocialiteController::class)->group(function () {
    Route::get('auth-google', 'googleLogin');
    Route::get('auth-google-callback', 'googleAuthentication');
});

Route::post('forgot-password', [ForgotPasswordController::class, 'forgotPassword']);
Route::post('reset-password', [ForgotPasswordController::class, 'resetPassword']);
Route::post('email/verify/send', [EmailVerificationController::class, 'sendVerificationEmail'])->middleware('auth:sanctum');
Route::get('email/verify/{id}/{hash}', [EmailVerificationController::class, 'verifyEmail'])->name('verification.verify')->middleware(['signed', 'throttle:6,1']);
Route::post('register', [AuthController::class, 'register'])->name('register');
Route::post('login', [AuthController::class, 'login'])->name('login');
Route::middleware(['auth:sanctum'])->post('logout', [AuthController::class, 'logout'])->name('logout');

//ADMS API
Route::post('attendance', [AttendanceController::class, 'store'])->middleware('valid-adms');

//USER API
Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::prefix('time')->group(function () {
        Route::get('/current', [TimeController::class, 'getCurrentTime']);
    });

    //SCHOOL INVITATION
    Route::prefix('school-invitation')->group(function () {
        Route::get('/receiver', [SchoolInvitationController::class, 'getByReceiver']);
        Route::post('/respond/{id}', [SchoolInvitationController::class, 'respondInvitation'])->middleware("school:true");

        Route::middleware(['permission:manage_school_users', 'school'])->group(function () {
            Route::get('/', [SchoolInvitationController::class, 'getAll']);
            Route::get('/sender', [SchoolInvitationController::class, 'getBySender']);
            Route::post('/', [SchoolInvitationController::class, 'store']);
            Route::put('/{id}', [SchoolInvitationController::class, 'update']);
            Route::delete('/{id}', [SchoolInvitationController::class, 'destroy']);
        });
    });

    // ROLE
    Route::prefix('role')->group(function () {
        Route::middleware(["permission:manage_school_users", "school"])->group(function () {
            Route::get('/school', [RoleController::class, 'getSchoolRoles']);
            Route::post('/user/assign', [RoleController::class, 'assignToUser']);
        });

        Route::middleware('role:super_admin')->group(function () {
            Route::get('/', [RoleController::class, 'getAll']);
            Route::post('/', [RoleController::class, 'store']);
            Route::get('/{id}', [RoleController::class, 'getById']);
            Route::put('/{id}', [RoleController::class, 'update']);
            Route::delete('/destroy-all', [RoleController::class, 'destroyAll']);
            Route::delete('/{id}', [RoleController::class, 'destroy']);
            Route::post('/user/remove/{id}', [RoleController::class, 'removeRoleFromUser']);
        });
    });

    // PERMISSION
    Route::middleware('role:super_admin')->prefix('permission')->group(function () {
        Route::get('/', [PermissionController::class, 'getAll']);
        Route::post('/', [PermissionController::class, 'store']);
        Route::get('/{id}', [PermissionController::class, 'getById']);
        Route::put('/{id}', [PermissionController::class, 'update']);
        Route::delete('/destroy-all', [PermissionController::class, 'destroyAll']);
        Route::delete('/{id}', [PermissionController::class, 'destroy']);
        Route::post('/role/assign', [PermissionController::class, 'assignToRole']);
        Route::post('/role/remove', [PermissionController::class, 'removeFromRole']);
    });

    // USER
    Route::prefix('user')->group(function () {
        // Allow all authenticated users to access get-by-token
        Route::get('/get-by-token', [UserController::class, 'getByToken']);
        Route::post('/school/assign-via-token', [UserController::class, 'assignToSchoolViaToken']);
        Route::put('/', [UserController::class, 'update']);

        // Allow only super_admin and school_admin to manage link-to-school
        Route::middleware(['permission:manage_school_users', 'school'])->group(function () {
            Route::get('/school', [UserController::class, 'getSchoolUsers']);
            Route::post('/school/assign/{id}', [UserController::class, 'assignToSchool']);
            Route::post('/school/remove/{id}', [UserController::class, 'removeFromSchool']);
            Route::get('/unassignedUsers', [UserController::class, 'getUnassignedUsers']);
        });

        // RESTRICT ALL OTHER  to users with 'super_admin' role
        Route::middleware('role:super_admin')->group(function () {
            Route::get('/', [UserController::class, 'getAll']);
            Route::post('/', [UserController::class, 'store']);
            Route::get('/{id}', [UserController::class, 'getById']);
            Route::delete('/{id}', [UserController::class, 'destroy']);
        });
    });

    // FEATURE
    Route::prefix('feature')->group(function () {
        Route::get('/', [FeatureController::class, 'getAll']);
        Route::get('/{id}', [FeatureController::class, 'getById']);

        Route::middleware('role:super_admin')->group(function () {
            Route::post('/', [FeatureController::class, 'store']);
            Route::put('/{id}', [FeatureController::class, 'update']);
            Route::delete('/{id}', [FeatureController::class, 'destroy']);
        });
    });

    // SUBSCRIPTION
    Route::prefix('subscription-plan')->group(function () {
        Route::get('/', [SubscriptionPlanController::class, 'getAll']);
        Route::get('/{id}', [SubscriptionPlanController::class, 'getById']);

        Route::middleware('role:super_admin')->group(function () {
            Route::post('/', [SubscriptionPlanController::class, 'store']);
            Route::put('/{id}', [SubscriptionPlanController::class, 'update']);
            Route::delete('/{id}', [SubscriptionPlanController::class, 'destroy']);
        });
    });

    // SUBSCRIPTION FEATURE
    Route::prefix('subscription-feature')->group(function () {
        Route::get('/', [SubscriptionFeatureController::class, 'getAll']);
        Route::get('/{id}', [SubscriptionFeatureController::class, 'getById']);

        Route::middleware('role:super_admin')->group(function () {
            Route::post('/', [SubscriptionFeatureController::class, 'store']);
            Route::put('/{id}', [SubscriptionFeatureController::class, 'update']);
            Route::delete('/{id}', [SubscriptionFeatureController::class, 'destroy']);
        });
    });

    // SCHOOL
    Route::prefix('school')->group(function () {
        Route::get('/', [SchoolController::class, 'getAll']);

        Route::middleware('role:super_admin')->group(function () {
            Route::post('/', [SchoolController::class, 'store']);
            Route::put('/task-scheduler-toogle/{id}', [SchoolController::class, 'taskSchedulerToogle']);
            Route::put('/{id}', [SchoolController::class, 'update']);
            Route::delete('/{id}', [SchoolController::class, 'destroy']);
        });
    });

    Route::middleware(['school'])->prefix('semester')->group(function () {
        Route::get('/', [SemesterController::class, 'getAll']);
        Route::post('/', [SemesterController::class, 'store']);
        Route::get('/current', [SemesterController::class, 'getByCurrentTime']);
        Route::put('/is-active-toogle/{id}', [SemesterController::class, 'isActiveToogle']);
        Route::get('{id}', [SemesterController::class, 'getById']);
        Route::put('{id}', [SemesterController::class, 'update']);
        Route::delete('{id}', [SemesterController::class, 'destroy']);
    });

    // JOB
    Route::middleware('role:super_admin')->prefix('job')->group(function () {
        Route::get('/failed', [JobController::class, 'failedJobs']);
        Route::post('/retry/{id}', [JobController::class, 'retryJob']);
        Route::post('/flush', [JobController::class, 'flushJobs']);
        Route::post('/restart', [JobController::class, 'restartQueue']);
        Route::get('/pending', [JobController::class, 'pendingJobs']);
        Route::delete('/pending', [JobController::class, 'flushPendingJobs']);
    });

    // SCHOOL DATA
    Route::middleware(['school', 'permission:basic_school'])->group(function () {

        // ATTENDANCE SOURCE
        Route::prefix('attendance-source')->group(function () {

            Route::prefix('auth')->group(function () {
                Route::post('/login', [AttendanceSourceAuthController::class, 'login']);
            });

            Route::middleware('permission:manage_schools')->group(function () {
                Route::prefix('connection')->group(function () {
                    Route::get('/', [AttendanceSourceConnectionController::class, 'getAll']);
                    Route::post('/enroll', [AttendanceSourceConnectionController::class, 'enroll']);
                    Route::put('/update-auth-profile', [AttendanceSourceConnectionController::class, 'updateAuthProfile']);
                });

                Route::get('/', [AttendanceSourceController::class, 'getAll'])->middleware('role:super_admin');
                Route::get('/get-data', [AttendanceSourceController::class, 'getData']);
                Route::get('/{id}', [AttendanceSourceController::class, 'getById'])->middleware('role:super_admin');
                Route::post('/', [AttendanceSourceController::class, 'store']);
                Route::put('/{id}', [AttendanceSourceController::class, 'update']);
                Route::delete('/{id}', [AttendanceSourceController::class, 'destroy']);
            });
        });

        // CLASS GROUP
        Route::prefix('class-group')->group(function () {
            Route::get('/{id}', [ClassGroupController::class, 'getById']);

            Route::middleware('permission:manage_schools')->group(function () {
                Route::post('/', [ClassGroupController::class, 'store']);
                Route::put('/{id}', [ClassGroupController::class, 'update']);
                Route::delete('/{id}', [ClassGroupController::class, 'destroy']);
            });
        });

        //DASHBOARD STATISTIC
        Route::prefix('dashboard-statistic')->group(function () {
            Route::get('/static', [DashboardStatistic::class, 'StaticStatistic']);
            Route::get('/daily', [DashboardStatistic::class, 'DailyStatistic']);
        });

        // STUDENT
        Route::prefix('student')->group(function () {
            Route::get('/{id}', [StudentController::class, 'getById']);

            Route::get('/csv', [StudentController::class, 'exportStudents'])->middleware('role:super_admin');

            Route::middleware('permission:manage_students')->group(function () {
                Route::post('/', [StudentController::class, 'store']);
                Route::post('/store-via-file', [StudentController::class, 'storeViaFile']);
                Route::put('/{id}', [StudentController::class, 'update']);
                Route::delete('/{id}', [StudentController::class, 'destroy']);
            });
        });

        // ATTENDANCE
        Route::prefix('attendance')->group(function () {
            Route::post('/manual/nis', [AttendanceController::class, 'storeManualAttendanceNisOnly']);
            Route::get('/export', [AttendanceController::class, 'exportAttendance']);

            Route::middleware('permission:manage_attendance')->group(function () {    
                Route::put('/adjust', [AttendanceController::class, 'adjustAttendance']);
                Route::post('/file', [AttendanceController::class, 'storeFromFile']);
                Route::post('/manual', [AttendanceController::class, 'storeManualAttendance']);
                Route::post('/mark-absent', [AttendanceController::class, 'markAbsentStudents']);
                Route::delete('/clear-records/{attendanceWindowId}', [AttendanceController::class, 'clearAttendanceRecords']);
                Route::get('/{id}', [AttendanceController::class, 'getById']);
                Route::put('/{id}', [AttendanceController::class, 'update']);
                Route::delete('/{id}', [AttendanceController::class, 'destroy']);
            });

        });

        Route::middleware('permission:manage_schools')->group(function () {

            // ATTENDANCE LATE TYPE
            Route::prefix('check-in-status')->group(function () {
                Route::get('/', [CheckInStatusController::class, 'getAll'])->withoutMiddleware('permission:manage_schools');
                Route::post('/', [CheckInStatusController::class, 'store']);
                Route::get('/{id}', [CheckInStatusController::class, 'getById']);
                Route::put('/{id}', [CheckInStatusController::class, 'update']);
                Route::delete('/{id}', [CheckInStatusController::class, 'destroy']);
            });

            // DOCUMENT
            Route::prefix('document')->group(function () {
                Route::get('/', [DocumentController::class, 'getAll']);
                Route::post('/', [DocumentController::class, 'store']);
                Route::get('/{id}', [DocumentController::class, 'getById']);
                Route::put('/{id}', [DocumentController::class, 'update']);
                Route::delete('/{id}', [DocumentController::class, 'destroy']);
            });

            // ABSENCE PERMIT TYPE
            Route::prefix('absence-permit-type')->group(function () {
                Route::get('/', [AbsencePermitTypeController::class, 'getAll'])->withoutMiddleware('permission:manage_schools');
                Route::post('/', [AbsencePermitTypeController::class, 'store']);
                Route::get('/{id}', [AbsencePermitTypeController::class, 'getById']);
                Route::put('/{id}', [AbsencePermitTypeController::class, 'update']);
                Route::delete('/{id}', [AbsencePermitTypeController::class, 'destroy']);
            });

            // ABSENCE PERMIT
            Route::prefix('absence-permit')->group(function () {
                Route::get('/', [AbsencePermitController::class, 'getAll']);
                Route::post('/', [AbsencePermitController::class, 'store']);
                Route::get('/{id}', [AbsencePermitController::class, 'getById']);
                Route::put('/{id}', [AbsencePermitController::class, 'update']);
                Route::delete('/{id}', [AbsencePermitController::class, 'destroy']);
            });

            // ATTENDANCE REFERENCES (Combined)
            Route::get('/attendance-references', [AttendanceReferenceController::class, 'getAll']);

            // ATTENDANCE WINDOW
            Route::prefix('attendance-window')->group(function () {
                Route::get('/', [AttendanceWindowController::class, 'getAll']);
                Route::get('/get-utc', [AttendanceWindowController::class, 'getAllInUtcFormat']);
                Route::post('/generate-window', [AttendanceWindowController::class, 'generateWindow'])->middleware('role:super_admin|school_coadmin|school_admin');
                Route::get('/{id}', [AttendanceWindowController::class, 'getById']);
                Route::put('/{id}', [AttendanceWindowController::class, 'update']);
                Route::delete('/{id}', [AttendanceWindowController::class, 'destroy']);
            });


            // ATTENDANCE SCHEDULE
            Route::prefix('attendance-schedule')->group(function () {
                Route::get('/', [AttendanceScheduleController::class, 'getAll'])->withoutMiddleware('permission:manage_schools');;
                Route::put('/assign-to-day/{id}', [AttendanceScheduleController::class, 'assignToDay']);
                Route::get('/{id}', [AttendanceScheduleController::class, 'getById']);
                Route::put('/{id}', [AttendanceScheduleController::class, 'update']);
                Route::delete('/{id}', [AttendanceScheduleController::class, 'destroy']);
            });

            //EVENT
            Route::prefix('event')->group(function () {
                Route::post('/', [EventController::class, 'store']);
                Route::delete('/{id}', [EventController::class, 'destroy']);
            });

            // DAY
            Route::prefix('day')->group(function () {
                Route::get('/', [DayController::class, 'getAll']);
                Route::get('/{id}', [DayController::class, 'getById']);
                Route::put('/{id}', [DayController::class, 'update']);
            });
        });
    });
});

// PUBLIC SCHOOL DATA
Route::middleware(['school:true'])->group(function () {
    Route::get('student', [StudentController::class, 'getAll']);
    Route::get('class-group', [ClassGroupController::class, 'getAll']);
    Route::get('attendance', [AttendanceController::class, 'getAll']);
});

Route::get('school/{id}', [SchoolController::class, 'getById']);
