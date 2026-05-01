<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class LeavesWorkbookExport implements WithMultipleSheets
{
    public function __construct(protected Collection $leaves, protected array $filters = [])
    {
    }

    public function sheets(): array
    {
        $rowExportClass = $this->filters['row_export_class'] ?? LeavesExport::class;

        return [
            new $rowExportClass($this->leaves, $this->filters),
            new LeaveSummarySheetExport(
                $this->filters['monthly_summary'] ?? [],
                'Monthly Summary',
                ['period_label', 'status_label', 'requests', 'days'],
                ['month', 'status', 'total_requests', 'total_days']
            ),
            new LeaveSummarySheetExport(
                $this->filters['annual_summary'] ?? [],
                'Annual Summary',
                ['period_label', 'status_label', 'requests', 'days'],
                ['year', 'status', 'total_requests', 'total_days']
            ),
            new LeaveSummarySheetExport(
                $this->filters['filtered_summary'] ?? [],
                'Filtered Summary',
                ['filter_scope', 'status_label', 'requests', 'days'],
                ['filter_scope', 'status', 'total_requests', 'total_days']
            ),
        ];
    }
}