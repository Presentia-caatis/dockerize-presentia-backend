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
        $checkInStatuses = CheckInStatus::where('late_duration', "!=", -1)
            ->get(['id', 'status_name'])
            ->map(function ($status) {
                return ['id' => $status->id, 'name' => $status->status_name, 'type' => 'check_in_status'];
            });

        $absencePermitTypes = AbsencePermitType::all(['id', 'permit_name'])
            ->map(function ($permit) {
                return ['id' => $permit->id, 'name' => $permit->permit_name, 'type' => 'absence_permit_type'];
            });

        $checkOutStatuses = CheckOutStatus::where('late_duration', "!=", -1)
            ->get(['id', 'status_name'])
            ->map(function ($status) {
                return ['id' => $status->id, 'name' => $status->status_name, 'type' => 'check_out_status'];
            });

        return $checkInStatuses->concat($absencePermitTypes)->concat($checkOutStatuses)->all();
    }
}