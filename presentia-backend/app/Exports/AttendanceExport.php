<?php

namespace App\Exports;

use App\Models\ClassGroup;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;

class AttendanceExport implements WithMultipleSheets
{
    use Exportable;

    protected $startDate;
    protected $endDate;
    protected $classGroup = 'all';
    
    public function __construct(string $startDate, string $endDate, string $classGroup)
    {
        $this->startDate = $startDate;
        $this->endDate= $endDate;
        $this->classGroup = $classGroup;
    }

    public function sheets(): array
    {
        $sheets = [];

        $classGroup = $this->classGroup == 'all' ? ClassGroup::all() : $this->classGroup;
        
        

        foreach () {
            ;
        }

        return $sheets;
    }
    
}
