<?php

namespace App\Services;

use App\Models\AbsenceRule;
use App\Models\DeductionRule;
use App\Models\Payroll;
use App\Models\Employee;

class PayrollAttendanceCalculationService
{
    public function calculate(Employee $employee, $periodStart, $periodEnd, ?float $monthlySalary = null, ?float $dailyWage = null): array
    {
        return $this->calculateAttendanceDeductionForEmployee(
            $employee,
            $periodStart,
            $periodEnd,
            $monthlySalary,
            $dailyWage,
        );
    }

    public function calculateWithRule(Employee $employee, DeductionRule $rule, $periodStart, $periodEnd, ?float $monthlySalary = null, ?float $dailyWage = null): array
    {
        return $this->calculateAttendanceDeductionForEmployee(
            $employee,
            $periodStart,
            $periodEnd,
            $monthlySalary,
            $dailyWage,
            $rule,
        );
    }

    /**
     * Get absence rule for a payroll's tenant (employee's tenant).
     */
    public function getRuleForPayroll(Payroll $payroll): ?AbsenceRule
    {
        $tenantId = $payroll->employee?->tenant_id;
        if (! $tenantId) return null;

        return AbsenceRule::where('tenant_id', $tenantId)->first();
    }

    /**
     * Calculate rate per hour based on payroll values and rule.
     */
    public function calculateRatePerHour(Payroll $payroll): ?float
    {
        $rule = $this->getRuleForPayroll($payroll);
        if (! $rule) return null;

        if ($payroll->daily_wage !== null) {
            return (float) $payroll->daily_wage / (float) $rule->working_hours_per_day;
        }

        if ($payroll->monthly_salary !== null) {
            $hoursPerMonth = (float) $rule->working_days_per_month * (float) $rule->working_hours_per_day;
            if ($hoursPerMonth <= 0) return null;
            return (float) $payroll->monthly_salary / $hoursPerMonth;
        }

        return null;
    }

    /**
     * Get tenant deduction rule.
     */
    public function getDeductionRuleForTenant(int $tenantId): ?DeductionRule
    {
        return DeductionRule::where('tenant_id', $tenantId)->first();
    }

    /**
     * Calculate rate per hour for an employee using tenant's deduction rule.
     * Accepts optional salary values; otherwise tries to use latest payroll.
     */
    public function calculateRatePerHourForEmployee(Employee $employee, ?float $monthlySalary = null, ?float $dailyWage = null): ?float
    {
        $rule = $this->getDeductionRuleForTenant($employee->tenant_id);
        if (! $rule) return null;

        // Try provided values, otherwise fallback to latest payroll entry.
        if ($monthlySalary === null && $dailyWage === null) {
            $latest = Payroll::where('employee_id', $employee->id)->orderByDesc('period_end')->first();
            if ($latest) {
                $monthlySalary = $latest->monthly_salary;
                $dailyWage = $latest->daily_wage;
            }
        }

        if ($dailyWage !== null && $dailyWage > 0) {
            return (float) $dailyWage / (float) $rule->working_hours_per_day;
        }

        if ($monthlySalary !== null && $monthlySalary > 0) {
            $hoursPerMonth = (float) $rule->working_days_per_month * (float) $rule->working_hours_per_day;
            if ($hoursPerMonth <= 0) return null;
            return (float) $monthlySalary / $hoursPerMonth;
        }

        return null;
    }

    /**
     * Calculate attendance-based deduction for an employee between two dates.
     * Returns array with keys: deduction_attendance (float) and deduction_attendance_note (string).
     */
    public function calculateAttendanceDeductionForEmployee(Employee $employee, $periodStart, $periodEnd, ?float $monthlySalary = null, ?float $dailyWage = null, ?DeductionRule $selectedRule = null): array
    {
        $rule = $selectedRule ?: $this->getDeductionRuleForTenant($employee->tenant_id);
        if (! $rule) {
            return ['deduction_attendance' => 0.0, 'deduction_attendance_note' => 'Tidak ada rule potongan untuk tenant'];
        }

        $attendances = \App\Models\Attendance::where('employee_id', $employee->id)
            ->whereBetween('date', [$periodStart, $periodEnd])
            ->get();

        $deduction = 0.0;
        $notes = [];

        $effectiveDailyWage = $dailyWage;
        $effectiveMonthlySalary = $monthlySalary;

        if ($effectiveDailyWage === null && $effectiveMonthlySalary === null) {
            $latestPayroll = Payroll::where('employee_id', $employee->id)->orderByDesc('period_end')->first();
            $effectiveDailyWage = $latestPayroll?->daily_wage;
            $effectiveMonthlySalary = $latestPayroll?->monthly_salary;
        }

        $ratePerHour = $effectiveDailyWage !== null && (float) $effectiveDailyWage > 0
            ? (float) $effectiveDailyWage / (float) $rule->working_hours_per_day
            : ((float) $effectiveMonthlySalary / max(1, ((float) $rule->working_days_per_month * (float) $rule->working_hours_per_day)));

        foreach ($attendances as $a) {
            $status = strtolower((string) ($a->status ?? ''));

            // Late
            if (in_array($status, ['telat', 'late'], true)) {
                $late = (int) ($a->late_minutes ?? 0);
                if ($late > $rule->tolerance_minutes) {
                    $amount = ($late / 60) * $ratePerHour;
                    $deduction += $amount;
                    $notes[] = "Telat {$late} menit → Rp " . number_format($amount, 0, ',', '.');
                }
            }

            // Early leave
            if (in_array($status, ['earlyleave', 'early_leave'], true)) {
                $early = (int) ($a->early_leave_minutes ?? 0);
                if ($early > $rule->tolerance_minutes) {
                    $amount = ($early / 60) * $ratePerHour;
                    $deduction += $amount;
                    $notes[] = "Pulang cepat {$early} menit → Rp " . number_format($amount, 0, ',', '.');
                }
            }

            // Alpha (absent)
            if (in_array($status, ['alpha', 'absent'], true) && $rule->alpha_full_day) {
                $amount = $effectiveDailyWage !== null && (float) $effectiveDailyWage > 0
                    ? (float) $effectiveDailyWage
                    : ((float) $effectiveMonthlySalary / max(1, (float) $rule->working_days_per_month));
                $deduction += $amount;
                $notes[] = "Alpha → potong penuh Rp " . number_format($amount, 0, ',', '.');
            }
        }

        return [
            'deduction_attendance' => round($deduction, 2),
            'deduction_attendance_note' => count($notes) ? implode('; ', $notes) : '',
        ];
    }
}
