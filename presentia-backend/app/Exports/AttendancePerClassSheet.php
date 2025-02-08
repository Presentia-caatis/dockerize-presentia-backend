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

    protected $classGroup;
    private $attendanceWindows;
    private $lang;
    private $checkInStatuses;
    private $absencePermitTypes;


    public function __construct(
        $classGroup,
        $attendanceWindows,
        $checkInStatuses,
        $absencePermitTypes,
        string $lang = "ID",
    ) {
        $this->classGroup = $classGroup;
        $this->attendanceWindows = $attendanceWindows;
        $this->checkInStatuses = $checkInStatuses;
        $this->absencePermitTypes = $absencePermitTypes;
        $this->lang = $lang;
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
        // Filter only attendances that match the given attendance windows
        $filteredAttendances = $student->attendances->filter(function ($attendance) {
            return in_array($attendance->attendance_window_id, $this->attendanceWindows);
        });

        // Count only the filtered attendances
        $totalAttendanceStudents = $filteredAttendances->count();

        $base = [
            '',
            $student->nis,
            $student->nisn,
            $student->student_name,
            $student->gender == 'male' ? 'Laki-laki' : 'Perempuan',
            ($student->is_active ? 'ya' : 'tidak') ?? 'tidak',
            $totalAttendanceStudents ?? 0, // Use the count of filtered attendances
        ];


        $checkInStatusData[] = $filteredAttendances->where('check_in_status_id', -1)->count()
            + (count($this->attendanceWindows) - $totalAttendanceStudents) ?? 0;

        $checkInStatusData = array_merge($checkInStatusData, array_map(
            fn($status) =>
            $filteredAttendances->where('check_in_status_id', $status['id'])->count() ?? 0,
            array_slice($this->checkInStatuses, 1)
        ));

        // Process absence permit types using only filtered attendances
        $absencePermitTypeData = array_map(
            fn($permit) =>
            $filteredAttendances->where('check_in_status_id', -1)
                ->filter(fn($attendance) => $attendance->absencePermits->where('absence_permit_type_id', $permit['id'])->isNotEmpty())
                ->count() ?? 0,
            $this->absencePermitTypes
        );

        $absencePermitTypeData[] = $filteredAttendances->where('check_in_status_id', -1)
            ->whereNull('absence_permit_type_id')->count()
            + (count($this->attendanceWindows) - $totalAttendanceStudents) ?? 0;

        return array_merge($base, $checkInStatusData, $absencePermitTypeData, [$totalAttendanceStudents / count($this->attendanceWindows)]);
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
            [''], // Empty row 1
            [''], // Empty row 2
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

        $startColCheckInStatus = 8;
        $endColCheckInStatus = $startColCheckInStatus + $checkInStatusCount - 1;

        $startColCheckInStatusIndex = Coordinate::stringFromColumnIndex($startColCheckInStatus);
        $endColCheckInStatusIndex = Coordinate::stringFromColumnIndex($endColCheckInStatus);

        if ($checkInStatusCount > 0) {
            $sheet->setCellValue("{$startColCheckInStatusIndex}1", "Status Kehadiran");
            $sheet->getStyle("{$startColCheckInStatusIndex}1")->getAlignment()->setHorizontal('center')->setVertical('center');
            if ($startColCheckInStatus <= $endColCheckInStatus) {
                $sheet->mergeCells("{$startColCheckInStatusIndex}1:{$endColCheckInStatusIndex}1");
                $currentCol = 0;
                foreach (range($startColCheckInStatusIndex, $endColCheckInStatusIndex) as $col) {
                    $sheet->setCellValue("{$col}2", $this->checkInStatuses[$currentCol]["status_name"]);
                    $sheet->getStyle("{$col}2")->getAlignment()->setHorizontal('center')->setVertical('center');
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                    $currentCol++;
                }
            }
        }

        $startColAbsencePermitType = $endColCheckInStatus + 1;
        $endColAbsencePermitType = $startColAbsencePermitType + $absencePermitTypeCount - 1;

        $startColAbsencePermitTypeIndex = Coordinate::stringFromColumnIndex($startColAbsencePermitType);
        $endColAbsencePermitTypeIndex = Coordinate::stringFromColumnIndex($endColAbsencePermitType);

        if ($absencePermitTypeCount > 0) {
            $sheet->setCellValue("{$startColAbsencePermitTypeIndex}1", "Jenis Izin Absensi");
            $sheet->getStyle("{$startColAbsencePermitTypeIndex}1")->getAlignment()->setHorizontal('center')->setVertical('center');
            if ($startColAbsencePermitType <= $endColAbsencePermitType) {
                $extEndColAbsencePermitTypeIndex = Coordinate::stringFromColumnIndex($endColAbsencePermitType + 1);
                $sheet->mergeCells("{$startColAbsencePermitTypeIndex}1:{$extEndColAbsencePermitTypeIndex}1");
                $currentCol = 0;
                foreach (range($startColAbsencePermitTypeIndex, $endColAbsencePermitTypeIndex) as $col) {
                    $sheet->setCellValue("{$col}2", $this->absencePermitTypes[$currentCol]["permit_name"]);
                    $sheet->getStyle("{$col}2")->getAlignment()->setHorizontal('center')->setVertical('center');
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                    $currentCol++;
                }
                $lastAbsencePermitTypeIndex = Coordinate::stringFromColumnIndex($endColAbsencePermitType + 1);
                $sheet->setCellValue("{$lastAbsencePermitTypeIndex}2", "Tidak ada\nKeterangan");
                $sheet->getStyle("{$lastAbsencePermitTypeIndex}2")->getAlignment()->setHorizontal('center')->setVertical('center');
                $sheet->getColumnDimension($lastAbsencePermitTypeIndex)->setAutoSize(true);
            }
        }

        $lastColIndex = Coordinate::stringFromColumnIndex($endColAbsencePermitType + 2);
        $sheet->getStyle("{$lastColIndex}2:{$lastColIndex}1000")->getNumberFormat()->setFormatCode('0.00%');
        $sheet->setCellValue("{$lastColIndex}1", "Persentase\nKehadiran (%)");
        $sheet->mergeCells("{$lastColIndex}1:{$lastColIndex}2");
        $sheet->getStyle("{$lastColIndex}1")->getAlignment()->setHorizontal('center')->setVertical('center');
        $sheet->getColumnDimension($lastColIndex)->setAutoSize(true);

        $sheet->insertNewRowBefore(1, 1);

    }
}