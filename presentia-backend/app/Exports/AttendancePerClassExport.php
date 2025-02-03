<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;

class AttendancePerClassExport implements FromCollection
{
    protected $startDate;
    protected $endDate;
    protected $classGroup = 'all';
    
    public function __construct(string $startDate, string $endDate, string $classGroup)
    {
        $this->startDate = $startDate;
        $this->endDate= $endDate;
        $this->classGroup = $classGroup;
    }
}
