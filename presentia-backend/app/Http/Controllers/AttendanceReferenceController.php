<?php

namespace App\Http\Controllers;

use App\Services\AttendanceReferenceService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AttendanceReferenceController extends Controller
{
    protected AttendanceReferenceService $attendanceReferenceService;

    public function __construct(AttendanceReferenceService $attendanceReferenceService)
    {
        $this->attendanceReferenceService = $attendanceReferenceService;
    }

    /**
     * Handle the request to get combined attendance reference data.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getAll(Request $request): JsonResponse
    {
        $data = $this->attendanceReferenceService->getCombinedReferences($request);

        return response()->json([
            'status' => 'success',
            'message' => 'Attendance references retrieved successfully.',
            'data' => $data
        ]);
    }
}