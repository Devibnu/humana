<?php

namespace App\Exports;

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

class LeavesEmployeeAggregationSummarySheetExport implements FromCollection, WithEvents, WithHeadings, WithStyles, WithTitle
{
    private const SUMMARY_FILL = 'F3F4F6';
    private const BODY_ZEBRA_FILL = 'F9FAFB';

    public function __construct(
        protected string $titleName,
        protected string $summaryTitle,
        protected array $headingsRow,
        protected array $rows,
        protected array $columnKeys,
    ) {
    }

    public function collection(): Collection
    {
        return collect($this->rows)->map(function (array $row) {
            return collect($this->columnKeys)->map(fn (string $key) => $row[$key] ?? null)->all();
        });
    }

    public function headings(): array
    {
        return $this->headingsRow;
    }

    public function title(): string
    {
        return $this->titleName;
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
                    'startColor' => ['rgb' => self::SUMMARY_FILL],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
            3 => [
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

                $worksheet->insertNewRowBefore(1, 2);
                $worksheet->setCellValue('A1', $this->summaryTitle);
                $worksheet->mergeCells('A1:'.$highestColumn.'1');
                $worksheet->freezePane('A4');
                $worksheet->getStyle('A1:'.$highestColumn.$worksheet->getHighestRow())->getAlignment()->setWrapText(true);
                $worksheet->getStyle('A1:'.$highestColumn.$worksheet->getHighestRow())->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $worksheet->getStyle('A3:'.$highestColumn.$worksheet->getHighestRow())->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $worksheet->getStyle('A3:'.$highestColumn.$worksheet->getHighestRow())->getBorders()->getAllBorders()->getColor()->setRGB('D1D5DB');

                if ($worksheet->getHighestRow() >= 4) {
                    for ($row = 4; $row <= $worksheet->getHighestRow(); $row++) {
                        if (($row - 4) % 2 === 0) {
                            $worksheet->getStyle('A'.$row.':'.$highestColumn.$row)
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