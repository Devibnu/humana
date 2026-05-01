<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class LeaveSummarySheetExport implements FromCollection, WithHeadings, WithTitle
{
    public function __construct(
        protected array $rows,
        protected string $titleName,
        protected array $columnKeys,
        protected array $headingRow
    ) {
    }

    public function collection(): Collection
    {
        return collect($this->rows)->map(function (array $row) {
            return array_map(function ($key) use ($row) {
                return $row[$key] ?? null;
            }, $this->columnKeys);
        });
    }

    public function headings(): array
    {
        return $this->headingRow;
    }

    public function title(): string
    {
        return substr($this->titleName, 0, 31);
    }
}