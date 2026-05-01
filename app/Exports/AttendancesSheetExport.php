<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AttendancesSheetExport implements FromCollection, WithEvents, WithStyles, WithTitle
{
    private const SUMMARY_FILL = 'F3F4F6';
    private const BODY_ZEBRA_FILL = 'F9FAFB';

    protected int $presentCount;

    protected int $leaveCount;

    protected int $sickCount;

    protected int $absentCount;

    protected string $format;

    public function __construct(protected Collection $attendances, protected array $filters = [])
    {
        $this->presentCount = (int) ($this->filters['present_count'] ?? $this->attendances->filter(fn ($attendance) => in_array($attendance->status, ['present', 'late'], true))->count());
        $this->leaveCount = (int) ($this->filters['leave_count'] ?? $this->attendances->where('status', 'leave')->count());
        $this->sickCount = (int) ($this->filters['sick_count'] ?? $this->attendances->where('status', 'sick')->count());
        $this->absentCount = (int) ($this->filters['absent_count'] ?? $this->attendances->where('status', 'absent')->count());
        $this->format = (string) ($this->filters['format'] ?? 'xlsx');
    }

    public function collection(): Collection
    {
        $rows = $this->format === 'csv'
            ? collect([
                ['Total Hadir: '.$this->presentCount],
                ['Total Izin: '.$this->leaveCount],
                ['Total Sakit: '.$this->sickCount],
                ['Total Alpha: '.$this->absentCount],
                ['Nama Karyawan', 'Tanggal', 'Status', 'Jam Masuk', 'Jam Keluar', 'Lokasi'],
            ])
            : collect([
                ['Summary Absensi Harian', null, null, null, null, null],
                ['Total Hadir: '.$this->presentCount, null, null, null, null, null],
                ['Total Izin: '.$this->leaveCount, null, null, null, null, null],
                ['Total Sakit: '.$this->sickCount, null, null, null, null, null],
                ['Total Alpha: '.$this->absentCount, null, null, null, null, null],
                [null, null, null, null, null, null],
                ['Nama Karyawan', 'Tanggal', 'Status', 'Jam Masuk', 'Jam Keluar', 'Lokasi'],
            ]);

        return $rows->concat($this->attendances->map(function ($attendance) {
            $attendanceLog = $attendance->attendanceLog;
            $workLocationName = $attendanceLog?->workLocation?->name ?? $attendance->employee?->workLocation?->name ?? '—';

            return [
                $attendance->employee?->name ?? '—',
                optional($attendance->date)->format('Y-m-d') ?? (string) $attendance->date,
                $this->formatStatus($attendance->status),
                $attendance->check_in ?? '—',
                $attendance->check_out ?? '—',
                $workLocationName,
            ];
        }));
    }

    public function title(): string
    {
        return 'Absensi';
    }

    public function styles(Worksheet $sheet): array
    {
        if ($this->format === 'csv') {
            return [];
        }

        return [
            1 => $this->summaryRowStyle(13),
            2 => $this->summaryRowStyle(),
            3 => $this->summaryRowStyle(),
            4 => $this->summaryRowStyle(),
            5 => $this->summaryRowStyle(),
            7 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '1F2937'],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        if ($this->format === 'csv') {
            return [];
        }

        return [
            AfterSheet::class => function ($event): void {
                $worksheet = $event->sheet->getDelegate();
                $highestColumn = $worksheet->getHighestColumn();
                $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);
                $highestRow = $worksheet->getHighestRow();
                $highestCell = $highestColumn.$highestRow;

                $worksheet->mergeCells('A1:D1');
                $worksheet->freezePane('A7');
                $worksheet->getStyle('A1:'.$highestCell)->getAlignment()->setWrapText(true);
                $worksheet->getStyle('A1:'.$highestCell)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

                $worksheet->getStyle('A1:F5')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $worksheet->getStyle('A1:F5')->getBorders()->getAllBorders()->getColor()->setRGB('D1D5DB');
                $worksheet->getStyle('A7:F'.$highestRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $worksheet->getStyle('A7:F'.$highestRow)->getBorders()->getAllBorders()->getColor()->setRGB('D1D5DB');

                if ($highestRow >= 8) {
                    for ($row = 8; $row <= $highestRow; $row++) {
                        if (($row - 8) % 2 === 0) {
                            $worksheet->getStyle('A'.$row.':F'.$row)
                                ->getFill()
                                ->setFillType(Fill::FILL_SOLID)
                                ->getStartColor()
                                ->setRGB(self::BODY_ZEBRA_FILL);
                        }
                    }
                }

                for ($columnIndex = 1; $columnIndex <= $highestColumnIndex; $columnIndex++) {
                    $worksheet->getColumnDimension(Coordinate::stringFromColumnIndex($columnIndex))->setAutoSize(true);
                }
            },
        ];
    }

    protected function formatStatus(?string $status): string
    {
        return match ($status) {
            'present' => 'Hadir',
            'late' => 'Terlambat',
            'leave' => 'Izin',
            'sick' => 'Sakit',
            'absent' => 'Alpha',
            default => '—',
        };
    }

    protected function summaryRowStyle(int $fontSize = 11): array
    {
        return [
            'font' => [
                'bold' => true,
                'size' => $fontSize,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => self::SUMMARY_FILL],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ];
    }
}