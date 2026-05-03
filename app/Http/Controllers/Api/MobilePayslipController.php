<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\AttendanceController;
use App\Models\Employee;
use App\Models\Payroll;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class MobilePayslipController extends AttendanceController
{
    public function __construct()
    {
        //
    }

    public function index(Request $request)
    {
        $employee = $this->mobileEmployee($request);
        $payslips = Payroll::query()
            ->where('employee_id', $employee->id)
            ->orderByDesc('period_start')
            ->latest('id')
            ->limit(24)
            ->get()
            ->map(fn (Payroll $payroll) => $this->serializePayroll($payroll))
            ->values();

        return response()->json([
            'employee' => [
                'id' => $employee->id,
                'name' => $employee->name,
                'employee_code' => $employee->employee_code,
            ],
            'summary' => [
                'total' => $payslips->count(),
                'latest_net_salary' => $payslips->first()['net_salary'] ?? 0,
            ],
            'data' => $payslips,
        ]);
    }

    public function show(Request $request, Payroll $payroll)
    {
        $employee = $this->mobileEmployee($request);

        abort_unless((int) $payroll->employee_id === (int) $employee->id, 403);

        return response()->json([
            'data' => $this->serializePayroll($payroll),
        ]);
    }

    protected function mobileEmployee(Request $request): Employee
    {
        $user = $request->user();

        abort_unless($user?->isEmployee(), 403);
        abort_unless($user->hasMenuAccess('payroll.slips'), 403);

        $employee = $this->resolveSelfEmployee($user);

        if (! $employee) {
            throw ValidationException::withMessages([
                'employee_id' => 'Akun Anda belum terhubung ke data karyawan.',
            ]);
        }

        return $employee;
    }

    protected function serializePayroll(Payroll $payroll): array
    {
        $baseSalary = $this->moneyValue($payroll->monthly_salary ?: $payroll->daily_wage);
        $allowances = [
            'transport' => $this->moneyValue($payroll->allowance_transport),
            'meal' => $this->moneyValue($payroll->allowance_meal),
            'health' => $this->moneyValue($payroll->allowance_health),
            'overtime' => $this->moneyValue($payroll->overtime_pay),
        ];
        $deductions = [
            'tax' => $this->moneyValue($payroll->deduction_tax),
            'bpjs' => $this->moneyValue($payroll->deduction_bpjs),
            'loan' => $this->moneyValue($payroll->deduction_loan),
            'attendance' => $this->moneyValue($payroll->deduction_attendance),
        ];
        $totalAllowances = array_sum($allowances);
        $totalDeductions = array_sum($deductions);

        $allowances['total'] = $totalAllowances;
        $deductions['total'] = $totalDeductions;

        return [
            'id' => $payroll->id,
            'period_start' => $payroll->period_start?->toDateString(),
            'period_end' => $payroll->period_end?->toDateString(),
            'base_salary' => $baseSalary,
            'monthly_salary' => $this->moneyValue($payroll->monthly_salary),
            'daily_wage' => $this->moneyValue($payroll->daily_wage),
            'allowances' => $allowances,
            'deductions' => $deductions,
            'net_salary' => $baseSalary + $totalAllowances - $totalDeductions,
            'overtime_note' => $payroll->overtime_note,
            'deduction_attendance_note' => $payroll->deduction_attendance_note,
        ];
    }

    protected function moneyValue($value): float
    {
        return (float) ($value ?? 0);
    }
}
