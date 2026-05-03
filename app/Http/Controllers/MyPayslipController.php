<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Leave;
use App\Models\Lembur;
use App\Models\LemburSetting;
use App\Models\Payroll;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MyPayslipController extends Controller
{
    public function index(Request $request): View
    {
        $employee = $this->resolveEmployee($request);

        $payslips = Payroll::query()
            ->where('employee_id', $employee->id)
            ->orderByDesc('period_start')
            ->latest('id')
            ->paginate(10);

        return view('payroll.my-slips.index', [
            'employee' => $employee,
            'payslips' => $payslips,
        ]);
    }

    public function show(Request $request, Payroll $payroll): View
    {
        $employee = $this->resolveEmployee($request);

        abort_unless((int) $payroll->employee_id === (int) $employee->id, 403);

        [$approvedLeaves, $approvedLemburs, $lemburTotalHours, $lemburTotalValue] = $this->resolvePeriodDetails($payroll);

        return view('payroll.show', [
            'payroll' => $payroll->load('employee.tenant'),
            'approvedLeaves' => $approvedLeaves,
            'approvedLemburs' => $approvedLemburs,
            'lemburTotalHours' => $lemburTotalHours,
            'lemburTotalValue' => $lemburTotalValue,
            'isSelfPayslip' => true,
        ]);
    }

    protected function resolveEmployee(Request $request): Employee
    {
        $employee = Employee::query()
            ->where('user_id', $request->user()->id)
            ->first();

        abort_unless($employee, 403);

        return $employee;
    }

    protected function resolvePeriodDetails(Payroll $payroll): array
    {
        $approvedLeaves = collect();
        $approvedLemburs = collect();
        $lemburTotalHours = 0.0;
        $lemburTotalValue = 0.0;

        if (! $payroll->employee_id || ! $payroll->period_start || ! $payroll->period_end) {
            return [$approvedLeaves, $approvedLemburs, $lemburTotalHours, $lemburTotalValue];
        }

        $approvedLeaves = Leave::with('leaveType')
            ->where('employee_id', $payroll->employee_id)
            ->where('status', 'approved')
            ->where('start_date', '<=', $payroll->period_end)
            ->where('end_date', '>=', $payroll->period_start)
            ->get();

        $approvedLemburs = Lembur::query()
            ->where('employee_id', $payroll->employee_id)
            ->where('status', 'disetujui')
            ->where('waktu_mulai', '<=', $payroll->period_end)
            ->where('waktu_selesai', '>=', $payroll->period_start)
            ->orderBy('waktu_mulai')
            ->get();

        $lemburTotalHours = (float) $approvedLemburs->sum(fn (Lembur $lembur) => (float) ($lembur->durasi_jam ?? 0));
        $setting = $payroll->employee?->tenant_id
            ? LemburSetting::query()->where('tenant_id', $payroll->employee->tenant_id)->first()
            : null;

        if ($setting?->tipe_tarif === 'per_jam') {
            $lemburTotalValue = $lemburTotalHours * (float) ($setting->nilai_tarif ?? 0);
        } elseif ($setting?->tipe_tarif === 'tetap') {
            $lemburTotalValue = $lemburTotalHours > 0 ? (float) ($setting->nilai_tarif ?? 0) : 0;
        } elseif ($setting?->tipe_tarif === 'multiplier') {
            $hourlyBase = 0.0;
            if ((float) ($payroll->daily_wage ?? 0) > 0) {
                $hourlyBase = ((float) $payroll->daily_wage) / 8;
            } elseif ((float) ($payroll->monthly_salary ?? 0) > 0) {
                $hourlyBase = ((float) $payroll->monthly_salary) / 173;
            }

            $lemburTotalValue = $lemburTotalHours * $hourlyBase * (float) ($setting->multiplier ?? 1);
        }

        return [$approvedLeaves, $approvedLemburs, $lemburTotalHours, $lemburTotalValue];
    }
}
