<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\DeductionRule;
use App\Models\Leave;
use App\Models\Lembur;
use App\Models\LemburSetting;
use App\Models\Payroll;
use App\Models\PayrollPeriod;
use App\Models\PayrollSetting;
use App\Models\Tenant;
use App\Services\PayrollAttendanceCalculationService;
use App\Services\PayrollOvertimeCalculationService;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PayrollController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:payroll');
    }

    public function index(Request $request): View
    {
        [$search, $selectedTenantId, $selectedSortBy, $selectedSortDirection] = $this->resolveIndexFilters($request);

        $payrollQuery = $this->buildPayrollIndexQuery($search, $selectedTenantId);
        $this->applyPayrollSorting($payrollQuery, $selectedSortBy, $selectedSortDirection);

        $payrolls = (clone $payrollQuery)
            ->with(['employee.tenant', 'payrollPeriod'])
            ->paginate(10)
            ->withQueryString();

        $summaryQuery = $this->buildPayrollIndexQuery($search, $selectedTenantId);
        $summary = [
            'total' => (clone $summaryQuery)->count(),
            'monthly_salary_total' => (float) (clone $summaryQuery)->sum('monthly_salary'),
            'overtime_total' => (float) (clone $summaryQuery)->sum('overtime_pay'),
            'deduction_total' => (float) (
                (clone $summaryQuery)->sum('deduction_tax')
                + (clone $summaryQuery)->sum('deduction_bpjs')
                + (clone $summaryQuery)->sum('deduction_loan')
                + (clone $summaryQuery)->sum('deduction_attendance')
            ),
        ];

        $tenants = Tenant::query()->orderBy('name')->get();
        $periods = PayrollPeriod::query()
            ->with('tenant')
            ->orderByDesc('payroll_month')
            ->latest('id')
            ->limit(6)
            ->get();
        $selectedTenantName = $selectedTenantId !== null
            ? optional($tenants->firstWhere('id', $selectedTenantId))->name
            : null;

        return view('payroll.index', [
            'payrolls' => $payrolls,
            'summary' => $summary,
            'tenants' => $tenants,
            'search' => $search,
            'selectedTenantId' => $selectedTenantId,
            'selectedTenantName' => $selectedTenantName,
            'selectedSortBy' => $selectedSortBy,
            'selectedSortDirection' => $selectedSortDirection,
            'sortOptions' => $this->payrollSortOptions(),
            'periods' => $periods,
        ]);
    }

    public function show(Request $request, Payroll $payroll): View
    {
        $this->ensurePayrollAccessible($request, $payroll);

        $payroll->load('employee.tenant');

        $approvedLeaves = collect();
        if ($payroll->employee_id && $payroll->period_start && $payroll->period_end) {
            $approvedLeaves = Leave::with('leaveType')
                ->where('employee_id', $payroll->employee_id)
                ->where('status', 'approved')
                ->where('start_date', '<=', $payroll->period_end)
                ->where('end_date', '>=', $payroll->period_start)
                ->get();
        }

        $approvedLemburs = collect();
        $lemburTotalHours = 0.0;
        $lemburTotalValue = 0.0;
        if ($payroll->employee_id && $payroll->period_start && $payroll->period_end) {
            $approvedLemburs = Lembur::query()
                ->where('employee_id', $payroll->employee_id)
                ->where('status', 'disetujui')
                ->where('waktu_mulai', '<=', $payroll->period_end)
                ->where('waktu_selesai', '>=', $payroll->period_start)
                ->orderBy('waktu_mulai')
                ->get();

            $lemburTotalHours = (float) $approvedLemburs->sum(fn (Lembur $lembur) => (float) ($lembur->durasi_jam ?? 0));

            $setting = null;
            if ($payroll->employee?->tenant_id) {
                $setting = LemburSetting::query()->where('tenant_id', $payroll->employee->tenant_id)->first();
            }

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
        }

        return view('payroll.show', [
            'payroll' => $payroll,
            'approvedLeaves' => $approvedLeaves,
            'approvedLemburs' => $approvedLemburs,
            'lemburTotalHours' => $lemburTotalHours,
            'lemburTotalValue' => $lemburTotalValue,
        ]);
    }

    public function create(Request $request): View
    {
        $currentUser = $request->user() ?? auth()->user();

        $employees = Employee::query()
            ->with(['tenant', 'department', 'position'])
            ->when($currentUser?->isManager(), fn ($query) => $query->where('tenant_id', $currentUser->tenant_id))
            ->orderBy('name')
            ->get();

        $rules = DeductionRule::query()
            ->when($currentUser?->tenant_id, fn ($query) => $query->where('tenant_id', $currentUser->tenant_id))
            ->orderBy('tenant_id')
            ->orderBy('working_hours_per_day')
            ->get();

        return view('payroll.create', [
            'employees' => $employees,
            'rules' => $rules,
        ]);
    }

    public function settings(Request $request): View
    {
        $currentUser = $request->user() ?? auth()->user();
        $tenants = Tenant::query()
            ->with('payrollSetting')
            ->when($currentUser?->isManager(), fn ($query) => $query->where('id', $currentUser->tenant_id))
            ->orderBy('name')
            ->get();

        return view('payroll.settings', [
            'tenants' => $tenants,
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $currentUser = $request->user() ?? auth()->user();

        $payload = $request->validate([
            'tenant_id' => [
                'required',
                Rule::exists('tenants', 'id')->where(function ($query) use ($currentUser) {
                    if ($currentUser?->isManager()) {
                        $query->where('id', $currentUser->tenant_id);
                    }
                }),
            ],
            'payroll_day' => ['required', 'integer', 'min:1', 'max:31'],
            'period_start_day' => ['required', 'integer', 'min:1', 'max:31'],
            'period_end_day' => ['required', 'integer', 'min:1', 'max:31'],
            'period_month_offset' => ['required', Rule::in(['current', 'previous'])],
            'publish_slips_on_approval' => ['nullable', 'boolean'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $payload['publish_slips_on_approval'] = $request->boolean('publish_slips_on_approval');

        PayrollSetting::updateOrCreate(
            ['tenant_id' => $payload['tenant_id']],
            $payload
        );

        return redirect()
            ->route('payroll.settings')
            ->with('success', 'Pengaturan payroll perusahaan berhasil disimpan.');
    }

    public function generateForm(Request $request): View
    {
        $currentUser = $request->user() ?? auth()->user();
        $tenants = Tenant::query()
            ->with('payrollSetting')
            ->when($currentUser?->isManager(), fn ($query) => $query->where('id', $currentUser->tenant_id))
            ->orderBy('name')
            ->get();

        return view('payroll.generate', [
            'tenants' => $tenants,
            'defaultPayrollMonth' => now()->format('Y-m'),
        ]);
    }

    public function generate(Request $request): RedirectResponse
    {
        $currentUser = $request->user() ?? auth()->user();

        $payload = $request->validate([
            'tenant_id' => [
                'required',
                Rule::exists('tenants', 'id')->where(function ($query) use ($currentUser) {
                    if ($currentUser?->isManager()) {
                        $query->where('id', $currentUser->tenant_id);
                    }
                }),
            ],
            'payroll_month' => ['required', 'date_format:Y-m'],
        ]);

        $tenant = Tenant::with('payrollSetting')->findOrFail($payload['tenant_id']);
        $setting = $tenant->payrollSetting ?: PayrollSetting::create([
            'tenant_id' => $tenant->id,
        ]);

        $dates = $setting->periodDatesForMonth($payload['payroll_month'].'-01');
        $payrollMonth = Carbon::parse($dates['payroll_month']);

        $period = PayrollPeriod::firstOrCreate(
            [
                'tenant_id' => $tenant->id,
                'payroll_month' => $dates['payroll_month'],
            ],
            [
                'payroll_setting_id' => $setting->id,
                'name' => $tenant->name.' - Payroll '.$payrollMonth->translatedFormat('F Y'),
                'period_start' => $dates['period_start'],
                'period_end' => $dates['period_end'],
                'payroll_date' => $dates['payroll_date'],
                'status' => 'draft',
                'generated_at' => now(),
                'created_by' => $currentUser?->id,
            ]
        );

        $employees = Employee::query()
            ->where('tenant_id', $tenant->id)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $created = 0;
        $skipped = 0;

        foreach ($employees as $employee) {
            if (Payroll::where('payroll_period_id', $period->id)->where('employee_id', $employee->id)->exists()) {
                $skipped++;
                continue;
            }

            $template = Payroll::query()
                ->where('employee_id', $employee->id)
                ->where(function (Builder $query) use ($period): void {
                    $query->whereNull('payroll_period_id')
                        ->orWhere('payroll_period_id', '!=', $period->id);
                })
                ->latest('period_end')
                ->latest('id')
                ->first();

            $rule = $this->resolveDeductionRuleForGeneratedPayroll($employee, $template);

            if (! $template || ! $rule) {
                $skipped++;
                continue;
            }

            $payload = $this->buildGeneratedPayrollPayload($employee, $template, $rule, $period);
            Payroll::create($payload);
            $created++;
        }

        return redirect()
            ->route('payroll.index', ['tenant_id' => $tenant->id])
            ->with('success', "Generate payroll selesai. {$created} payroll dibuat, {$skipped} dilewati.");
    }

    public function edit(Request $request, Payroll $payroll): View
    {
        $this->ensurePayrollAccessible($request, $payroll);

        $currentUser = $request->user() ?? auth()->user();

        $employees = Employee::query()
            ->with(['tenant', 'department', 'position'])
            ->when($currentUser?->isManager(), fn ($query) => $query->where('tenant_id', $currentUser->tenant_id))
            ->orderBy('name')
            ->get();

        $rules = DeductionRule::query()
            ->when($currentUser?->tenant_id, fn ($query) => $query->where('tenant_id', $currentUser->tenant_id))
            ->orderBy('tenant_id')
            ->orderBy('working_hours_per_day')
            ->get();

        return view('payroll.edit', [
            'employees' => $employees,
            'rules' => $rules,
            'payroll' => $payroll->load('employee'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $payload = $this->validatedPayload($request);

        $employee = Employee::findOrFail($payload['employee_id']);
        $rule = DeductionRule::where('tenant_id', $employee->tenant_id)
            ->findOrFail($payload['deduction_rule_id']);

        if (($payload['period_start'] ?? null) && ($payload['period_end'] ?? null)) {
            $calc = app(PayrollAttendanceCalculationService::class)->calculateWithRule(
                $employee,
                $rule,
                $payload['period_start'],
                $payload['period_end'],
                isset($payload['monthly_salary']) ? (float) $payload['monthly_salary'] : null,
                isset($payload['daily_wage']) ? (float) $payload['daily_wage'] : null,
            );

            $payload['deduction_attendance'] = $calc['deduction_attendance'];
            $payload['deduction_attendance_note'] = $calc['deduction_attendance_note'];

            $overtime = app(PayrollOvertimeCalculationService::class)->calculate(
                $employee,
                $payload['period_start'],
                $payload['period_end'],
                isset($payload['monthly_salary']) ? (float) $payload['monthly_salary'] : null,
                isset($payload['daily_wage']) ? (float) $payload['daily_wage'] : null,
                $rule->salary_type,
            );
            $payload['overtime_pay'] = $overtime['overtime_pay'];
            $payload['overtime_note'] = $overtime['overtime_note'];
        } else {
            $payload['deduction_attendance'] = 0;
            $payload['deduction_attendance_note'] = '';
            $payload['overtime_pay'] = 0;
            $payload['overtime_note'] = '';
        }

        $payroll = new Payroll($payload);
        $payroll->deduction_rule_id = $rule->id;
        $payroll->save();

        return redirect()
            ->route('payroll.index')
            ->with('success', 'Payroll berhasil disimpan dengan aturan potongan terpilih');
    }

    public function update(Request $request, Payroll $payroll): RedirectResponse
    {
        $this->ensurePayrollAccessible($request, $payroll);

        $payload = $this->validatedPayload($request);

        $employee = Employee::findOrFail($payload['employee_id']);
        $rule = DeductionRule::where('tenant_id', $employee->tenant_id)
            ->findOrFail($payload['deduction_rule_id']);

        if (($payload['period_start'] ?? null) && ($payload['period_end'] ?? null)) {
            $calc = app(PayrollAttendanceCalculationService::class)->calculateWithRule(
                $employee,
                $rule,
                $payload['period_start'],
                $payload['period_end'],
                isset($payload['monthly_salary']) ? (float) $payload['monthly_salary'] : null,
                isset($payload['daily_wage']) ? (float) $payload['daily_wage'] : null,
            );

            $payload['deduction_attendance'] = $calc['deduction_attendance'];
            $payload['deduction_attendance_note'] = $calc['deduction_attendance_note'];

            $overtime = app(PayrollOvertimeCalculationService::class)->calculate(
                $employee,
                $payload['period_start'],
                $payload['period_end'],
                isset($payload['monthly_salary']) ? (float) $payload['monthly_salary'] : null,
                isset($payload['daily_wage']) ? (float) $payload['daily_wage'] : null,
                $rule->salary_type,
            );
            $payload['overtime_pay'] = $overtime['overtime_pay'];
            $payload['overtime_note'] = $overtime['overtime_note'];
        } else {
            $payload['deduction_attendance'] = 0;
            $payload['deduction_attendance_note'] = '';
            $payload['overtime_pay'] = 0;
            $payload['overtime_note'] = '';
        }

        $payload['deduction_rule_id'] = $rule->id;
        $payroll->fill($payload);
        $payroll->save();

        return redirect()
            ->route('payroll.index')
            ->with('success', 'Payroll berhasil diupdate dengan aturan potongan terpilih');
    }

    public function destroy(Request $request, Payroll $payroll): RedirectResponse
    {
        $this->ensurePayrollAccessible($request, $payroll);

        $payroll->delete();

        return redirect()
            ->route('payroll.index')
            ->with('success', 'Payroll berhasil dihapus');
    }

    protected function resolveDeductionRuleForGeneratedPayroll(Employee $employee, ?Payroll $template): ?DeductionRule
    {
        if ($template?->deduction_rule_id) {
            return DeductionRule::where('tenant_id', $employee->tenant_id)->find($template->deduction_rule_id);
        }

        $salaryType = ($template?->daily_wage ?? 0) > 0 && ! (($template?->monthly_salary ?? 0) > 0)
            ? 'daily'
            : 'monthly';

        return DeductionRule::query()
            ->where('tenant_id', $employee->tenant_id)
            ->where('salary_type', $salaryType)
            ->first();
    }

    protected function buildGeneratedPayrollPayload(Employee $employee, Payroll $template, DeductionRule $rule, PayrollPeriod $period): array
    {
        $payload = [
            'payroll_period_id' => $period->id,
            'employee_id' => $employee->id,
            'deduction_rule_id' => $rule->id,
            'status' => 'draft',
            'monthly_salary' => $template->monthly_salary,
            'daily_wage' => $template->daily_wage,
            'allowance_transport' => $template->allowance_transport,
            'allowance_meal' => $template->allowance_meal,
            'allowance_health' => $template->allowance_health,
            'deduction_tax' => $template->deduction_tax,
            'deduction_bpjs' => $template->deduction_bpjs,
            'deduction_loan' => $template->deduction_loan,
            'period_start' => $period->period_start,
            'period_end' => $period->period_end,
        ];

        $calc = app(PayrollAttendanceCalculationService::class)->calculateWithRule(
            $employee,
            $rule,
            $period->period_start,
            $period->period_end,
            isset($payload['monthly_salary']) ? (float) $payload['monthly_salary'] : null,
            isset($payload['daily_wage']) ? (float) $payload['daily_wage'] : null,
        );

        $overtime = app(PayrollOvertimeCalculationService::class)->calculate(
            $employee,
            $period->period_start,
            $period->period_end,
            isset($payload['monthly_salary']) ? (float) $payload['monthly_salary'] : null,
            isset($payload['daily_wage']) ? (float) $payload['daily_wage'] : null,
            $rule->salary_type,
        );

        return array_merge($payload, [
            'deduction_attendance' => $calc['deduction_attendance'],
            'deduction_attendance_note' => $calc['deduction_attendance_note'],
            'overtime_pay' => $overtime['overtime_pay'],
            'overtime_note' => $overtime['overtime_note'],
        ]);
    }

    protected function validatedPayload(Request $request): array
    {
        $currentUser = $request->user() ?? auth()->user();

        return $request->validate([
            'employee_id' => [
                'required',
                Rule::exists('employees', 'id')->where(function ($query) use ($currentUser) {
                    if ($currentUser?->isManager()) {
                        $query->where('tenant_id', $currentUser->tenant_id);
                    }
                }),
            ],
            'deduction_rule_id' => ['required', 'integer', Rule::exists('deduction_rules', 'id')],
            'monthly_salary' => ['nullable', 'numeric', 'min:0', 'required_without:daily_wage'],
            'daily_wage' => ['nullable', 'numeric', 'min:0', 'required_without:monthly_salary'],
            'allowance_transport' => ['nullable', 'numeric', 'min:0'],
            'allowance_meal' => ['nullable', 'numeric', 'min:0'],
            'allowance_health' => ['nullable', 'numeric', 'min:0'],
            'deduction_tax' => ['nullable', 'numeric', 'min:0'],
            'deduction_bpjs' => ['nullable', 'numeric', 'min:0'],
            'deduction_loan' => ['nullable', 'numeric', 'min:0'],
            'deduction_attendance' => ['nullable', 'numeric', 'min:0'],
            'deduction_attendance_note' => ['nullable', 'string', 'max:255'],
            'period_start' => ['nullable', 'date'],
            'period_end' => ['nullable', 'date', 'after_or_equal:period_start'],
        ]);
    }

    protected function resolveIndexFilters(Request $request): array
    {
        $search = trim((string) $request->query('search', ''));
        $tenantId = $request->query('tenant_id');
        $sortOptions = $this->payrollSortOptions();
        $selectedSortBy = (string) $request->query('sort_by', 'latest_period');
        $selectedSortDirection = strtolower((string) $request->query('sort_direction', 'desc'));

        if (! array_key_exists($selectedSortBy, $sortOptions)) {
            $selectedSortBy = 'latest_period';
        }

        if (! in_array($selectedSortDirection, ['asc', 'desc'], true)) {
            $selectedSortDirection = 'desc';
        }

        return [
            $search,
            is_numeric($tenantId) ? (int) $tenantId : null,
            $selectedSortBy,
            $selectedSortDirection,
        ];
    }

    protected function buildPayrollIndexQuery(string $search, ?int $tenantId): Builder
    {
        return Payroll::query()
            ->whereHas('employee', function (Builder $query) use ($search, $tenantId): void {
                if ($tenantId !== null) {
                    $query->where('tenant_id', $tenantId);
                }

                if ($search === '') {
                    return;
                }

                $query->where(function (Builder $employeeQuery) use ($search): void {
                    $employeeQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('employee_code', 'like', "%{$search}%")
                        ->orWhereHas('tenant', function (Builder $tenantQuery) use ($search): void {
                            $tenantQuery->where('name', 'like', "%{$search}%");
                        });
                });
            });
    }

    protected function applyPayrollSorting(Builder $query, string $sortBy, string $direction): void
    {
        switch ($sortBy) {
            case 'employee_name':
                $query
                    ->join('employees', 'employees.id', '=', 'payrolls.employee_id')
                    ->select('payrolls.*')
                    ->orderBy('employees.name', $direction)
                    ->orderBy('payrolls.id', 'desc');
                break;

            case 'monthly_salary':
                $query
                    ->orderBy('payrolls.monthly_salary', $direction)
                    ->orderBy('payrolls.period_start', 'desc')
                    ->orderBy('payrolls.id', 'desc');
                break;

            case 'overtime_pay':
                $query
                    ->orderBy('payrolls.overtime_pay', $direction)
                    ->orderBy('payrolls.period_start', 'desc')
                    ->orderBy('payrolls.id', 'desc');
                break;

            case 'oldest_period':
                $query
                    ->orderBy('payrolls.period_start', 'asc')
                    ->orderBy('payrolls.id', 'asc');
                break;

            case 'latest_period':
            default:
                $query
                    ->orderBy('payrolls.period_start', 'desc')
                    ->orderBy('payrolls.id', 'desc');
                break;
        }
    }

    protected function payrollSortOptions(): array
    {
        return [
            'latest_period' => 'Periode Terbaru',
            'oldest_period' => 'Periode Terlama',
            'employee_name' => 'Nama Karyawan',
            'monthly_salary' => 'Gaji Bulanan',
            'overtime_pay' => 'Nilai Lembur',
        ];
    }

    protected function ensurePayrollAccessible(Request $request, Payroll $payroll): void
    {
        $currentUser = $request->user() ?? auth()->user();

        if ($currentUser?->isManager() && $payroll->employee?->tenant_id !== $currentUser->tenant_id) {
            throw new AuthorizationException();
        }
    }
}
