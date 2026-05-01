<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class LeavesExport implements FromCollection, WithHeadings, WithTitle
{
    public function __construct(protected Collection $leaves, protected array $filters = [])
    {
    }

    public function collection(): Collection
    {
        return $this->filteredLeaves()->map(function ($leave) {
            return [
                'employee_code' => $leave->employee?->employee_code,
                'employee_name' => $leave->employee?->name,
                'tenant' => $leave->tenant?->name,
                'leave_type' => $leave->leaveType?->name,
                'start_date' => optional($leave->start_date)->format('Y-m-d'),
                'end_date' => optional($leave->end_date)->format('Y-m-d'),
                'duration_days' => $leave->duration,
                'status' => $leave->status,
                'reason' => $leave->reason,
            ];
        });
    }

    public function headings(): array
    {
        $summary = $this->filters['summary'] ?? [];

        return [
            [
                'Summary',
                'Pending Requests',
                (int) ($summary['pending']['requests'] ?? 0),
                'Pending Days',
                (int) ($summary['pending']['days'] ?? 0),
                'Approved Requests',
                (int) ($summary['approved']['requests'] ?? 0),
                'Approved Days',
                (int) ($summary['approved']['days'] ?? 0),
                'Rejected Requests',
                (int) ($summary['rejected']['requests'] ?? 0),
                'Rejected Days',
                (int) ($summary['rejected']['days'] ?? 0),
            ],
            [],
            [
                'employee_code',
                'employee_name',
                'tenant',
                'leave_type',
                'start_date',
                'end_date',
                'duration_days',
                'status',
                'reason',
            ],
        ];
    }

    protected function filteredLeaves(): Collection
    {
        $month = $this->normalizedMonth($this->filters['month'] ?? null);
        $year = $this->normalizedYear($this->filters['year'] ?? null);

        return $this->leaves->filter(function ($leave) use ($month, $year) {
            if (! $leave->start_date) {
                return $month === null && $year === null;
            }

            $date = $leave->start_date instanceof Carbon
                ? $leave->start_date
                : Carbon::parse($leave->start_date);

            if ($month !== null && (int) $date->format('n') !== $month) {
                return false;
            }

            if ($year !== null && (int) $date->format('Y') !== $year) {
                return false;
            }

            return true;
        })->values();
    }

    protected function normalizedMonth($month): ?int
    {
        $normalizedMonth = is_numeric($month) ? (int) $month : null;

        return $normalizedMonth >= 1 && $normalizedMonth <= 12 ? $normalizedMonth : null;
    }

    protected function normalizedYear($year): ?int
    {
        $normalizedYear = is_numeric($year) ? (int) $year : null;

        return $normalizedYear >= 1900 && $normalizedYear <= 3000 ? $normalizedYear : null;
    }

    public function title(): string
    {
        $parts = ['Leaves'];

        if (! empty($this->filters['tenant_name'])) {
            $parts[] = (string) $this->filters['tenant_name'];
        }

        if (! empty($this->filters['status'])) {
            $parts[] = ucfirst((string) $this->filters['status']);
        }

        return substr(implode(' ', $parts), 0, 31);
    }
}