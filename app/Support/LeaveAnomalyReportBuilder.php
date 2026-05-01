<?php

namespace App\Support;

use App\Models\Leave;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class LeaveAnomalyReportBuilder
{
    private const SPIKE_MULTIPLIER = 3;
    private const CARRY_OVER_LIMIT = 10;

    public function build(?User $currentUser, ?int $requestedTenantId = null): array
    {
        $now = Carbon::now();
        $selectedYear = (int) $now->format('Y');
        $selectedMonth = (int) $now->format('n');
        $tenantContext = $this->resolveTenantContext($currentUser, $requestedTenantId);
        $leaves = $this->baseLeaveQuery($currentUser, $tenantContext['tenant']?->id)
            ->with(['employee', 'leaveType'])
            ->orderBy('start_date')
            ->get();

        $spikeAlerts = $this->detectSpikeAlerts($leaves, $selectedYear, $selectedMonth);
        $recurringAlerts = $this->detectRecurringAlerts($leaves, $selectedYear, $selectedMonth);
        $carryOverAlerts = $this->detectCarryOverAlerts($leaves, $selectedYear, $selectedMonth);
        $alerts = collect([$spikeAlerts, $recurringAlerts, $carryOverAlerts])
            ->flatten(1)
            ->sortByDesc('priority')
            ->values()
            ->all();

        return [
            'tenants' => $tenantContext['tenants'],
            'tenant' => $tenantContext['tenant'],
            'canSwitchTenant' => $tenantContext['can_switch_tenant'],
            'selectedYear' => $selectedYear,
            'selectedMonth' => $selectedMonth,
            'selectedMonthLabel' => $this->monthOptions()[$selectedMonth],
            'summary' => $this->buildSummary($alerts),
            'charts' => [
                'spikeTrend' => $this->buildSpikeTrendChart($leaves, $selectedYear),
                'heatmap' => $this->buildHeatmapChart($leaves, $selectedYear),
                'carryOver' => $this->buildCarryOverChart($leaves, $selectedYear),
            ],
            'alerts' => $alerts,
        ];
    }

    public function buildTrendReport(?User $currentUser, ?int $requestedTenantId = null, ?int $requestedYear = null): array
    {
        $now = Carbon::now();
        $selectedYear = $this->normalizeYear($requestedYear, (int) $now->format('Y'));
        $selectedMonth = (int) $now->format('n');
        $tenantContext = $this->resolveTenantContext($currentUser, $requestedTenantId);
        $leaves = $this->baseLeaveQuery($currentUser, $tenantContext['tenant']?->id)
            ->with(['employee', 'leaveType'])
            ->orderBy('start_date')
            ->get();

        $monthly = $this->buildMonthlyTrendSummary($leaves, $selectedYear);
        $annual = $this->buildAnnualTrendSummary($leaves, $selectedYear);

        return [
            'tenants' => $tenantContext['tenants'],
            'tenant' => $tenantContext['tenant'],
            'canSwitchTenant' => $tenantContext['can_switch_tenant'],
            'selectedYear' => $selectedYear,
            'selectedMonth' => $selectedMonth,
            'yearOptions' => $this->buildYearOptions($leaves, $selectedYear),
            'monthly' => $monthly,
            'annual' => $annual,
            'summary' => [
                'total_this_month' => (int) ($monthly[$selectedMonth - 1]['total'] ?? 0),
                'total_this_year' => (int) collect($annual)->firstWhere('year', $selectedYear)['total'] ?? 0,
            ],
            'charts' => [
                'monthlyTrend' => $this->buildMonthlyAnomalyTrendChart($monthly),
                'annualTrend' => $this->buildAnnualAnomalyTrendChart($annual),
            ],
        ];
    }

    public function resolveTenantContext(?User $currentUser, ?int $requestedTenantId = null): array
    {
        $canSwitchTenant = (bool) $currentUser?->isAdminHr();
        $tenants = $canSwitchTenant
            ? Tenant::query()->orderBy('name')->get()
            : Tenant::query()
                ->when($currentUser?->tenant_id, fn (Builder $query) => $query->whereKey($currentUser->tenant_id))
                ->orderBy('name')
                ->get();

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
            ->when($currentUser?->isManager(), fn (Builder $query) => $query->where('tenant_id', $currentUser->tenant_id));
    }

    protected function buildSummary(array $alerts): array
    {
        $collection = collect($alerts);

        return [
            'anomalies_this_month' => (int) $collection->where('active_this_month', true)->count(),
            'spike_count' => (int) $collection->where('type', 'lonjakan')->count(),
            'recurring_count' => (int) $collection->where('type', 'pola_berulang')->count(),
            'carry_over_count' => (int) $collection->where('type', 'carry_over')->count(),
            'total_alerts' => (int) $collection->count(),
        ];
    }

    protected function buildMonthlyTrendSummary(Collection $leaves, int $selectedYear): array
    {
        return collect(range(1, 12))->map(function (int $month) use ($leaves, $selectedYear) {
            $counts = $this->countMonthlyAnomalies($leaves, $selectedYear, $month);

            return [
                'year' => $selectedYear,
                'month' => $month,
                'label' => $this->monthShortLabels()[$month],
                'spike' => $counts['spike'],
                'recurring' => $counts['recurring'],
                'carry_over' => $counts['carry_over'],
                'total' => $counts['spike'] + $counts['recurring'] + $counts['carry_over'],
            ];
        })->all();
    }

    protected function buildAnnualTrendSummary(Collection $leaves, int $selectedYear): array
    {
        return collect(range($selectedYear - 4, $selectedYear))->map(function (int $year) use ($leaves) {
            $counts = $this->countAnnualAnomalies($leaves, $year);

            return [
                'year' => $year,
                'spike' => $counts['spike'],
                'recurring' => $counts['recurring'],
                'carry_over' => $counts['carry_over'],
                'total' => $counts['spike'] + $counts['recurring'] + $counts['carry_over'],
            ];
        })->all();
    }

    protected function buildMonthlyAnomalyTrendChart(array $monthly): array
    {
        return [
            'labels' => array_map(fn (array $row) => $row['label'], $monthly),
            'spike' => array_map(fn (array $row) => (int) $row['spike'], $monthly),
            'recurring' => array_map(fn (array $row) => (int) $row['recurring'], $monthly),
            'carry_over' => array_map(fn (array $row) => (int) $row['carry_over'], $monthly),
            'totals' => array_map(fn (array $row) => (int) $row['total'], $monthly),
        ];
    }

    protected function buildAnnualAnomalyTrendChart(array $annual): array
    {
        return [
            'labels' => array_map(fn (array $row) => (string) $row['year'], $annual),
            'spike' => array_map(fn (array $row) => (int) $row['spike'], $annual),
            'recurring' => array_map(fn (array $row) => (int) $row['recurring'], $annual),
            'carry_over' => array_map(fn (array $row) => (int) $row['carry_over'], $annual),
            'totals' => array_map(fn (array $row) => (int) $row['total'], $annual),
        ];
    }

    protected function countMonthlyAnomalies(Collection $leaves, int $selectedYear, int $selectedMonth): array
    {
        return [
            'spike' => count($this->detectSpikeAlerts($leaves, $selectedYear, $selectedMonth)),
            'recurring' => count(array_filter(
                $this->detectRecurringAlerts($leaves, $selectedYear, $selectedMonth),
                fn (array $alert) => (bool) ($alert['active_this_month'] ?? false)
            )),
            'carry_over' => count(array_filter(
                $this->detectCarryOverAlerts($leaves, $selectedYear, $selectedMonth),
                fn (array $alert) => (bool) ($alert['active_this_month'] ?? false)
            )),
        ];
    }

    protected function countAnnualAnomalies(Collection $leaves, int $selectedYear): array
    {
        $spike = collect(range(1, 12))
            ->sum(fn (int $month) => count($this->detectSpikeAlerts($leaves, $selectedYear, $month)));
        $recurring = count($this->detectRecurringAlerts($leaves, $selectedYear, (int) Carbon::now()->format('n')));
        $carryOver = count($this->detectCarryOverAlerts($leaves, $selectedYear, 1));

        return [
            'spike' => (int) $spike,
            'recurring' => (int) $recurring,
            'carry_over' => (int) $carryOver,
        ];
    }

    protected function buildYearOptions(Collection $leaves, int $selectedYear): array
    {
        $years = $leaves
            ->filter(fn ($leave) => (bool) $leave->start_date)
            ->map(function ($leave) {
                $startDate = $leave->start_date instanceof Carbon ? $leave->start_date : Carbon::parse($leave->start_date);

                return (int) $startDate->format('Y');
            })
            ->merge(range($selectedYear - 4, $selectedYear))
            ->push($selectedYear)
            ->unique()
            ->sortDesc()
            ->values();

        return $years->all();
    }

    protected function normalizeYear(?int $requestedYear, int $defaultYear): int
    {
        if ($requestedYear === null || $requestedYear < 2000 || $requestedYear > 2100) {
            return $defaultYear;
        }

        return $requestedYear;
    }

    protected function buildSpikeTrendChart(Collection $leaves, int $selectedYear): array
    {
        $monthLabels = array_values($this->monthShortLabels());
        $monthlyDays = array_fill(0, 12, 0);

        foreach ($leaves as $leave) {
            if (! $leave->start_date) {
                continue;
            }

            $startDate = $leave->start_date instanceof Carbon ? $leave->start_date : Carbon::parse($leave->start_date);

            if ((int) $startDate->format('Y') !== $selectedYear) {
                continue;
            }

            $monthIndex = (int) $startDate->format('n') - 1;
            $monthlyDays[$monthIndex] += (int) $leave->duration;
        }

        $average = round(array_sum($monthlyDays) / 12, 1);
        $threshold = round($average * self::SPIKE_MULTIPLIER, 1);

        return [
            'labels' => $monthLabels,
            'days' => $monthlyDays,
            'average_days' => array_fill(0, 12, $average),
            'threshold_days' => array_fill(0, 12, $threshold),
            'anomaly_points' => array_map(
                fn (int $days) => $days > $threshold && $days > 0 ? $days : null,
                $monthlyDays
            ),
        ];
    }

    protected function buildHeatmapChart(Collection $leaves, int $selectedYear): array
    {
        $matrix = [];

        foreach (range(1, 7) as $weekday) {
            $matrix[$weekday] = array_fill(0, 12, 0);
        }

        foreach ($leaves as $leave) {
            if (! $leave->start_date) {
                continue;
            }

            $startDate = $leave->start_date instanceof Carbon ? $leave->start_date : Carbon::parse($leave->start_date);

            if ((int) $startDate->format('Y') !== $selectedYear) {
                continue;
            }

            $weekday = (int) $startDate->dayOfWeekIso;
            $monthIndex = (int) $startDate->format('n') - 1;
            $matrix[$weekday][$monthIndex]++;
        }

        return [
            'months' => array_values($this->monthShortLabels()),
            'weekdays' => [1 => 'Sen', 2 => 'Sel', 3 => 'Rab', 4 => 'Kam', 5 => 'Jum', 6 => 'Sab', 7 => 'Min'],
            'matrix' => array_values($matrix),
            'max_count' => max(array_map(fn (array $row) => max($row), $matrix)) ?: 0,
        ];
    }

    protected function buildCarryOverChart(Collection $leaves, int $selectedYear): array
    {
        $years = range($selectedYear - 4, $selectedYear);
        $totals = array_fill_keys($years, 0);

        foreach ($this->groupLeavesByEmployee($leaves) as $employeeLeaves) {
            $carryPerYear = [];

            foreach ($employeeLeaves as $leave) {
                if (! $leave->start_date || $leave->status !== 'approved' || ! $this->isAnnualLeaveType($leave)) {
                    continue;
                }

                $startDate = $leave->start_date instanceof Carbon ? $leave->start_date : Carbon::parse($leave->start_date);
                $year = (int) $startDate->format('Y');
                $month = (int) $startDate->format('n');

                if (! array_key_exists($year, $totals) || $month > 2) {
                    continue;
                }

                $carryPerYear[$year] = ($carryPerYear[$year] ?? 0) + (int) $leave->duration;
            }

            foreach ($carryPerYear as $year => $days) {
                if ($days > self::CARRY_OVER_LIMIT) {
                    $totals[$year] += $days;
                }
            }
        }

        return [
            'labels' => array_map(fn (int $year) => (string) $year, $years),
            'days' => array_values($totals),
            'limit' => array_fill(0, count($years), self::CARRY_OVER_LIMIT),
        ];
    }

    protected function detectSpikeAlerts(Collection $leaves, int $selectedYear, int $selectedMonth): array
    {
        return $this->groupLeavesByEmployee($leaves)
            ->map(function (Collection $employeeLeaves) use ($selectedYear, $selectedMonth) {
                $monthlyDays = array_fill(1, 12, 0);

                foreach ($employeeLeaves as $leave) {
                    if (! $leave->start_date) {
                        continue;
                    }

                    $startDate = $leave->start_date instanceof Carbon ? $leave->start_date : Carbon::parse($leave->start_date);

                    if ((int) $startDate->format('Y') !== $selectedYear) {
                        continue;
                    }

                    $monthlyDays[(int) $startDate->format('n')] += (int) $leave->duration;
                }

                $currentDays = $monthlyDays[$selectedMonth] ?? 0;
                $baselineMonths = collect($monthlyDays)
                    ->except($selectedMonth)
                    ->filter(fn (int $days) => $days > 0)
                    ->values();
                $averageDays = $baselineMonths->isNotEmpty()
                    ? round($baselineMonths->avg(), 1)
                    : 0.0;

                if ($currentDays <= 0 || $averageDays <= 0 || $currentDays <= ($averageDays * self::SPIKE_MULTIPLIER)) {
                    return null;
                }

                $employee = $employeeLeaves->first()->employee;

                return [
                    'type' => 'lonjakan',
                    'priority' => 300,
                    'color' => 'danger',
                    'icon' => 'fas fa-triangle-exclamation',
                    'employee_name' => $employee?->name ?? 'Karyawan Tidak Diketahui',
                    'title' => 'Lonjakan cuti bulan '.$this->monthOptions()[$selectedMonth],
                    'description' => sprintf(
                        '%s mengalami lonjakan cuti bulan %s (%d hari vs rata-rata %.1f hari).',
                        $employee?->name ?? 'Karyawan',
                        $this->monthOptions()[$selectedMonth],
                        $currentDays,
                        $averageDays
                    ),
                    'active_this_month' => true,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    protected function detectRecurringAlerts(Collection $leaves, int $selectedYear, int $selectedMonth): array
    {
        $alerts = [];

        foreach ($this->groupLeavesByEmployee($leaves) as $employeeLeaves) {
            $yearLeaves = $employeeLeaves
                ->filter(function ($leave) use ($selectedYear) {
                    if (! $leave->start_date) {
                        return false;
                    }

                    $startDate = $leave->start_date instanceof Carbon ? $leave->start_date : Carbon::parse($leave->start_date);

                    return (int) $startDate->format('Y') === $selectedYear;
                })
                ->sortBy('start_date')
                ->values();

            if ($yearLeaves->isEmpty()) {
                continue;
            }

            $employee = $yearLeaves->first()->employee;
            $weekdayPattern = $this->detectWeekdayPattern($yearLeaves);

            if ($weekdayPattern !== null) {
                $alerts[] = [
                    'type' => 'pola_berulang',
                    'priority' => 200,
                    'color' => 'warning',
                    'icon' => 'fas fa-repeat',
                    'employee_name' => $employee?->name ?? 'Karyawan Tidak Diketahui',
                    'title' => 'Pola berulang hari '.$weekdayPattern['weekday_label'],
                    'description' => sprintf(
                        '%s menunjukkan pola berulang: cuti di hari %s %dx berturut-turut.',
                        $employee?->name ?? 'Karyawan',
                        $weekdayPattern['weekday_label'],
                        $weekdayPattern['streak']
                    ),
                    'active_this_month' => in_array($selectedMonth, $weekdayPattern['months'], true),
                ];

                continue;
            }

            $datePattern = $this->detectSameDatePattern($yearLeaves);

            if ($datePattern !== null) {
                $alerts[] = [
                    'type' => 'pola_berulang',
                    'priority' => 180,
                    'color' => 'warning',
                    'icon' => 'fas fa-calendar-day',
                    'employee_name' => $employee?->name ?? 'Karyawan Tidak Diketahui',
                    'title' => 'Pola berulang tanggal sama',
                    'description' => sprintf(
                        '%s mengajukan cuti di tanggal %d pada %d bulan berbeda.',
                        $employee?->name ?? 'Karyawan',
                        $datePattern['day_of_month'],
                        $datePattern['months_count']
                    ),
                    'active_this_month' => in_array($selectedMonth, $datePattern['months'], true),
                ];
            }
        }

        return $alerts;
    }

    protected function detectCarryOverAlerts(Collection $leaves, int $selectedYear, int $selectedMonth): array
    {
        return $this->groupLeavesByEmployee($leaves)
            ->map(function (Collection $employeeLeaves) use ($selectedYear, $selectedMonth) {
                $carryOverDays = $employeeLeaves
                    ->filter(function ($leave) use ($selectedYear) {
                        if (! $leave->start_date || $leave->status !== 'approved' || ! $this->isAnnualLeaveType($leave)) {
                            return false;
                        }

                        $startDate = $leave->start_date instanceof Carbon ? $leave->start_date : Carbon::parse($leave->start_date);

                        return (int) $startDate->format('Y') === $selectedYear
                            && (int) $startDate->format('n') <= 2;
                    })
                    ->sum('duration');

                if ($carryOverDays <= self::CARRY_OVER_LIMIT) {
                    return null;
                }

                $employee = $employeeLeaves->first()->employee;

                return [
                    'type' => 'carry_over',
                    'priority' => 150,
                    'color' => 'info',
                    'icon' => 'fas fa-layer-group',
                    'employee_name' => $employee?->name ?? 'Karyawan Tidak Diketahui',
                    'title' => 'Carry-over cuti tinggi',
                    'description' => sprintf(
                        '%s memiliki indikasi carry-over cuti %d hari dari tahun lalu.',
                        $employee?->name ?? 'Karyawan',
                        $carryOverDays
                    ),
                    'active_this_month' => $selectedMonth <= 2,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    protected function detectWeekdayPattern(Collection $yearLeaves): ?array
    {
        $targetWeekdays = [1 => 'Senin', 5 => 'Jumat'];
        $longestStreak = 0;
        $currentStreak = 0;
        $currentWeekday = null;
        $bestWeekday = null;
        $matchedMonths = [];
        $currentMonths = [];

        foreach ($yearLeaves as $leave) {
            $startDate = $leave->start_date instanceof Carbon ? $leave->start_date : Carbon::parse($leave->start_date);
            $weekday = (int) $startDate->dayOfWeekIso;
            $month = (int) $startDate->format('n');

            if (! array_key_exists($weekday, $targetWeekdays)) {
                $currentStreak = 0;
                $currentWeekday = null;
                $currentMonths = [];

                continue;
            }

            if ($weekday === $currentWeekday) {
                $currentStreak++;
                $currentMonths[] = $month;
            } else {
                $currentWeekday = $weekday;
                $currentStreak = 1;
                $currentMonths = [$month];
            }

            if ($currentStreak > $longestStreak) {
                $longestStreak = $currentStreak;
                $bestWeekday = $weekday;
                $matchedMonths = $currentMonths;
            }
        }

        if ($longestStreak < 4 || $bestWeekday === null) {
            return null;
        }

        return [
            'weekday_label' => $targetWeekdays[$bestWeekday],
            'streak' => $longestStreak,
            'months' => $matchedMonths,
        ];
    }

    protected function detectSameDatePattern(Collection $yearLeaves): ?array
    {
        $pattern = $yearLeaves
            ->groupBy(fn ($leave) => (int) ( $leave->start_date instanceof Carbon ? $leave->start_date->format('d') : Carbon::parse($leave->start_date)->format('d')))
            ->map(function (Collection $group, int $dayOfMonth) {
                $months = $group->map(function ($leave) {
                    $startDate = $leave->start_date instanceof Carbon ? $leave->start_date : Carbon::parse($leave->start_date);

                    return (int) $startDate->format('n');
                })->unique()->values()->all();

                return [
                    'day_of_month' => $dayOfMonth,
                    'months_count' => count($months),
                    'months' => $months,
                ];
            })
            ->first(fn (array $row) => $row['months_count'] >= 3);

        return $pattern ?: null;
    }

    protected function groupLeavesByEmployee(Collection $leaves): Collection
    {
        return $leaves
            ->filter(fn ($leave) => $leave->employee_id)
            ->groupBy('employee_id');
    }

    protected function monthOptions(): array
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

    protected function monthShortLabels(): array
    {
        return [
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
    }

    protected function isAnnualLeaveType(Leave $leave): bool
    {
        $name = strtolower(trim((string) ($leave->leaveType?->name ?? '')));

        return in_array($name, ['cuti tahunan', 'annual'], true);
    }
}