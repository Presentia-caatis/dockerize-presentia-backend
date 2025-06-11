<?php

namespace App\Services;

use App\Models\CheckInStatus;
use App\Models\AbsencePermitType;
use App\Models\CheckOutStatus; // Assuming this model might exist
use Illuminate\Http\Request;

class AttendanceReferenceService
{
    /**
     * Gathers all check-in statuses, absence permit types,
     * and check-out statuses (if available).
     *
     * @param Request $request
     * @return array
     */
    public function getCombinedReferences(Request $request): array
    {
        $checkInStatuses = CheckInStatus::where('late_duration', "!=", -1)->get();
        $absencePermitTypes = AbsencePermitType::all();
        $checkOutStatuses = CheckOutStatus::where('late_duration', "!=", -1)->get();

        return [
            'check_in_statuses' => $checkInStatuses,
            'absence_permit_types' => $absencePermitTypes,
            'check_out_statuses' => $checkOutStatuses,
        ];
    }
}