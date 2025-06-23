<?php

namespace App\Exports;

use App\Models\Student;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class AttendancePerClassSheet implements FromCollection, WithTitle, WithMapping, WithStyles, WithStrictNullComparison, WithHeadings
{
    /**
     * Only god knows what happens here.  
     * If you must change it, pray first.  
     * One wrong move, and everything might fall apart.  
     * Proceed with caution. You have been warned.
     * - The previous developer
     */

    protected $classGroup;
    private $attendanceWindows;
    private $lang;
    private $checkInStatuses;
    private $absencePermitTypes;
    private $showCheckInStatus;
    private $checkInAbsenceId;


    public function __construct(
        $classGroup,
        $attendanceWindows,
        $checkInStatuses,
        $absencePermitTypes,
        $checkInAbsenceId,
        $showCheckInStatus = false,
        string $lang = "ID",
    ) {
        $this->classGroup = $classGroup;
        $this->attendanceWindows = $attendanceWindows;
        $this->checkInStatuses = $checkInStatuses;
        $this->absencePermitTypes = $absencePermitTypes;
        $this->checkInAbsenceId = $checkInAbsenceId;
        $this->lang = $lang;
        $this->showCheckInStatus = $showCheckInStatus;
    }

    /**
     * Retrieve attendance data
     */
    public function collection()
    {
        return Student::where('class_group_id', $this->classGroup->id)
            ->with("attendances")
            ->get();
    }

    /**
     * Map each row data (Shift to start from Column 2)
     */
    public function map($student): array
    {

        $filteredAttendancesQuery = $student->attendances()
            ->whereIn('attendance_window_id', $this->attendanceWindows);

        $filteredAttendancesPresentOnly = $filteredAttendancesQuery
            ->where('check_in_status_id', "!=" , $this->checkInAbsenceId)
            ->get()
            ->count();

        $totalAbsenceStudents = count($this->attendanceWindows) - $filteredAttendancesPresentOnly;

        $base = [
            '',
            $student->nis,
            $student->nisn,
            $student->student_name,
            $student->gender == 'male' ? 'Laki-laki' : 'Perempuan',
            ($student->is_active ? 'ya' : 'tidak') ?? 'tidak',
            $filteredAttendancesPresentOnly ?? 0,
        ];

        // Check-in status section (collection filter is fine, since data set per student is small)
        if ($this->checkInStatuses && $this->showCheckInStatus) {
            $checkInStatusData = [];

            $checkInStatusData = array_merge($checkInStatusData, array_map(
                fn($status) => $filteredAttendancesQuery->where('check_in_status_id', $status['id'])->count() ?? 0,
                array_slice($this->checkInStatuses, 1)
            ));
        }

        if (count($this->absencePermitTypes) > 0) {
            $totalAbsenceWithPermit = 0;

            $absencePermitTypeData = array_map(
                function ($permit) use ($filteredAttendancesQuery, &$totalAbsenceWithPermit) {
                    $query = clone $filteredAttendancesQuery;
                    $result = $query
                        ->whereHas('absencePermit', function($q) use ($permit) {
                            $q->where('absence_permit_type_id', $permit["id"]);
                        })
                        ->count(); 
                    $totalAbsenceWithPermit += $result;
                    return $result;
                },
                $this->absencePermitTypes
            );

            // Count 'Tidak Ada Keterangan' (absent with check_in_status -1, no absence_permit)
            $absencePermitTypeData[] = $totalAbsenceStudents - $totalAbsenceWithPermit;

            return array_merge(
                $base,
                [count($this->attendanceWindows) ? $filteredAttendancesPresentOnly / count($this->attendanceWindows) : 0],
                $absencePermitTypeData,
                [$totalAbsenceStudents]
            );
        }

        return array_merge(
            $base,
            [count($this->attendanceWindows) ? $filteredAttendancesPresentOnly / count($this->attendanceWindows) : 0],
            [$totalAbsenceStudents]
        );
    }


    /**
     * Set the sheet title
     */
    public function title(): string
    {
        return $this->classGroup->class_name;
    }

    public function headings(): array
    {
        return [
            [''],
            [''],
        ];
    }

    /**
     * Apply styles (Move headings to Row 2)
     */
    public function styles(Worksheet $sheet)
    {

        $highestColumn = $sheet->getHighestColumn();
        $highestRow = $sheet->getHighestRow();

        // Apply header styles
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 12],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F81BD']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]]
        ];
        $sheet->getStyle("B1:{$highestColumn}2")->applyFromArray($headerStyle);

        // Auto-size columns
        foreach (range('B', $highestColumn) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Alternating row colors
        for ($row = 3; $row <= $highestRow; $row++) {
            $color = ($row % 2 == 0) ? 'E8F6F3' : 'FFFFFF';
            $sheet->getStyle("B{$row}:{$highestColumn}{$row}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $color]],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D3D3D3']]]
            ]);
        }

        // Wrap text in headers
        foreach (range('B', $highestColumn) as $col) {
            $sheet->getStyle("{$col}1:{$col}2")->getAlignment()->setWrapText(true);
        }

        $sheet->fromArray(
            [
                ['', 'NIS', 'NISN', 'Nama Siswa', 'Jenis Kelamin', 'Siswa Aktif', "Total Kehadiran\n(dari total " . count($this->attendanceWindows) . ")"]
            ],
            null,
            'A1'
        );

        foreach (range('B', 'G') as $col) {
            $sheet->getStyle("{$col}3:{$col}1000")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);
            $sheet->mergeCells("{$col}1:{$col}2");
            $sheet->getStyle("{$col}1")->getAlignment()->setHorizontal('center')->setVertical('center'); // Center align
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $checkInStatusCount = count($this->checkInStatuses);
        $absencePermitTypeCount = count($this->absencePermitTypes);

        $startCol = 0;
        $endCol = 8;

        $lastColIndex = Coordinate::stringFromColumnIndex($endCol);
        $sheet->getStyle("{$lastColIndex}2:{$lastColIndex}1000")->getNumberFormat()->setFormatCode('0.00%');
        $sheet->setCellValue("{$lastColIndex}1", "Persentase\nKehadiran (%)");
        $sheet->mergeCells("{$lastColIndex}1:{$lastColIndex}2");
        $sheet->getStyle("{$lastColIndex}1")->getAlignment()->setHorizontal('center')->setVertical('center');
        $sheet->getColumnDimension($lastColIndex)->setAutoSize(true);


        if ($this->showCheckInStatus) {
            $this->colBoundariesUpdate($startCol, $endCol, $startColIndex, $endColIndex, $endCol + 1, $endCol + $checkInStatusCount);

            if ($checkInStatusCount > 0) {
                $sheet->setCellValue("{$startColIndex}1", "Status Kehadiran");
                $sheet->getStyle("{$startColIndex}1")->getAlignment()->setHorizontal('center')->setVertical('center');
                if ($startCol <= $endCol) {
                    $sheet->mergeCells("{$startColIndex}1:{$endColIndex}1");
                    $currentCol = 0;
                    foreach (range($startColIndex, $endColIndex) as $col) {
                        $sheet->setCellValue("{$col}2", $this->checkInStatuses[$currentCol]["status_name"]);
                        $sheet->getStyle("{$col}2")->getAlignment()->setHorizontal('center')->setVertical('center');
                        $sheet->getColumnDimension($col)->setAutoSize(true);
                        $currentCol++;
                    }
                }
            }
        }

        $this->colBoundariesUpdate($startCol, $endCol, $startColIndex, $endColIndex, $endCol + 1, $endCol + $absencePermitTypeCount);

        if ($absencePermitTypeCount > 0) {
            $sheet->setCellValue("{$startColIndex}1", "Jenis Izin Absensi");
            $sheet->getStyle("{$startColIndex}1")->getAlignment()->setHorizontal('center')->setVertical('center');
            if ($startCol <= $endCol) {
                $extEndColAbsencePermitTypeIndex = Coordinate::stringFromColumnIndex($endCol + 1);
                $sheet->mergeCells("{$startColIndex}1:{$extEndColAbsencePermitTypeIndex}1");
                $currentCol = 0;
                foreach (range($startColIndex, $endColIndex) as $col) {
                    $sheet->setCellValue("{$col}2", $this->absencePermitTypes[$currentCol]["permit_name"]);
                    $sheet->getStyle("{$col}2")->getAlignment()->setHorizontal('center')->setVertical('center');
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                    $currentCol++;
                }
            }
        }

        $lastAbsencePermitTypeIndex = Coordinate::stringFromColumnIndex($endCol + 1);
        $sheet->setCellValue("{$lastAbsencePermitTypeIndex}2", "Tidak ada\nKeterangan");
        $sheet->getStyle("{$lastAbsencePermitTypeIndex}2")->getAlignment()->setHorizontal('center')->setVertical('center');
        $sheet->getColumnDimension($lastAbsencePermitTypeIndex)->setAutoSize(true);

        $this->colBoundariesUpdate($startCol, $endCol, $startColIndex, $endColIndex, $endCol + 1, $endCol + 2);

        $sheet->setCellValue("{$endColIndex}1", "Total Ketidakhadiran\n(dari total " . count($this->attendanceWindows) . ")");
        $sheet->mergeCells("{$endColIndex}1:{$endColIndex}2");

        $sheet->insertNewRowBefore(1, 1);
    }

    protected function colBoundariesUpdate(&$startCol, &$endCol, &$startColIndex, &$endColIndex, $formulaStartCol, $formulaEndCol)
    {
        $startCol = $formulaStartCol;
        $endCol = $formulaEndCol;
        $startColIndex = Coordinate::stringFromColumnIndex($startCol);
        $endColIndex = Coordinate::stringFromColumnIndex($endCol);
    }
}