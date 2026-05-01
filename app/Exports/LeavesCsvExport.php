<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;

class LeavesCsvExport implements FromArray
{
    public function __construct(protected Collection $leaves, protected array $filters = [])
    {
    }

    public function array(): array
    {
        $rowExportClass = $this->filters['row_export_class'] ?? LeavesExport::class;
        $rowExport = new $rowExportClass($this->leaves, $this->filters);

        $rows = [
            [
                'Summary',
                'Pending Requests',
                (int) (($this->filters['summary']['pending']['requests'] ?? 0)),
                'Pending Days',
                (int) (($this->filters['summary']['pending']['days'] ?? 0)),
                'Approved Requests',
                (int) (($this->filters['summary']['approved']['requests'] ?? 0)),
                'Approved Days',
                (int) (($this->filters['summary']['approved']['days'] ?? 0)),
                'Rejected Requests',
                (int) (($this->filters['summary']['rejected']['requests'] ?? 0)),
                'Rejected Days',
                (int) (($this->filters['summary']['rejected']['days'] ?? 0)),
            ],
            [],
            ['employee_code', 'employee_name', 'tenant', 'leave_type', 'start_date', 'end_date', 'duration_days', 'status', 'reason'],
        ];

        foreach ($rowExport->collection() as $leaveRow) {
            $rows[] = array_values($leaveRow);
        }

        $rows[] = [];
        $rows[] = ['Monthly Summary'];
        $rows[] = ['month', 'status', 'total_requests', 'total_days'];

        foreach ($this->filters['monthly_summary'] ?? [] as $summaryRow) {
            $rows[] = [
                $summaryRow['period_label'],
                $summaryRow['status_label'],
                (int) $summaryRow['requests'],
                (int) $summaryRow['days'],
            ];
        }

        $rows[] = [];
        $rows[] = ['Annual Summary'];
        $rows[] = ['year', 'status', 'total_requests', 'total_days'];

        foreach ($this->filters['annual_summary'] ?? [] as $summaryRow) {
            $rows[] = [
                $summaryRow['period_label'],
                $summaryRow['status_label'],
                (int) $summaryRow['requests'],
                (int) $summaryRow['days'],
            ];
        }

        $rows[] = [];
        $rows[] = ['Filtered Summary'];
        $rows[] = ['filter_scope', 'status', 'total_requests', 'total_days'];

        foreach ($this->filters['filtered_summary'] ?? [] as $summaryRow) {
            $rows[] = [
                $summaryRow['filter_scope'],
                $summaryRow['status_label'],
                (int) $summaryRow['requests'],
                (int) $summaryRow['days'],
            ];
        }

        return $rows;
    }
}