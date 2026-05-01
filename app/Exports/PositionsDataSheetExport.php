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

class PositionsDataSheetExport implements FromCollection, WithEvents, WithStyles, WithTitle
{
    private const BODY_ZEBRA_FILL = 'FFF7ED';
    private const SUMMARY_FILL = 'F3F4F6';

    protected int $activeCount;

    protected int $inactiveCount;

    protected string $format;

    protected string $summaryTitle;

    public function __construct(protected Collection $positions, protected array $filters = [])
    {
        $this->activeCount = (int) ($this->filters['active_count'] ?? $this->positions->where('status', 'active')->count());
        $this->inactiveCount = (int) ($this->filters['inactive_count'] ?? $this->positions->where('status', 'inactive')->count());
        $this->format = (string) ($this->filters['format'] ?? 'xlsx');
        $this->summaryTitle = (string) ($this->filters['summary_title'] ?? 'Summary Export Departemen');
    }

    public function collection(): Collection
    {
        $rows = $this->format === 'csv'
            ? collect([
                ['Total Posisi Aktif: '.$this->activeCount],
                ['Total Posisi Non-Aktif: '.$this->inactiveCount],
                ['Nama Posisi', 'Kode', 'Deskripsi', 'Status', 'Jumlah Karyawan'],
            ])
            : collect([
                [$this->summaryTitle, null, null, null, null],
                ['Total Posisi Aktif: '.$this->activeCount, null, null, null, null],
                ['Total Posisi Non-Aktif: '.$this->inactiveCount, null, null, null, null],
                [null, null, null, null, null],
                ['Nama Posisi', 'Kode', 'Deskripsi', 'Status', 'Jumlah Karyawan'],
            ]);

        return $rows->concat($this->positions->map(function ($position) {
            return [
                $position->name,
                $position->code,
                $position->description,
                $this->formatStatus($position->status),
                (int) ($position->employees_count ?? 0),
            ];
        }));
    }

    public function title(): string
    {
        return 'Posisi';
    }

    public function styles(Worksheet $sheet): array
    {
        if ($this->format === 'csv') {
            return [];
        }

        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 13,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => self::SUMMARY_FILL],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
            2 => [
                'font' => [
                    'bold' => true,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => self::SUMMARY_FILL],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
            3 => [
                'font' => [
                    'bold' => true,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => self::SUMMARY_FILL],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
            5 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '9A3412'],
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
                $highestColumnIndex = Coordinate::columnIndexFromString($worksheet->getHighestColumn());
                $highestCell = $worksheet->getHighestColumn().$worksheet->getHighestRow();
                $highestRow = $worksheet->getHighestRow();

                $worksheet->mergeCells('A1:E1');
                $worksheet->mergeCells('A2:E2');
                $worksheet->mergeCells('A3:E3');
                $worksheet->freezePane('A6');
                $worksheet->getStyle('A1:'.$highestCell)->getAlignment()->setWrapText(true);
                $worksheet->getStyle('A1:'.$highestCell)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

                if ($highestRow >= 6) {
                    $bodyRange = 'A6:'.$highestCell;

                    $worksheet->getStyle($bodyRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                    $worksheet->getStyle($bodyRange)->getBorders()->getAllBorders()->getColor()->setRGB('D6D3D1');

                    for ($row = 6; $row <= $highestRow; $row++) {
                        if (($row - 6) % 2 === 0) {
                            $worksheet->getStyle('A'.$row.':'.$worksheet->getHighestColumn().$row)
                                ->getFill()
                                ->setFillType(Fill::FILL_SOLID)
                                ->getStartColor()
                                ->setRGB(self::BODY_ZEBRA_FILL);
                        }
                    }
                }

                $worksheet->getStyle('A1:E3')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $worksheet->getStyle('A1:E3')->getBorders()->getAllBorders()->getColor()->setRGB('D1D5DB');
                $worksheet->getStyle('A5:E5')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $worksheet->getStyle('A5:E5')->getBorders()->getAllBorders()->getColor()->setRGB('D6D3D1');

                for ($columnIndex = 1; $columnIndex <= $highestColumnIndex; $columnIndex++) {
                    $worksheet->getColumnDimension(Coordinate::stringFromColumnIndex($columnIndex))->setAutoSize(true);
                }
            },
        ];
    }

    protected function formatStatus(?string $status): string
    {
        return $status === 'active' ? 'Aktif' : 'Non-Aktif';
    }
}