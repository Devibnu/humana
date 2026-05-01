<?php

namespace App\Support;

use App\Models\Leave;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class LeaveAnalyticsReportBuilder
{
    public function build(?User $currentUser, ?int $requestedTenantId = null, ?int $requestedYear = null, ?int $requestedMonth = null): array
    {
        $now = Carbon::now();
        $selectedYear = $this->normalizeYear($requestedYear, (int) $now->format('Y'));
        $selectedMonth = $this->normalizeMonth($requestedMonth, (int) $now->format('n'));
        $tenantContext = $this->resolveTenantContext($currentUser, $requestedTenantId);
        $leaves = $this->baseLeaveQuery($currentUser, $tenantContext['tenant']?->id)
            ->with('leaveType')
            ->orderBy('start_date')
            ->get();

        $summary = $this->buildSummary($leaves, $selectedYear, $selectedMonth);
        $monthly = $this->buildMonthlySummary($leaves, $selectedYear);
        $annual = $this->buildAnnualSummary($leaves, $selectedYear);
        $leaveTypeBreakdown = $this->buildLeaveTypeBreakdown($leaves, $selectedYear, $selectedMonth);
        $monthOptions = $this->buildMonthOptions();

        return [
            'tenants' => $tenantContext['tenants'],
            'tenant' => $tenantContext['tenant'],
            'canSwitchTenant' => $tenantContext['can_switch_tenant'],
            'summary' => $summary,
            'monthly' => $monthly,
            'annual' => $annual,
            'leaveTypeBreakdown' => $leaveTypeBreakdown,
            'selectedYear' => $selectedYear,
            'selectedMonth' => $selectedMonth,
            'selectedMonthLabel' => $monthOptions[$selectedMonth] ?? Carbon::createFromDate($selectedYear, $selectedMonth, 1)->translatedFormat('F'),
            'yearOptions' => $this->buildYearOptions($leaves, $selectedYear),
            'monthOptions' => $monthOptions,
            'charts' => [
                'monthlyTrend' => $this->buildMonthlyTrendChart($monthly),
                'annualStatus' => $this->buildAnnualStatusChart($annual),
                'statusPie' => $this->buildStatusPieChart($summary),
                'statusDays' => $this->buildStatusDaysChart($summary),
                'leaveTypeBreakdown' => [
                    'labels' => array_values(array_map(fn (array $row) => $row['leave_type_name'], $leaveTypeBreakdown)),
                    'requests' => array_values(array_map(fn (array $row) => $row['requests'], $leaveTypeBreakdown)),
                    'days' => array_values(array_map(fn (array $row) => $row['days'], $leaveTypeBreakdown)),
                ],
            ],
        ];
    }

    protected function buildLeaveTypeBreakdown(Collection $leaves, int $selectedYear, int $selectedMonth): array
    {
        return $leaves
            ->filter(function ($leave) use ($selectedYear, $selectedMonth) {
                if (! $leave->start_date) {
                    return false;
                }

                $startDate = $leave->start_date instanceof Carbon
                    ? $leave->start_date
                    : Carbon::parse($leave->start_date);

                return (int) $startDate->format('Y') === $selectedYear
                    && (int) $startDate->format('n') === $selectedMonth;
            })
            ->groupBy(fn ($leave) => $leave->leaveType?->name ?? 'Tidak Ditentukan')
            ->map(function (Collection $group, string $leaveTypeName) {
                return [
                    'leave_type_name' => $leaveTypeName,
                    'requests' => (int) $group->count(),
                    'days' => (int) $group->sum('duration'),
                ];
            })
            ->values()
            ->sortByDesc('requests')
            ->all();
    }

    public function resolveTenantContext(?User $currentUser, ?int $requestedTenantId = null): array
    {
        $canSwitchTenant = (bool) $currentUser?->isAdminHr();
        $tenants = $canSwitchTenant
            ? Tenant::query()->orderBy('name')->get()
            : Tenant::query()->when($currentUser?->tenant_id, fn (Builder $query) => $query->whereKey($currentUser->tenant_id))->orderBy('name')->get();

        $fallbackTenant = $tenants->firstWhere('id', (int) $currentUser?->tenant_id) ?: $tenants->first();
        $tenant = $canSwitchTenant
            ? ($tenants->firstWhere('id', $requestedTenantId) ?: $fallbackTenant)
            : $fallbackTenant;

        return [
            'tenants' => $tenants,
            'tenant' => $tenant,
            'can_switch_tenant' => $canSwitchTenant,
        ];
    }

    protected function baseLeaveQuery(?User $currentUser, ?int $tenantId = null): Builder
    {
        return Leave::query()
            ->when($currentUser?->isAdminHr() && $tenantId, fn (Builder $query) => $query->where('tenant_id', $tenantId))
            ->when($currentUser?->isAdminHr() && ! $tenantId, fn (Builder $query) => $query->whereRaw('1 = 0'))
            ->when($currentUser?->isManager(), fn (Builder $query) => $query->where('tenant_id', $currentUser->tenant_id))
            ->when($currentUser?->isEmployee(), function (Builder $query) use ($currentUser) {
                $query->where('tenant_id', $currentUser->tenant_id)
                    ->whereHas('employee', fn (Builder $employeeQuery) => $employeeQuery->where('user_id', $currentUser->id));
            });
    }

    protected function buildSummary(Collection $leaves, int $selectedYear, int $selectedMonth): array
    {
        $selectedLeaves = $leaves->filter(function ($leave) use ($selectedYear, $selectedMonth) {
            if (! $leave->start_date) {
                return false;
            }

            $startDate = $leave->start_date instanceof Carbon
                ? $leave->start_date
                : Carbon::parse($leave->start_date);

            return (int) $startDate->format('Y') === $selectedYear
                && (int) $startDate->format('n') === $selectedMonth;
        })->values();

        $summary = [
            'pending' => ['requests' => 0, 'days' => 0],
            'approved' => ['requests' => 0, 'days' => 0],
            'rejected' => ['requests' => 0, 'days' => 0],
        ];

        foreach ($selectedLeaves as $leave) {
            if (! array_key_exists($leave->status, $summary)) {
                continue;
            }

            $summary[$leave->status]['requests']++;
            $summary[$leave->status]['days'] += (int) $leave->duration;
        }

        $summary['pending_count'] = (int) $summary['pending']['requests'];
        $summary['pending_days'] = (int) $summary['pending']['days'];
        $summary['approved_count'] = (int) $summary['approved']['requests'];
        $summary['approved_days'] = (int) $summary['approved']['days'];
        $summary['rejected_count'] = (int) $summary['rejected']['requests'];
        $summary['rejected_days'] = (int) $summary['rejected']['days'];
        $summary['total_requests'] = (int) $selectedLeaves->count();
        $summary['total_days'] = (int) $selectedLeaves->sum('duration');
        $summary['selected_year'] = $selectedYear;
        $summary['selected_month'] = $selectedMonth;

        foreach (['pending', 'approved', 'rejected'] as $statusKey) {
            $summary[$statusKey]['percentage'] = $summary['total_requests'] > 0
                ? (float) round(($summary[$statusKey]['requests'] / $summary['total_requests']) * 100, 1)
                : 0.0;
        }

        return $summary;
    }

    protected function buildMonthlySummary(Collection $leaves, int $selectedYear): array
    {
        $periodSummaries = [];

        foreach (range(1, 12) as $month) {
            $periodDate = Carbon::createFromDate($selectedYear, $month, 1);
            $periodKey = $periodDate->format('Y-m');
            $periodSummaries[$periodKey] = [
                'period_key' => $periodKey,
                'period_label' => $this->formatMonthLabel($periodDate, false),
                'year' => $selectedYear,
                'month' => $month,
                'pending' => ['requests' => 0, 'days' => 0],
                'approved' => ['requests' => 0, 'days' => 0],
                'rejected' => ['requests' => 0, 'days' => 0],
                'total_requests' => 0,
                'total_days' => 0,
            ];
        }

        foreach ($leaves as $leave) {
            if (! $leave->start_date) {
                continue;
            }

            $periodDate = $leave->start_date instanceof Carbon
                ? $leave->start_date->copy()
                : Carbon::parse($leave->start_date);

            if ((int) $periodDate->format('Y') !== $selectedYear) {
                continue;
            }

            $periodKey = $periodDate->format('Y-m');

            if (! array_key_exists($leave->status, $periodSummaries[$periodKey])) {
                continue;
            }

            $periodSummaries[$periodKey][$leave->status]['requests']++;
            $periodSummaries[$periodKey][$leave->status]['days'] += (int) $leave->duration;
            $periodSummaries[$periodKey]['total_requests']++;
            $periodSummaries[$periodKey]['total_days'] += (int) $leave->duration;
        }

        ksort($periodSummaries);

        return array_values($periodSummaries);
    }

    protected function buildAnnualSummary(Collection $leaves, int $selectedYear): array
    {
        $annualSummaries = [];

        foreach (range($selectedYear - 4, $selectedYear) as $year) {
            $annualSummaries[$year] = [
                'year' => $year,
                'pending' => ['requests' => 0, 'days' => 0],
                'approved' => ['requests' => 0, 'days' => 0],
                'rejected' => ['requests' => 0, 'days' => 0],
                'total_requests' => 0,
                'total_days' => 0,
            ];
        }

        foreach ($leaves as $leave) {
            if (! $leave->start_date) {
                continue;
            }

            $startDate = $leave->start_date instanceof Carbon
                ? $leave->start_date
                : Carbon::parse($leave->start_date);
            $year = (int) $startDate->format('Y');

            if (! array_key_exists($year, $annualSummaries) || ! array_key_exists($leave->status, $annualSummaries[$year])) {
                continue;
            }

            $annualSummaries[$year][$leave->status]['requests']++;
            $annualSummaries[$year][$leave->status]['days'] += (int) $leave->duration;
            $annualSummaries[$year]['total_requests']++;
            $annualSummaries[$year]['total_days'] += (int) $leave->duration;
        }

        ksort($annualSummaries);

        return array_values($annualSummaries);
    }

    protected function buildMonthlyTrendChart(array $monthlySummary): array
    {
        return [
            'labels' => array_map(fn (array $row) => $row['period_label'], $monthlySummary),
            'pending_requests' => array_map(fn (array $row) => (int) $row['pending']['requests'], $monthlySummary),
            'approved_requests' => array_map(fn (array $row) => (int) $row['approved']['requests'], $monthlySummary),
            'rejected_requests' => array_map(fn (array $row) => (int) $row['rejected']['requests'], $monthlySummary),
            'total_days' => array_map(fn (array $row) => (int) $row['pending']['days'] + (int) $row['approved']['days'] + (int) $row['rejected']['days'], $monthlySummary),
        ];
    }

    protected function buildAnnualStatusChart(array $annualSummary): array
    {
        return [
            'labels' => array_map(fn (array $row) => (string) $row['year'], $annualSummary),
            'pending_requests' => array_map(fn (array $row) => (int) $row['pending']['requests'], $annualSummary),
            'approved_requests' => array_map(fn (array $row) => (int) $row['approved']['requests'], $annualSummary),
            'rejected_requests' => array_map(fn (array $row) => (int) $row['rejected']['requests'], $annualSummary),
            'total_days' => array_map(fn (array $row) => (int) $row['total_days'], $annualSummary),
        ];
    }

    protected function buildStatusPieChart(array $summary): array
    {
        return [
            'labels' => ['Pending', 'Approved', 'Rejected'],
            'requests' => [
                (int) ($summary['pending']['requests'] ?? 0),
                (int) ($summary['approved']['requests'] ?? 0),
                (int) ($summary['rejected']['requests'] ?? 0),
            ],
            'days' => [
                (int) ($summary['pending']['days'] ?? 0),
                (int) ($summary['approved']['days'] ?? 0),
                (int) ($summary['rejected']['days'] ?? 0),
            ],
            'percentages' => [
                (float) ($summary['pending']['percentage'] ?? 0),
                (float) ($summary['approved']['percentage'] ?? 0),
                (float) ($summary['rejected']['percentage'] ?? 0),
            ],
            'backgroundColor' => ['#fbcf33', '#82d616', '#ea0606'],
        ];
    }

    protected function buildStatusDaysChart(array $summary): array
    {
        return [
            'labels' => ['Pending', 'Approved', 'Rejected'],
            'requests' => [
                (int) ($summary['pending']['requests'] ?? 0),
                (int) ($summary['approved']['requests'] ?? 0),
                (int) ($summary['rejected']['requests'] ?? 0),
            ],
            'days' => [
                (int) ($summary['pending']['days'] ?? 0),
                (int) ($summary['approved']['days'] ?? 0),
                (int) ($summary['rejected']['days'] ?? 0),
            ],
            'backgroundColor' => ['#fbcf33', '#82d616', '#ea0606'],
        ];
    }

    protected function buildYearOptions(Collection $leaves, int $selectedYear): array
    {
        $years = collect(range($selectedYear - 4, $selectedYear));

        $dataYears = $leaves
            ->filter(fn ($leave) => (bool) $leave->start_date)
            ->map(function ($leave) {
                $startDate = $leave->start_date instanceof Carbon
                    ? $leave->start_date
                    : Carbon::parse($leave->start_date);

                return (int) $startDate->format('Y');
            });

        return $years
            ->merge($dataYears)
            ->push($selectedYear)
            ->unique()
            ->sortDesc()
            ->values()
            ->all();
    }

    protected function buildMonthOptions(): array
    {
        return [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];
    }

    protected function normalizeYear(?int $requestedYear, int $defaultYear): int
    {
        if ($requestedYear === null || $requestedYear < 2000 || $requestedYear > 2100) {
            return $defaultYear;
        }

        return $requestedYear;
    }

    protected function normalizeMonth(?int $requestedMonth, int $defaultMonth): int
    {
        if ($requestedMonth === null || $requestedMonth < 1 || $requestedMonth > 12) {
            return $defaultMonth;
        }

        return $requestedMonth;
    }

    protected function formatMonthLabel(Carbon $date, bool $includeYear = true): string
    {
        $monthNames = [
            1 => 'Jan',
            2 => 'Feb',
            3 => 'Mar',
            4 => 'Apr',
            5 => 'Mei',
            6 => 'Jun',
            7 => 'Jul',
            8 => 'Agu',
            9 => 'Sep',
            10 => 'Okt',
            11 => 'Nov',
            12 => 'Des',
        ];

        $label = $monthNames[(int) $date->format('n')] ?? $date->format('M');

        return $includeYear
            ? $label.' '.$date->format('Y')
            : $label;
    }
}