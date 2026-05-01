<?php

namespace App\Support;

use App\Models\Attendance;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AttendanceAnalyticsReportBuilder
{
    public function build(?User $currentUser, ?int $requestedYear = null, ?int $requestedMonth = null, ?int $requestedTenantId = null): array
    {
        $today = Carbon::today();
        $availableYears = collect(range($today->year, $today->year - 4));
        $selectedYear = $this->resolveSelectedYear($requestedYear, $availableYears, $today);
        $selectedMonth = $this->resolveSelectedMonth($requestedMonth, $today);
        $tenantContext = $this->resolveTenantContext($currentUser, $requestedTenantId);
        $referenceMonth = Carbon::create($selectedYear, $selectedMonth, 1);
        $startDate = Carbon::create($selectedYear - 4, 1, 1)->startOfDay()->toDateString();
        $endDate = Carbon::create($selectedYear, 12, 31)->endOfDay()->toDateString();
        $statusMeta = $this->statusMeta();
        $monthNames = $this->monthNames();
        $shortMonthNames = $this->shortMonthNames();

        $attendances = $this->baseAttendanceQuery($currentUser, $tenantContext['tenant']?->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date')
            ->get();

        $monthlySummaryRows = $this->buildMonthlySummaryRows($attendances, $referenceMonth, $statusMeta, $shortMonthNames);
        $yearlySummaryRows = $this->buildYearlySummaryRows($attendances, $selectedYear, $statusMeta);
        $monthSummary = $this->buildSummaryFromMonthlyRows($monthlySummaryRows);
        $yearSummary = $this->buildSummaryFromYearlyRows($yearlySummaryRows, $selectedYear);
        $monthlyTrendChart = $this->buildMonthlyTrendChart($monthlySummaryRows, $statusMeta);
        $yearlyDistributionChart = $this->buildYearlyDistributionChart($yearlySummaryRows, $statusMeta);
        $statusDistributionChart = $this->buildStatusDistributionChart($monthSummary, $statusMeta);
        $selectedMonthLabel = $monthNames[$selectedMonth];

        return [
            'tenants' => $tenantContext['tenants'],
            'tenant' => $tenantContext['tenant'],
            'canSwitchTenant' => $tenantContext['can_switch_tenant'],
            'availableYears' => $availableYears,
            'selectedYear' => $selectedYear,
            'selectedMonth' => $selectedMonth,
            'selectedMonthLabel' => $selectedMonthLabel,
            'selectedPeriodLabel' => $selectedMonthLabel.' '.$selectedYear,
            'monthNames' => $monthNames,
            'shortMonthNames' => $shortMonthNames,
            'statusMeta' => $statusMeta,
            'monthSummary' => $monthSummary,
            'yearSummary' => $yearSummary,
            'monthlyTrendChart' => $monthlyTrendChart,
            'yearlyDistributionChart' => $yearlyDistributionChart,
            'statusDistributionChart' => $statusDistributionChart,
            'monthlySummaryRows' => $monthlySummaryRows,
            'yearlySummaryRows' => $yearlySummaryRows,
            'generatedAt' => now(),
        ];
    }

    public function resolveTenantContext(?User $currentUser, ?int $requestedTenantId = null): array
    {
        $canSwitchTenant = (bool) $currentUser?->isAdminHr();
        $tenants = $canSwitchTenant
            ? Tenant::query()->orderBy('name')->get()
            : Tenant::query()->when($currentUser?->tenant_id, fn (Builder $query) => $query->whereKey($currentUser->tenant_id))->get();

        $fallbackTenant = $tenants->firstWhere('id', (int) $currentUser?->tenant_id) ?: $tenants->first();

        $tenant = $canSwitchTenant
            ? ($tenants->firstWhere('id', $requestedTenantId) ?: $fallbackTenant)
            : ($tenants->firstWhere('id', (int) $currentUser?->tenant_id) ?: $fallbackTenant);

        return [
            'tenants' => $tenants,
            'tenant' => $tenant,
            'can_switch_tenant' => $canSwitchTenant,
        ];
    }

    protected function resolveSelectedYear(?int $requestedYear, Collection $availableYears, Carbon $today): int
    {
        $selectedYear = (int) ($requestedYear ?: $today->year);

        return $availableYears->contains($selectedYear)
            ? $selectedYear
            : $today->year;
    }

    protected function resolveSelectedMonth(?int $requestedMonth, Carbon $today): int
    {
        $selectedMonth = (int) ($requestedMonth ?: $today->month);

        return $selectedMonth >= 1 && $selectedMonth <= 12
            ? $selectedMonth
            : $today->month;
    }

    protected function buildMonthlySummaryRows(Collection $attendances, Carbon $referenceMonth, array $statusMeta, array $shortMonthNames): array
    {
        $groupedAttendances = $attendances->groupBy(function ($attendance) {
            $attendanceDate = $attendance->date instanceof Carbon
                ? $attendance->date
                : Carbon::parse($attendance->date);

            return $attendanceDate->format('Y-m');
        });

        $rows = [];

        for ($offset = 11; $offset >= 0; $offset--) {
            $period = $referenceMonth->copy()->subMonths($offset);
            $periodAttendances = $groupedAttendances->get($period->format('Y-m'), collect());
            $statusCounts = $this->buildMappedStatusCounts($periodAttendances);
            $totalWorkMinutes = $periodAttendances->sum(fn ($attendance) => $this->calculateWorkMinutes($attendance->check_in, $attendance->check_out));

            $rows[] = [
                'year' => (int) $period->year,
                'month' => (int) $period->month,
                'month_label' => $shortMonthNames[(int) $period->month],
                'period_label' => $shortMonthNames[(int) $period->month].' '.$period->format('Y'),
                'present' => $statusCounts['present'],
                'leave' => $statusCounts['leave'],
                'sick' => $statusCounts['sick'],
                'absent' => $statusCounts['absent'],
                'total_attendances' => array_sum($statusCounts),
                'total_work_minutes' => $totalWorkMinutes,
                'total_work_hours_label' => $this->formatMinutesAsHours($totalWorkMinutes),
            ];
        }

        return $rows;
    }

    protected function buildYearlySummaryRows(Collection $attendances, int $selectedYear, array $statusMeta): array
    {
        $groupedAttendances = $attendances->groupBy(function ($attendance) {
            $attendanceDate = $attendance->date instanceof Carbon
                ? $attendance->date
                : Carbon::parse($attendance->date);

            return $attendanceDate->format('Y');
        });

        $rows = [];

        foreach (range($selectedYear - 4, $selectedYear) as $year) {
            $yearAttendances = $groupedAttendances->get((string) $year, collect());
            $statusCounts = $this->buildMappedStatusCounts($yearAttendances);
            $totalWorkMinutes = $yearAttendances->sum(fn ($attendance) => $this->calculateWorkMinutes($attendance->check_in, $attendance->check_out));

            $rows[] = [
                'year' => $year,
                'present' => $statusCounts['present'],
                'leave' => $statusCounts['leave'],
                'sick' => $statusCounts['sick'],
                'absent' => $statusCounts['absent'],
                'total_attendances' => array_sum($statusCounts),
                'total_work_minutes' => $totalWorkMinutes,
                'total_work_hours_label' => $this->formatMinutesAsHours($totalWorkMinutes),
            ];
        }

        return $rows;
    }

    protected function buildSummaryFromMonthlyRows(array $monthlySummaryRows): array
    {
        $selectedRow = collect($monthlySummaryRows)->last() ?: [
            'present' => 0,
            'leave' => 0,
            'sick' => 0,
            'absent' => 0,
            'total_attendances' => 0,
            'total_work_minutes' => 0,
            'total_work_hours_label' => $this->formatMinutesAsHours(0),
        ];

        return $this->buildSummaryPayload($selectedRow);
    }

    protected function buildSummaryFromYearlyRows(array $yearlySummaryRows, int $selectedYear): array
    {
        $selectedRow = collect($yearlySummaryRows)->firstWhere('year', $selectedYear) ?: [
            'present' => 0,
            'leave' => 0,
            'sick' => 0,
            'absent' => 0,
            'total_attendances' => 0,
            'total_work_minutes' => 0,
            'total_work_hours_label' => $this->formatMinutesAsHours(0),
        ];

        return $this->buildSummaryPayload($selectedRow);
    }

    protected function buildSummaryPayload(array $row): array
    {
        $statusCounts = [
            'present' => (int) ($row['present'] ?? 0),
            'leave' => (int) ($row['leave'] ?? 0),
            'sick' => (int) ($row['sick'] ?? 0),
            'absent' => (int) ($row['absent'] ?? 0),
        ];
        $totalAttendances = (int) ($row['total_attendances'] ?? array_sum($statusCounts));

        return [
            'total_attendances' => $totalAttendances,
            'status_counts' => $statusCounts,
            'percentages' => collect($statusCounts)->mapWithKeys(fn ($count, $key) => [
                $key => $totalAttendances > 0 ? round(($count / $totalAttendances) * 100, 1) : 0.0,
            ])->all(),
            'total_work_minutes' => (int) ($row['total_work_minutes'] ?? 0),
            'total_work_hours_label' => (string) ($row['total_work_hours_label'] ?? $this->formatMinutesAsHours((int) ($row['total_work_minutes'] ?? 0))),
        ];
    }

    protected function buildMonthlyTrendChart(array $monthlySummaryRows, array $statusMeta): array
    {
        $datasets = collect($statusMeta)->map(function (array $meta, string $statusKey) use ($monthlySummaryRows) {
            return [
                'label' => $meta['label'],
                'data' => array_map(fn (array $row) => (int) $row[$statusKey], $monthlySummaryRows),
                'borderColor' => $meta['color'],
                'backgroundColor' => $meta['color'],
                'tension' => 0.35,
                'fill' => false,
                'pointRadius' => 4,
                'pointHoverRadius' => 5,
                'pointBackgroundColor' => '#ffffff',
                'pointBorderWidth' => 2,
                'status_key' => $statusKey,
            ];
        })->values()->all();

        return [
            'labels' => array_map(fn (array $row) => $row['period_label'], $monthlySummaryRows),
            'datasets' => $datasets,
            'totals' => array_map(fn (array $row) => (int) $row['total_attendances'], $monthlySummaryRows),
        ];
    }

    protected function buildYearlyDistributionChart(array $yearlySummaryRows, array $statusMeta): array
    {
        $datasets = collect($statusMeta)->map(function (array $meta, string $statusKey) use ($yearlySummaryRows) {
            return [
                'label' => $meta['label'],
                'data' => array_map(fn (array $row) => (int) $row[$statusKey], $yearlySummaryRows),
                'backgroundColor' => $meta['color'],
                'borderColor' => $meta['color'],
                'borderRadius' => 8,
                'status_key' => $statusKey,
            ];
        })->values()->all();

        return [
            'labels' => array_map(fn (array $row) => (string) $row['year'], $yearlySummaryRows),
            'datasets' => $datasets,
            'totals' => array_map(fn (array $row) => (int) $row['total_attendances'], $yearlySummaryRows),
        ];
    }

    protected function buildStatusDistributionChart(array $monthSummary, array $statusMeta): array
    {
        $statusCounts = $monthSummary['status_counts'];

        return [
            'labels' => array_map(fn ($meta) => $meta['label'], $statusMeta),
            'counts' => array_map(fn ($statusKey) => $statusCounts[$statusKey] ?? 0, array_keys($statusMeta)),
            'percentages' => array_map(fn ($statusKey) => $monthSummary['percentages'][$statusKey] ?? 0.0, array_keys($statusMeta)),
            'backgroundColor' => array_map(fn ($meta) => $meta['color'], $statusMeta),
        ];
    }

    protected function buildMappedStatusCounts(Collection $attendances): array
    {
        $counts = [
            'present' => 0,
            'leave' => 0,
            'sick' => 0,
            'absent' => 0,
        ];

        foreach ($attendances as $attendance) {
            $normalizedStatus = $this->normalizeStatus($attendance->status);

            if ($normalizedStatus !== null) {
                $counts[$normalizedStatus]++;
            }
        }

        return $counts;
    }

    protected function normalizeStatus(?string $status): ?string
    {
        return match ($status) {
            'present', 'late' => 'present',
            'leave' => 'leave',
            'sick' => 'sick',
            'absent' => 'absent',
            default => null,
        };
    }

    protected function calculateWorkMinutes(?string $checkIn, ?string $checkOut): int
    {
        if (! $checkIn || ! $checkOut) {
            return 0;
        }

        [$checkInHour, $checkInMinute] = array_map('intval', explode(':', $checkIn));
        [$checkOutHour, $checkOutMinute] = array_map('intval', explode(':', $checkOut));

        return max(0, (($checkOutHour * 60) + $checkOutMinute) - (($checkInHour * 60) + $checkInMinute));
    }

    protected function formatMinutesAsHours(int $totalMinutes): string
    {
        $hours = intdiv($totalMinutes, 60);
        $minutes = $totalMinutes % 60;

        return sprintf('%d jam %02d menit', $hours, $minutes);
    }

    protected function monthNames(): array
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

    protected function shortMonthNames(): array
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

    protected function statusMeta(): array
    {
        return [
            'present' => ['label' => 'Hadir', 'color' => '#82d616'],
            'leave' => ['label' => 'Izin', 'color' => '#fbcf33'],
            'sick' => ['label' => 'Sakit', 'color' => '#17c1e8'],
            'absent' => ['label' => 'Alpha', 'color' => '#ea0606'],
        ];
    }

    protected function baseAttendanceQuery(?User $currentUser, ?int $tenantId = null): Builder
    {
        return Attendance::query()
            ->when($currentUser?->isManager(), fn (Builder $query) => $query->where('tenant_id', $currentUser->tenant_id))
            ->when($currentUser?->isAdminHr() && $tenantId, fn (Builder $query) => $query->where('tenant_id', $tenantId))
            ->when($currentUser?->isAdminHr() && ! $tenantId, fn (Builder $query) => $query->whereRaw('1 = 0'));
    }
}