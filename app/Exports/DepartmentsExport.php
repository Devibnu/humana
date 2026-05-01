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

class DepartmentsExport implements FromCollection, WithEvents, WithHeadings, WithStyles, WithTitle
{
    private const BODY_ZEBRA_FILL = 'F8FAFC';

    private const HEADER_ROW_HEIGHT = 25;

    private const BODY_ROW_HEIGHT = 20;

    public function __construct(protected Collection $departments, protected array $filters = [])
    {
    }

    public function collection(): Collection
    {
        return $this->departments->map(function ($department) {
            return [
                'name' => $department->name,
                'code' => $department->code,
                'tenant' => $department->tenant?->name,
                'status' => $department->status,
                'description' => $department->description,
                'employees_count' => $department->employees_count,
                'positions_count' => $department->positions_count,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'name',
            'code',
            'tenant',
            'status',
            'description',
            'employees_count',
            'positions_count',
        ];
    }

    public function title(): string
    {
        $parts = ['Departments'];

        if (! empty($this->filters['tenant_name'])) {
            $parts[] = (string) $this->filters['tenant_name'];
        }

        if (! empty($this->filters['status'])) {
            $parts[] = ucfirst((string) $this->filters['status']);
        }

        return substr(implode(' ', $parts), 0, 31);
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
                    'startColor' => ['rgb' => '334155'],
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

                $worksheet->freezePane('A2');
                $worksheet->getStyle('A1:'.$highestCell)->getAlignment()->setWrapText(true);
                $worksheet->getStyle('A1:'.$highestCell)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $worksheet->getRowDimension(1)->setRowHeight(self::HEADER_ROW_HEIGHT);

                if ($highestRow >= 2) {
                    $bodyRange = 'A2:'.$highestCell;

                    $worksheet->getStyle($bodyRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                    $worksheet->getStyle($bodyRange)->getBorders()->getAllBorders()->getColor()->setRGB('D1D5DB');

                    for ($row = 2; $row <= $highestRow; $row++) {
                        $rowDimension = $worksheet->getRowDimension($row);

                        if ($rowDimension->getRowHeight() < self::BODY_ROW_HEIGHT) {
                            $rowDimension->setRowHeight(self::BODY_ROW_HEIGHT);
                        }

                        if ($row % 2 === 0) {
                            $worksheet->getStyle('A'.$row.':'.$worksheet->getHighestColumn().$row)
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
