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

class LeavesAnomalySummarySheetExport implements FromCollection, WithEvents, WithStyles, WithTitle
{
    private const HEADER_FILL = '1F2937';
    private const ZEBRA_FILL = 'F9FAFB';

    public function __construct(protected array $payload)
    {
    }

    public function collection(): Collection
    {
        $rows = collect([
            ['Jenis Anomali', 'Jumlah Kasus', 'Contoh'],
        ]);

        return $rows->concat(collect($this->payload['summaryRows'])->map(fn (array $row) => [
            $row['jenis_anomali'],
            $row['jumlah_kasus'],
            $row['contoh'],
        ]));
    }

    public function title(): string
    {
        return 'Summary';
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
                $highestRow = $worksheet->getHighestRow();
                $highestColumn = $worksheet->getHighestColumn();
                $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);

                $worksheet->freezePane('A2');
                $worksheet->getStyle('A1:'.$highestColumn.$highestRow)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $worksheet->getStyle('A1:'.$highestColumn.$highestRow)->getAlignment()->setWrapText(true);
                $worksheet->getStyle('A1:'.$highestColumn.$highestRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $worksheet->getStyle('A1:'.$highestColumn.$highestRow)->getBorders()->getAllBorders()->getColor()->setRGB('D1D5DB');

                for ($row = 2; $row <= $highestRow; $row++) {
                    if (($row - 2) % 2 === 0) {
                        $worksheet->getStyle('A'.$row.':'.$highestColumn.$row)
                            ->getFill()
                            ->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()
                            ->setRGB(self::ZEBRA_FILL);
                    }
                }

                for ($columnIndex = 1; $columnIndex <= $highestColumnIndex; $columnIndex++) {
                    $worksheet->getColumnDimension(Coordinate::stringFromColumnIndex($columnIndex))->setAutoSize(true);
                }
            },
        ];
    }
}