<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Comment;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LeavesAnomalyDetailSheetExport implements FromCollection, WithEvents, WithStyles, WithTitle
{
    private const HEADER_FILL = '0F172A';

    public function __construct(protected array $payload)
    {
    }

    public function collection(): Collection
    {
        $rows = collect([
            ['Employee', 'Jenis Anomali', 'Deskripsi', 'Periode', 'Status Resolusi', 'Tindakan Resolusi', 'Catatan Resolusi', 'Diselesaikan Pada'],
        ]);

        return $rows->concat(collect($this->payload['detailRows'])->map(fn (array $row) => [
            $row['employee'],
            $row['jenis_anomali'],
            $row['deskripsi'],
            $row['periode'],
            $row['status_resolusi'],
            $row['tindakan_resolusi'],
            $row['catatan_resolusi'],
            $row['diselesaikan_pada'],
        ]));
    }

    public function title(): string
    {
        return 'Detail Anomali';
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

                foreach ($this->payload['detailRows'] as $index => $row) {
                    $sheetRow = $index + 2;
                    $fillColor = match ($row['type_key']) {
                        'lonjakan' => 'FEE2E2',
                        'pola_berulang' => 'FFEDD5',
                        'carry_over' => 'DBEAFE',
                        default => 'F8FAFC',
                    };

                    $worksheet->getStyle('A'.$sheetRow.':H'.$sheetRow)
                        ->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()
                        ->setRGB($fillColor);

                    $comment = new Comment();
                    $comment->getText()->createTextRun($row['deskripsi']);
                    $worksheet->getComment('C'.$sheetRow)->setAuthor('GitHub Copilot');
                    $worksheet->getComment('C'.$sheetRow)->setText($comment->getText());
                }

                for ($columnIndex = 1; $columnIndex <= $highestColumnIndex; $columnIndex++) {
                    $worksheet->getColumnDimension(Coordinate::stringFromColumnIndex($columnIndex))->setAutoSize(true);
                }
            },
        ];
    }
}