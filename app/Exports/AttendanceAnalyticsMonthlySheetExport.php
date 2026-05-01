<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithCharts;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\Legend;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Title;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AttendanceAnalyticsMonthlySheetExport implements FromCollection, WithCharts, WithEvents, WithStyles, WithTitle
{
    private const HEADER_FILL = '1F2937';
    private const ZEBRA_FILL = 'F0FDF4';

    public function __construct(protected array $report)
    {
    }

    public function collection(): Collection
    {
        $rows = collect([
            ['Tahun', 'Bulan', 'Hadir', 'Izin', 'Sakit', 'Alpha', 'Total Jam Kerja'],
        ]);

        return $rows->concat(collect($this->report['monthlySummaryRows'])->map(function (array $row) {
            return [
                $row['year'],
                $row['month_label'],
                $row['present'],
                $row['leave'],
                $row['sick'],
                $row['absent'],
                $row['total_work_hours_label'],
            ];
        }));
    }

    public function title(): string
    {
        return 'Summary Bulanan';
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

    public function charts(): array
    {
        $rowCount = count($this->report['monthlySummaryRows']) + 1;
        $sheetName = $this->title();
        $categories = [new DataSeriesValues('String', "'{$sheetName}'!\$B\$2:\$B\${$rowCount}", null, $rowCount - 1)];
        $seriesLabels = [];
        $seriesValues = [];

        foreach (['C', 'D', 'E', 'F'] as $column) {
            $seriesLabels[] = new DataSeriesValues('String', "'{$sheetName}'!\${$column}\$1", null, 1);
            $seriesValues[] = new DataSeriesValues('Number', "'{$sheetName}'!\${$column}\$2:\${$column}\${$rowCount}", null, $rowCount - 1);
        }

        $series = new DataSeries(
            DataSeries::TYPE_LINECHART,
            DataSeries::GROUPING_STANDARD,
            range(0, count($seriesValues) - 1),
            $seriesLabels,
            $categories,
            $seriesValues
        );

        $plotArea = new PlotArea(null, [$series]);
        $legend = new Legend(Legend::POSITION_BOTTOM, null, false);
        $chart = new Chart(
            'attendance-analytics-monthly-line-chart',
            new Title('Tren Absensi Bulanan per Status'),
            $legend,
            $plotArea,
            true,
            0,
            new Title('Bulan'),
            new Title('Jumlah')
        );

        $chart->setTopLeftPosition('I2');
        $chart->setBottomRightPosition('P20');

        return [$chart];
    }
}