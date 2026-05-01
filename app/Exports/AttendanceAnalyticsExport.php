<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class AttendanceAnalyticsExport implements WithMultipleSheets
{
    public function __construct(protected array $report)
    {
    }

    public function sheets(): array
    {
        return [
            new AttendanceAnalyticsMonthlySheetExport($this->report),
            new AttendanceAnalyticsYearlySheetExport($this->report),
        ];
    }
}