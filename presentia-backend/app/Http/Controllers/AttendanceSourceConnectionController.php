<?php

namespace App\Http\Controllers;

use App\Services\AttendanceSourceAuthService;
use App\Services\AttendanceSourceConnectionService;
use App\Services\AttendanceSourceService;
use Illuminate\Http\Request;

class AttendanceSourceConnectionController extends Controller
{
    protected AttendanceSourceConnectionService $attendanceSourceConnectionService;

    public function __construct(){
        $this->attendanceSourceConnectionService = new AttendanceSourceConnectionService(new AttendanceSourceService());
    }

    public function getAllData(Request $request)
    {
        return $this->attendanceSourceConnectionService->getAllData($request);
    }

    public function enroll(Request $request)
    {
        return $this->attendanceSourceConnectionService->enroll($request);
    }

    public function updateAuthProfile(Request $request)
    {
        return $this->attendanceSourceConnectionService->updateAuthProfile($request);
    }

}
