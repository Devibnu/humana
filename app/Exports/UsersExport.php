<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UsersExport implements FromCollection, WithEvents, WithHeadings, WithStyles, WithTitle
{
    private const BODY_ZEBRA_FILL = 'EFF6FF';

    private const HEADER_ROW_HEIGHT = 25;

    private const BODY_ROW_HEIGHT = 20;

    public function __construct(protected Collection $users, protected array $filters = [])
    {
    }

    public function collection(): Collection
    {
        return $this->users->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'tenant' => $user->tenant?->name,
                'role' => $user->roleName() ?? $user->roleKey(),
                'status' => $user->status,
                'linked_employee_code' => $user->employee?->employee_code,
                'linked_employee_name' => $user->employee?->name,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'id',
            'name',
            'email',
            'tenant',
            'role',
            'status',
            'linked_employee_code',
            'linked_employee_name',
        ];
    }

    public function title(): string
    {
        $parts = ['Users'];

        if (! empty($this->filters['tenant_name'])) {
            $parts[] = (string) $this->filters['tenant_name'];
        }

        if (! empty($this->filters['role'])) {
            $parts[] = ucfirst(str_replace('_', ' ', (string) $this->filters['role']));
        }

        if (! empty($this->filters['linked'])) {
            $parts[] = ucfirst((string) $this->filters['linked']);
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
                    'startColor' => ['rgb' => '1F4E78'],
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