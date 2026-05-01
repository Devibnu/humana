<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class PositionsExport implements WithMultipleSheets
{
    public function __construct(protected Collection $positions, protected array $filters = [])
    {
    }

    public function sheets(): array
    {
        return [
            new PositionsDataSheetExport($this->positions, [
                ...$this->filters,
                'format' => 'xlsx',
            ]),
            new PositionsEmployeeSummarySheetExport($this->positions, $this->filters),
        ];
    }
}