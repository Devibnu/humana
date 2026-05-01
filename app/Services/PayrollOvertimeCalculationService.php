<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\DeductionRule;
use App\Models\Employee;
use App\Models\OvertimeRule;
use App\Models\Payroll;

class PayrollOvertimeCalculationService
{
    public function calculate(Employee $employee, $periodStart, $periodEnd, ?float $monthlySalary = null, ?float $dailyWage = null, ?string $salaryType = null): array
    {
        $salaryType ??= $this->resolveSalaryType($employee, $monthlySalary, $dailyWage);
        $rule = OvertimeRule::query()
            ->where('tenant_id', $employee->tenant_id)
            ->where('salary_type', $salaryType)
            ->first();

        if (! $rule) {
            return [
                'overtime_pay' => 0.0,
                'overtime_note' => 'Tidak ada rule lembur untuk tipe gaji yang dipilih',
            ];
        }

        [$effectiveMonthlySalary, $effectiveDailyWage] = $this->resolveSalaryValues($employee, $monthlySalary, $dailyWage);
        $ratePerHour = $this->resolveRatePerHour($employee, $rule, $salaryType, $effectiveMonthlySalary, $effectiveDailyWage);

        if ($ratePerHour <= 0) {
            return [
                'overtime_pay' => 0.0,
                'overtime_note' => 'Dasar gaji untuk perhitungan lembur tidak tersedia',
            ];
        }

        $attendances = Attendance::query()
            ->where('employee_id', $employee->id)
            ->whereBetween('date', [$periodStart, $periodEnd])
            ->get();

        $overtimePay = 0.0;
        $notes = [];

        foreach ($attendances as $attendance) {
            $overtimeHours = (float) ($attendance->overtime_hours ?? 0);

            if ($overtimeHours <= 0) {
                continue;
            }

            $firstHour = min(1, $overtimeHours);
            $nextHours = max(0, $overtimeHours - 1);

            $pay = ($firstHour * $ratePerHour * (float) $rule->rate_first_hour)
                + ($nextHours * $ratePerHour * (float) $rule->rate_next_hours);

            $overtimePay += $pay;
            $notes[] = 'Lembur '.rtrim(rtrim(number_format($overtimeHours, 2, '.', ''), '0'), '.').' jam -> Rp '.number_format($pay, 0, ',', '.');
        }

        return [
            'overtime_pay' => round($overtimePay, 2),
            'overtime_note' => $notes === [] ? '' : implode('; ', $notes),
        ];
    }

    protected function resolveSalaryType(Employee $employee, ?float $monthlySalary, ?float $dailyWage): string
    {
        if ($dailyWage !== null && $dailyWage > 0 && ($monthlySalary === null || $monthlySalary <= 0)) {
            return 'daily';
        }

        return 'monthly';
    }

    protected function resolveSalaryValues(Employee $employee, ?float $monthlySalary, ?float $dailyWage): array
    {
        if (($monthlySalary !== null && $monthlySalary > 0) || ($dailyWage !== null && $dailyWage > 0)) {
            return [$monthlySalary, $dailyWage];
        }

        $latestPayroll = Payroll::query()
            ->where('employee_id', $employee->id)
            ->orderByDesc('period_end')
            ->first();

        return [
            $latestPayroll?->monthly_salary,
            $latestPayroll?->daily_wage,
        ];
    }

    protected function resolveRatePerHour(Employee $employee, OvertimeRule $rule, string $salaryType, ?float $monthlySalary, ?float $dailyWage): float
    {
        if ($salaryType === 'daily') {
            return $dailyWage !== null && $dailyWage > 0
                ? (float) $dailyWage / max(1, (float) $rule->standard_hours_per_day)
                : 0.0;
        }

        $workingDaysPerMonth = DeductionRule::query()
            ->where('tenant_id', $employee->tenant_id)
            ->where('salary_type', 'monthly')
            ->value('working_days_per_month') ?? 22;

        $monthlyHours = max(1, (float) $rule->standard_hours_per_day * (float) $workingDaysPerMonth);

        return $monthlySalary !== null && $monthlySalary > 0
            ? (float) $monthlySalary / $monthlyHours
            : 0.0;
    }
}
