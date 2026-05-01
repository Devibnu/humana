<?php

namespace App\Exports;

use App\Models\Employee;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class LeavesEmployeeAggregationExport implements WithMultipleSheets
{
    public function __construct(
        protected Employee $employee,
        protected Collection $leaves,
        protected array $monthlyRows,
        protected array $annualRows,
    ) {
    }

    public function sheets(): array
    {
        return [
            new LeavesEmployeeAggregationDetailSheetExport($this->employee, $this->leaves),
            new LeavesEmployeeAggregationSummarySheetExport(
                'Rekap Bulanan',
                'Summary Rekap Cuti Bulanan/Tahunan',
                ['Tahun', 'Bulan', 'Pending', 'Approved', 'Rejected', 'Total Hari Cuti'],
                $this->monthlyRows,
                ['year', 'month', 'pending', 'approved', 'rejected', 'total_days']
            ),
            new LeavesEmployeeAggregationSummarySheetExport(
                'Rekap Tahunan',
                'Summary Rekap Cuti Bulanan/Tahunan',
                ['Tahun', 'Pending', 'Approved', 'Rejected', 'Total Hari Cuti'],
                $this->annualRows,
                ['year', 'pending', 'approved', 'rejected', 'total_days']
            ),
        ];
    }
}