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

class LeavesAnomalyResolutionAuditDashboardExport implements FromCollection, WithEvents, WithStyles, WithTitle
{
    private const HEADER_FILL = '0F172A';
    private const SUMMARY_FILL = 'E5E7EB';

    public function __construct(protected array $payload)
    {
    }

    public function collection(): Collection
    {
        $summaryRows = [
            ['Ringkasan Audit Dashboard', '', '', '', '', '', '', ''],
            ['Resolusi Bulan Ini', $this->payload['summary']['resolved_this_month'] ?? 0, 'Resolusi Tahun Ini', $this->payload['summary']['resolved_this_year'] ?? 0, 'Unresolved Aktif', $this->payload['summary']['unresolved_active'] ?? 0, 'Total Log', $this->payload['summary']['total_logs'] ?? 0],
            ['', '', '', '', '', '', '', ''],
            ['Employee', 'Jenis Anomali', 'Deskripsi', 'Periode', 'Manager', 'Tindakan', 'Catatan', 'Timestamp'],
        ];

        $detailRows = collect($this->payload['logs'])->map(fn (array $row) => [
            $row['employee'],
            $row['jenis_anomali'],
            $row['deskripsi'],
            $row['periode'],
            $row['manager'],
            $row['tindakan'],
            $row['catatan'],
            $row['timestamp'],
        ]);

        return collect($summaryRows)->concat($detailRows);
    }

    public function title(): string
    {
        return 'Audit Dashboard';
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true],
            ],
            4 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => self::HEADER_FILL],
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

                $worksheet->mergeCells('A1:H1');
                $worksheet->freezePane('A5');
                $worksheet->getStyle('A1:'.$highestColumn.$highestRow)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $worksheet->getStyle('A1:'.$highestColumn.$highestRow)->getAlignment()->setWrapText(true);
                $worksheet->getStyle('A1:'.$highestColumn.$highestRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $worksheet->getStyle('A1:'.$highestColumn.$highestRow)->getBorders()->getAllBorders()->getColor()->setRGB('D1D5DB');

                $worksheet->getStyle('A1:H2')
                    ->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setRGB(self::SUMMARY_FILL);

                foreach ($this->payload['logs'] as $index => $row) {
                    $sheetRow = $index + 5;
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
                    $comment->getText()->createTextRun('Catatan: '.$row['catatan']);
                    $worksheet->getComment('G'.$sheetRow)->setAuthor('GitHub Copilot');
                    $worksheet->getComment('G'.$sheetRow)->setText($comment->getText());
                }

                for ($columnIndex = 1; $columnIndex <= $highestColumnIndex; $columnIndex++) {
                    $worksheet->getColumnDimension(Coordinate::stringFromColumnIndex($columnIndex))->setAutoSize(true);
                }
            },
        ];
    }
}