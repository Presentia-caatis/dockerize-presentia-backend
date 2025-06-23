<?php

namespace App\Exports;

use App\Models\AbsencePermitType;
use App\Models\AttendanceWindow;
use App\Models\CheckInStatus;
use App\Models\ClassGroup;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class AttendanceExport implements WithMultipleSheets
{
    use Exportable;

    protected $startDate;
    protected $endDate;
    protected $classGroup = 'all';

    public function __construct(string $startDate, string $endDate, string $classGroup)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->classGroup = $classGroup;
    }

    public function sheets(): array
    {
        $sheets = [];
        $checkInStatusesQuery=CheckInStatus::orderBy('late_duration')->where('is_active', true);

        $classGroups = $this->classGroup == 'all'
            ? ClassGroup::all()
            : ClassGroup::whereIn('id', explode(',', $this->classGroup))->get();
        $attendanceWindows = AttendanceWindow::whereBetween('date', [$this->startDate, $this->endDate])->pluck('id')->toArray();
        $checkInStatuses =$checkInStatusesQuery->get()->toArray();
        $checkInAbsenceId = $checkInStatusesQuery->where('late_duration', -1)->firstOrFail()->id;
        $absencePermitTypes = AbsencePermitType::orderBy('permit_name')->where('is_active', true)->get()->toArray(); 

        foreach ($classGroups as $classGroup) {
            $sheets[] = new AttendancePerClassSheet($classGroup, $attendanceWindows, $checkInStatuses,$absencePermitTypes, $checkInAbsenceId) ;
        }

        return $sheets;
    }

}
