<?php

namespace App\Exports;

use App\Models\Employee;
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

class LeavesEmployeeSheetExport implements FromCollection, WithEvents, WithStyles, WithTitle
{
    private const SUMMARY_FILL = 'F3F4F6';
    private const BODY_ZEBRA_FILL = 'F9FAFB';

    protected string $format;

    public function __construct(protected Employee $employee, protected Collection $leaves, protected array $summary = [], array $filters = [])
    {
        $this->summary = [
            'pending_count' => (int) ($summary['pending_count'] ?? 0),
            'pending_days' => (int) ($summary['pending_days'] ?? 0),
            'approved_count' => (int) ($summary['approved_count'] ?? 0),
            'approved_days' => (int) ($summary['approved_days'] ?? 0),
            'rejected_count' => (int) ($summary['rejected_count'] ?? 0),
            'rejected_days' => (int) ($summary['rejected_days'] ?? 0),
            'total_days' => (int) ($summary['total_days'] ?? 0),
        ];
        $this->format = (string) ($filters['format'] ?? 'xlsx');
    }

    public function collection(): Collection
    {
        $rows = $this->format === 'csv'
            ? collect([
                ['Pending: '.$this->summary['pending_count'].' requests / '.$this->summary['pending_days'].' hari'],
                ['Approved: '.$this->summary['approved_count'].' requests / '.$this->summary['approved_days'].' hari'],
                ['Rejected: '.$this->summary['rejected_count'].' requests / '.$this->summary['rejected_days'].' hari'],
                ['Total Hari Cuti: '.$this->summary['total_days']],
                ['Jenis Cuti', 'Tanggal Mulai', 'Tanggal Selesai', 'Durasi (hari)', 'Status', 'Alasan'],
            ])
            : collect([
                ['Summary Cuti '.$this->employee->name, null, null, null, null, null],
                ['Pending: '.$this->summary['pending_count'].' requests / '.$this->summary['pending_days'].' hari', null, null, null, null, null],
                ['Approved: '.$this->summary['approved_count'].' requests / '.$this->summary['approved_days'].' hari', null, null, null, null, null],
                ['Rejected: '.$this->summary['rejected_count'].' requests / '.$this->summary['rejected_days'].' hari', null, null, null, null, null],
                ['Total Hari Cuti: '.$this->summary['total_days'], null, null, null, null, null],
                [null, null, null, null, null, null],
                ['Jenis Cuti', 'Tanggal Mulai', 'Tanggal Selesai', 'Durasi (hari)', 'Status', 'Alasan'],
            ]);

        return $rows->concat($this->leaves->map(function ($leave) {
            return [
                $leave->leaveType?->name ?? '—',
                optional($leave->start_date)->format('Y-m-d') ?? (string) $leave->start_date,
                optional($leave->end_date)->format('Y-m-d') ?? (string) $leave->end_date,
                (int) $leave->duration,
                $this->formatStatus($leave->status),
                $leave->reason,
            ];
        }));
    }

    public function title(): string
    {
        return 'Cuti Karyawan';
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

                if ($highestRow >= 7) {
                    $worksheet->getStyle('A7:F'.$highestRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                    $worksheet->getStyle('A7:F'.$highestRow)->getBorders()->getAllBorders()->getColor()->setRGB('D1D5DB');
                }

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

    protected function formatStatus(?string $status): string
    {
        return match ($status) {
            'pending' => 'Pending',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            default => '—',
        };
    }

}