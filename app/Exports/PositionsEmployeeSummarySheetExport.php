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

class PositionsEmployeeSummarySheetExport implements FromCollection, WithEvents, WithStyles, WithTitle
{
    private const BODY_ZEBRA_FILL = 'FEF3C7';
    private const HEADER_FILL = 'F3F4F6';

    public function __construct(protected Collection $positions, protected array $filters = [])
    {
    }

    public function collection(): Collection
    {
        return collect([
            ['Summary Karyawan per Posisi', null],
            ['Nama Posisi', 'Jumlah Karyawan'],
        ])->concat($this->positions->map(function ($position) {
            return [
                $position->name,
                (int) ($position->employees_count ?? 0),
            ];
        }));
    }

    public function title(): string
    {
        return 'Summary Karyawan';
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 13,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => self::HEADER_FILL],
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
                    'startColor' => ['rgb' => self::HEADER_FILL],
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
                $highestColumnIndex = Coordinate::columnIndexFromString($worksheet->getHighestColumn());
                $highestCell = $worksheet->getHighestColumn().$worksheet->getHighestRow();
                $highestRow = $worksheet->getHighestRow();

                $worksheet->mergeCells('A1:B1');
                $worksheet->freezePane('A3');
                $worksheet->getStyle('A1:'.$highestCell)->getAlignment()->setWrapText(true);
                $worksheet->getStyle('A1:'.$highestCell)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

                $worksheet->getStyle('A1:B2')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $worksheet->getStyle('A1:B2')->getBorders()->getAllBorders()->getColor()->setRGB('D1D5DB');

                if ($highestRow >= 3) {
                    $bodyRange = 'A3:'.$highestCell;

                    $worksheet->getStyle($bodyRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                    $worksheet->getStyle($bodyRange)->getBorders()->getAllBorders()->getColor()->setRGB('D6D3D1');

                    for ($row = 3; $row <= $highestRow; $row++) {
                        if (($row - 3) % 2 === 0) {
                            $worksheet->getStyle('A'.$row.':B'.$row)
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
}