<?php

namespace App\Exports;

use App\Models\Employee;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LeavesEmployeeAggregationDetailSheetExport implements FromCollection, WithEvents, WithHeadings, WithStyles, WithTitle
{
    private const BODY_ZEBRA_FILL = 'F9FAFB';

    public function __construct(protected Employee $employee, protected Collection $leaves)
    {
    }

    public function collection(): Collection
    {
        return $this->leaves->map(function ($leave) {
            return [
                'Jenis Cuti' => $leave->leaveType?->name ?? '—',
                'Tanggal Mulai' => optional($leave->start_date)->format('Y-m-d') ?? (string) $leave->start_date,
                'Tanggal Selesai' => optional($leave->end_date)->format('Y-m-d') ?? (string) $leave->end_date,
                'Durasi (hari)' => (int) $leave->duration,
                'Status' => $this->formatStatus($leave->status),
                'Alasan' => $leave->reason,
            ];
        });
    }

    public function headings(): array
    {
        return ['Jenis Cuti', 'Tanggal Mulai', 'Tanggal Selesai', 'Durasi (hari)', 'Status', 'Alasan'];
    }

    public function title(): string
    {
        return 'Detail Harian';
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
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
        return [
            AfterSheet::class => function ($event): void {
                $worksheet = $event->sheet->getDelegate();
                $highestColumn = $worksheet->getHighestColumn();
                $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);
                $highestRow = $worksheet->getHighestRow();
                $highestCell = $highestColumn.$highestRow;

                $worksheet->freezePane('A2');
                $worksheet->getStyle('A1:'.$highestCell)->getAlignment()->setWrapText(true);
                $worksheet->getStyle('A1:'.$highestCell)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $worksheet->getStyle('A1:F'.$highestRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $worksheet->getStyle('A1:F'.$highestRow)->getBorders()->getAllBorders()->getColor()->setRGB('D1D5DB');

                if ($highestRow >= 2) {
                    for ($row = 2; $row <= $highestRow; $row++) {
                        if (($row - 2) % 2 === 0) {
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
            'pending' => 'Pending',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            default => '—',
        };
    }

}