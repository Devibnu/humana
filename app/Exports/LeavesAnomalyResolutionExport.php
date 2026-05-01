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

class LeavesAnomalyResolutionExport implements FromCollection, WithEvents, WithStyles, WithTitle
{
    private const HEADER_FILL = '0F172A';
    private const SUMMARY_FILL = 'E5E7EB';
    private const ZEBRA_FILL = 'F8FAFC';

    public function __construct(protected array $payload)
    {
    }

    public function collection(): Collection
    {
        $rows = collect([
            ['Employee', 'Jenis Anomali', 'Deskripsi', 'Periode', 'Manager', 'Tindakan', 'Catatan', 'Tanggal Resolusi'],
        ]);

        return $rows->concat(collect($this->payload['rows'])->map(fn (array $row) => [
            $row['employee'],
            $row['jenis_anomali'],
            $row['deskripsi'],
            $row['periode'],
            $row['manager'],
            $row['tindakan'],
            $row['catatan'],
            $row['tanggal_resolusi'],
        ]));
    }

    public function title(): string
    {
        return 'Rekap Resolusi';
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

                foreach ($this->payload['rows'] as $index => $row) {
                    $sheetRow = $index + 2;
                    $fillColor = match ($row['type_key']) {
                        'lonjakan' => 'FEE2E2',
                        'pola_berulang' => 'FFEDD5',
                        'carry_over' => 'DBEAFE',
                        default => self::ZEBRA_FILL,
                    };

                    $worksheet->getStyle('A'.$sheetRow.':H'.$sheetRow)
                        ->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()
                        ->setRGB($fillColor);

                    if (($row['status_key'] ?? 'open') === 'resolved') {
                        $worksheet->getStyle('H'.$sheetRow)
                            ->getFill()
                            ->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()
                            ->setRGB('DCFCE7');
                    }

                    $comment = new Comment();
                    $comment->getText()->createTextRun('Status: '.$row['status_resolusi'].' | Catatan: '.$row['catatan']);
                    $worksheet->getComment('G'.$sheetRow)->setAuthor('GitHub Copilot');
                    $worksheet->getComment('G'.$sheetRow)->setText($comment->getText());
                }

                $summaryStartRow = $highestRow + 2;
                $summaryEndRow = $summaryStartRow + 1;
                $worksheet->mergeCells('A'.$summaryStartRow.':D'.$summaryStartRow);
                $worksheet->setCellValue('A'.$summaryStartRow, 'Ringkasan Resolusi');
                $worksheet->setCellValue('A'.$summaryEndRow, 'Jumlah Resolved');
                $worksheet->setCellValue('B'.$summaryEndRow, $this->payload['summary']['resolved']);
                $worksheet->setCellValue('C'.$summaryEndRow, 'Jumlah Unresolved');
                $worksheet->setCellValue('D'.$summaryEndRow, $this->payload['summary']['unresolved']);

                $worksheet->getStyle('A'.$summaryStartRow.':D'.$summaryEndRow)
                    ->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setRGB(self::SUMMARY_FILL);
                $worksheet->getStyle('A'.$summaryStartRow.':D'.$summaryEndRow)
                    ->getFont()
                    ->setBold(true);
                $worksheet->getStyle('A'.$summaryStartRow.':D'.$summaryEndRow)
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);

                for ($columnIndex = 1; $columnIndex <= $highestColumnIndex; $columnIndex++) {
                    $worksheet->getColumnDimension(Coordinate::stringFromColumnIndex($columnIndex))->setAutoSize(true);
                }
            },
        ];
    }
}