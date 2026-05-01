<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class LeavesAnomalyExport implements WithMultipleSheets
{
    public function __construct(protected array $payload)
    {
    }

    public function sheets(): array
    {
        return [
            new LeavesAnomalySummarySheetExport($this->payload),
            new LeavesAnomalyDetailSheetExport($this->payload),
        ];
    }
}