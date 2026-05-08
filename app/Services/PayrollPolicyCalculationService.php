<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Leave;
use App\Models\Payroll;
use App\Models\PayrollPolicy;
use App\Models\PayrollPolicyComponent;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

class PayrollPolicyCalculationService
{
    public function calculate(Employee $employee, $periodStart, $periodEnd, ?Payroll $template = null): array
    {
        $policy = $this->resolvePolicy($employee);

        if (! $policy) {
            return [
                'policy' => null,
                'items' => [],
                'totals' => ['earnings' => 0.0, 'deductions' => 0.0],
                'notes' => ['Tidak ada payroll policy aktif yang cocok.'],
            ];
        }

        $approvedLeaves = $this->approvedLeaves($employee, $periodStart, $periodEnd);
        $paidLeaveDays = $this->leaveDays($approvedLeaves, $periodStart, $periodEnd, true);
        $unpaidLeaveDays = $this->leaveDays($approvedLeaves, $periodStart, $periodEnd, false);
        $items = [];

        foreach ($policy->components()->orderBy('sort_order')->orderBy('id')->get() as $component) {
            $items = array_merge(
                $items,
                $this->calculateComponent($component, $employee, $periodStart, $periodEnd, $paidLeaveDays, $unpaidLeaveDays, $template),
            );
        }

        return [
            'policy' => $policy,
            'items' => $items,
            'totals' => [
                'earnings' => $this->sumItems($items, 'earning'),
                'deductions' => $this->sumItems($items, 'deduction'),
            ],
            'notes' => [],
        ];
    }

    public function syncItems(Payroll $payroll, array $items): void
    {
        $payroll->items()->delete();

        if ($items === []) {
            return;
        }

        $payroll->items()->createMany($items);
    }

    protected function resolvePolicy(Employee $employee): ?PayrollPolicy
    {
        return PayrollPolicy::query()
            ->where('tenant_id', $employee->tenant_id)
            ->where('status', 'active')
            ->where(function ($query) use ($employee): void {
                $query->whereNull('employment_type')
                    ->orWhere('employment_type', $employee->employment_type);
            })
            ->where(function ($query) use ($employee): void {
                $query->whereNull('employee_level_code')
                    ->orWhere('employee_level_code', $employee->role);
            })
            ->where(function ($query) use ($employee): void {
                $query->whereNull('position_id')
                    ->orWhere('position_id', $employee->position_id);
            })
            ->orderByRaw('CASE WHEN position_id IS NULL THEN 0 ELSE 1 END DESC')
            ->orderByRaw('CASE WHEN employee_level_code IS NULL THEN 0 ELSE 1 END DESC')
            ->orderByRaw('CASE WHEN employment_type IS NULL THEN 0 ELSE 1 END DESC')
            ->orderBy('priority')
            ->first();
    }

    protected function calculateComponent(
        PayrollPolicyComponent $component,
        Employee $employee,
        $periodStart,
        $periodEnd,
        int $paidLeaveDays,
        int $unpaidLeaveDays,
        ?Payroll $template,
    ): array {
        $amount = (float) $component->amount;
        $method = $component->calculation_method;

        if ($method === 'from_payroll_allowance') {
            $amount = $this->templateAllowanceAmount($template, $component->component_code);
        }

        if ($method === 'daily_attendance') {
            $workingDays = $this->workingDays($periodStart, $periodEnd);
            $payableDays = max(0, $workingDays - $unpaidLeaveDays);
            $deductedDays = $this->deductedLeaveDays($component, $paidLeaveDays, $unpaidLeaveDays);

            return array_filter([
                $this->item($component, 'earning', $payableDays * $amount, $payableDays, $amount, "{$payableDays} hari dibayar"),
                $deductedDays > 0
                    ? $this->item($component, 'deduction', $deductedDays * $amount, $deductedDays, $amount, "Potongan {$deductedDays} hari cuti/izin")
                    : null,
            ]);
        }

        if ($method === 'deduct_per_leave_day') {
            $deductedDays = $this->deductedLeaveDays($component, $paidLeaveDays, $unpaidLeaveDays);

            return $deductedDays > 0
                ? [$this->item($component, 'deduction', $deductedDays * $amount, $deductedDays, $amount, "Potongan {$deductedDays} hari cuti/izin")]
                : [];
        }

        if ($method === 'base_salary') {
            $amount = $this->baseSalary($template);
        }

        if ($amount <= 0) {
            return [];
        }

        return [$this->item($component, $component->component_type, $amount, 1, $amount, $component->notes)];
    }

    protected function item(PayrollPolicyComponent $component, string $type, float $amount, ?float $quantity, ?float $rate, ?string $note): array
    {
        return [
            'payroll_policy_component_id' => $component->id,
            'component_code' => $component->component_code,
            'component_name' => $component->component_name,
            'component_type' => $type,
            'amount' => round($amount, 2),
            'quantity' => $quantity,
            'rate' => $rate,
            'note' => $note,
            'metadata' => [
                'calculation_method' => $component->calculation_method,
                'policy_component_type' => $component->component_type,
            ],
        ];
    }

    protected function approvedLeaves(Employee $employee, $periodStart, $periodEnd): Collection
    {
        return Leave::query()
            ->with('leaveType')
            ->where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->where('start_date', '<=', $periodEnd)
            ->where('end_date', '>=', $periodStart)
            ->get();
    }

    protected function leaveDays(Collection $leaves, $periodStart, $periodEnd, bool $paid): int
    {
        $days = [];

        foreach ($leaves as $leave) {
            if ((bool) ($leave->leaveType?->is_paid ?? true) !== $paid) {
                continue;
            }

            foreach (CarbonPeriod::create(max($leave->start_date, $periodStart), min($leave->end_date, $periodEnd)) as $date) {
                $days[$date->toDateString()] = true;
            }
        }

        return count($days);
    }

    protected function deductedLeaveDays(PayrollPolicyComponent $component, int $paidLeaveDays, int $unpaidLeaveDays): int
    {
        $days = 0;

        if ($component->leave_paid_behavior === 'deduct_daily') {
            $days += $paidLeaveDays;
        }

        if ($component->leave_unpaid_behavior === 'deduct_daily') {
            $days += $unpaidLeaveDays;
        }

        return $days;
    }

    protected function workingDays($periodStart, $periodEnd): int
    {
        $days = 0;

        foreach (CarbonPeriod::create($periodStart, $periodEnd) as $date) {
            if (! $date->isWeekend()) {
                $days++;
            }
        }

        return $days;
    }

    protected function baseSalary(?Payroll $template): float
    {
        return (float) ($template?->monthly_salary ?? $template?->daily_wage ?? 0);
    }

    protected function templateAllowanceAmount(?Payroll $template, string $componentCode): float
    {
        return match ($componentCode) {
            'transport_allowance' => (float) ($template?->allowance_transport ?? 0),
            'meal_allowance' => (float) ($template?->allowance_meal ?? 0),
            'health_allowance' => (float) ($template?->allowance_health ?? 0),
            'thr_allowance' => (float) ($template?->allowance_thr ?? 0),
            'other_allowance' => (float) ($template?->allowance_other ?? 0),
            default => 0.0,
        };
    }

    protected function sumItems(array $items, string $type): float
    {
        return round(array_sum(array_map(
            fn (array $item) => $item['component_type'] === $type || ($type === 'earning' && $item['component_type'] === 'bonus') ? (float) $item['amount'] : 0,
            $items,
        )), 2);
    }
}
