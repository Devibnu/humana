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

class LeavesAnomalyResolutionAuditLogExport implements FromCollection, WithEvents, WithStyles, WithTitle
{
    private const HEADER_FILL = '0F172A';
    private const SUMMARY_FILL = 'E5E7EB';

    public function __construct(protected array $payload)
    {
    }

    public function collection(): Collection
    {
        $rows = collect([
            ['Employee', 'Jenis Anomali', 'Deskripsi', 'Periode', 'Manager', 'Tindakan', 'Catatan', 'Timestamp'],
        ]);

        return $rows->concat(collect($this->payload['logs'])->map(fn (array $row) => [
            $row['employee'],
            $row['jenis_anomali'],
            $row['deskripsi'],
            $row['periode'],
            $row['manager'],
            $row['tindakan'],
            $row['catatan'],
            $row['timestamp'],
        ]));
    }

    public function title(): string
    {
        return 'Audit Log';
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

                foreach ($this->payload['logs'] as $index => $row) {
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

                    $worksheet->getStyle('H'.$sheetRow)
                        ->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()
                        ->setRGB('DCFCE7');

                    $comment = new Comment();
                    $comment->getText()->createTextRun('Anomaly ID: '.$row['anomaly_id'].' | Manager ID: '.$row['manager_id'].' | Catatan: '.$row['catatan']);
                    $worksheet->getComment('G'.$sheetRow)->setAuthor('GitHub Copilot');
                    $worksheet->getComment('G'.$sheetRow)->setText($comment->getText());
                }

                $summaryStartRow = $highestRow + 2;
                $worksheet->mergeCells('A'.$summaryStartRow.':C'.$summaryStartRow);
                $worksheet->setCellValue('A'.$summaryStartRow, 'Ringkasan Audit Log');
                $worksheet->setCellValue('A'.($summaryStartRow + 1), 'Total Log');
                $worksheet->setCellValue('B'.($summaryStartRow + 1), $this->payload['summary']['total'] ?? 0);

                $worksheet->getStyle('A'.$summaryStartRow.':C'.($summaryStartRow + 1))
                    ->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setRGB(self::SUMMARY_FILL);
                $worksheet->getStyle('A'.$summaryStartRow.':C'.($summaryStartRow + 1))->getFont()->setBold(true);
                $worksheet->getStyle('A'.$summaryStartRow.':C'.($summaryStartRow + 1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

                for ($columnIndex = 1; $columnIndex <= $highestColumnIndex; $columnIndex++) {
                    $worksheet->getColumnDimension(Coordinate::stringFromColumnIndex($columnIndex))->setAutoSize(true);
                }
            },
        ];
    }
}