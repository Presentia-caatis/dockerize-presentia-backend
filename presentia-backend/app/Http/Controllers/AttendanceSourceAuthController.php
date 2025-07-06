<?php

namespace App\Http\Controllers;

use App\Services\AttendanceSourceAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AttendanceSourceAuthController extends Controller
{
    protected AttendanceSourceAuthService $attendanceSourceAuthService;

    public function __construct(AttendanceSourceAuthService $attendanceSourceAuthService){
        $this->attendanceSourceAuthService = $attendanceSourceAuthService;
    }

    public function login(Request $request){
        return $this->attendanceSourceAuthService->login($request);
    }
}
