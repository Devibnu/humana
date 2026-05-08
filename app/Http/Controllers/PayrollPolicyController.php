<?php

namespace App\Http\Controllers;

use App\Models\EmployeeLevel;
use App\Models\PayrollPolicy;
use App\Models\Position;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PayrollPolicyController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:payroll');
    }

    public function index(Request $request): View
    {
        $currentUser = $request->user() ?? auth()->user();
        $tenantId = $this->resolveTenantId($request);

        $policies = PayrollPolicy::query()
            ->with(['tenant', 'position', 'components'])
            ->when($tenantId, fn ($query) => $query->where('tenant_id', $tenantId))
            ->when($currentUser?->isManager(), fn ($query) => $query->where('tenant_id', $currentUser->tenant_id))
            ->orderBy('tenant_id')
            ->orderBy('priority')
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('payroll.policies.index', [
            'policies' => $policies,
            'tenants' => $this->tenantOptions($request),
            'selectedTenantId' => $tenantId,
        ]);
    }

    public function create(Request $request): View
    {
        $tenantId = $this->resolveTenantId($request);

        return view('payroll.policies.create', $this->formData(new PayrollPolicy(['tenant_id' => $tenantId]), $request));
    }

    public function store(Request $request): RedirectResponse
    {
        $payload = $this->validatedPayload($request);
        $components = $payload['components'] ?? [];
        unset($payload['components']);

        $policy = PayrollPolicy::create($payload);
        $this->syncComponents($policy, $components);

        return redirect()
            ->route('payroll.policies.index', ['tenant_id' => $policy->tenant_id])
            ->with('success', 'Payroll policy berhasil disimpan.');
    }

    public function edit(Request $request, PayrollPolicy $policy): View
    {
        $this->ensurePolicyAccessible($request, $policy);

        return view('payroll.policies.edit', $this->formData($policy->load('components'), $request));
    }

    public function update(Request $request, PayrollPolicy $policy): RedirectResponse
    {
        $this->ensurePolicyAccessible($request, $policy);

        $payload = $this->validatedPayload($request, $policy);
        $components = $payload['components'] ?? [];
        unset($payload['components']);

        $policy->update($payload);
        $this->syncComponents($policy, $components);

        return redirect()
            ->route('payroll.policies.index', ['tenant_id' => $policy->tenant_id])
            ->with('success', 'Payroll policy berhasil diperbarui.');
    }

    public function destroy(Request $request, PayrollPolicy $policy): RedirectResponse
    {
        $this->ensurePolicyAccessible($request, $policy);
        $tenantId = $policy->tenant_id;
        $policy->delete();

        return redirect()
            ->route('payroll.policies.index', ['tenant_id' => $tenantId])
            ->with('success', 'Payroll policy berhasil dihapus.');
    }

    protected function validatedPayload(Request $request, ?PayrollPolicy $policy = null): array
    {
        $currentUser = $request->user() ?? auth()->user();
        $tenantId = $currentUser?->isManager()
            ? (int) $currentUser->tenant_id
            : (int) $request->input('tenant_id');

        $request->merge(['tenant_id' => $tenantId]);

        return $request->validate([
            'tenant_id' => ['required', Rule::exists('tenants', 'id')],
            'name' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'employment_type' => ['nullable', Rule::in(['tetap', 'kontrak'])],
            'employee_level_code' => ['nullable', 'string', 'max:50'],
            'position_id' => [
                'nullable',
                Rule::exists('positions', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'priority' => ['required', 'integer', 'min:1', 'max:999'],
            'components' => ['nullable', 'array'],
            'components.*.component_code' => ['nullable', 'string', 'max:80'],
            'components.*.component_name' => ['nullable', 'string', 'max:255'],
            'components.*.component_type' => ['nullable', Rule::in(['earning', 'deduction', 'bonus'])],
            'components.*.amount' => ['nullable', 'numeric', 'min:0'],
            'components.*.calculation_method' => ['nullable', Rule::in(['flat', 'daily_attendance', 'deduct_per_leave_day', 'base_salary', 'from_payroll_allowance'])],
            'components.*.leave_paid_behavior' => ['nullable', Rule::in(['keep', 'deduct_daily'])],
            'components.*.leave_unpaid_behavior' => ['nullable', Rule::in(['keep', 'deduct_daily'])],
            'components.*.attendance_based' => ['nullable', 'boolean'],
            'components.*.taxable' => ['nullable', 'boolean'],
            'components.*.sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'components.*.notes' => ['nullable', 'string', 'max:1000'],
        ]);
    }

    protected function syncComponents(PayrollPolicy $policy, array $components): void
    {
        $rows = collect($components)
            ->filter(fn (array $component) => trim((string) ($component['component_code'] ?? '')) !== '' || trim((string) ($component['component_name'] ?? '')) !== '')
            ->map(function (array $component) {
                $name = trim((string) ($component['component_name'] ?? ''));
                $code = trim((string) ($component['component_code'] ?? '')) ?: $this->componentCodeFromName($name);

                return [
                    'component_code' => $code,
                    'component_name' => $name,
                    'component_type' => $component['component_type'] ?? 'earning',
                    'amount' => $component['amount'] ?? 0,
                    'calculation_method' => $component['calculation_method'] ?? 'flat',
                    'leave_paid_behavior' => $component['leave_paid_behavior'] ?? 'keep',
                    'leave_unpaid_behavior' => $component['leave_unpaid_behavior'] ?? 'deduct_daily',
                    'attendance_based' => ! empty($component['attendance_based']),
                    'taxable' => ! empty($component['taxable']),
                    'sort_order' => $component['sort_order'] ?? 0,
                    'notes' => $component['notes'] ?? null,
                ];
            })
            ->filter(fn (array $component) => $component['component_code'] !== '' && $component['component_name'] !== '')
            ->values()
            ->all();

        $policy->components()->delete();

        if ($rows !== []) {
            $policy->components()->createMany($rows);
        }
    }

    protected function componentCodeFromName(string $name): string
    {
        return Str::of($name)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '_')
            ->trim('_')
            ->limit(80, '')
            ->value();
    }

    protected function formData(PayrollPolicy $policy, Request $request): array
    {
        $tenantId = $policy->tenant_id ?: $this->resolveTenantId($request);

        return [
            'policy' => $policy,
            'tenants' => $this->tenantOptions($request),
            'positions' => Position::query()
                ->when($tenantId, fn ($query) => $query->where('tenant_id', $tenantId))
                ->orderBy('name')
                ->get(),
            'levels' => EmployeeLevel::query()
                ->when($tenantId, fn ($query) => $query->where('tenant_id', $tenantId))
                ->where('status', 'active')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
            'componentDefaults' => $this->componentDefaults($policy),
        ];
    }

    protected function componentDefaults(PayrollPolicy $policy): array
    {
        if ($policy->exists && $policy->components->isNotEmpty()) {
            return $policy->components->map(fn ($component) => $component->toArray())->all();
        }

        return [
            [
                'component_code' => 'meal_allowance',
                'component_name' => 'Uang Makan',
                'component_type' => 'earning',
                'amount' => 0,
                'calculation_method' => 'daily_attendance',
                'leave_paid_behavior' => 'deduct_daily',
                'leave_unpaid_behavior' => 'deduct_daily',
                'sort_order' => 10,
            ],
            [
                'component_code' => 'transport_allowance',
                'component_name' => 'Tunjangan Transport',
                'component_type' => 'earning',
                'amount' => 0,
                'calculation_method' => 'flat',
                'leave_paid_behavior' => 'keep',
                'leave_unpaid_behavior' => 'keep',
                'sort_order' => 20,
            ],
            [
                'component_code' => 'thr_allowance',
                'component_name' => 'THR',
                'component_type' => 'earning',
                'amount' => 0,
                'calculation_method' => 'flat',
                'leave_paid_behavior' => 'keep',
                'leave_unpaid_behavior' => 'keep',
                'sort_order' => 30,
            ],
        ];
    }

    protected function tenantOptions(Request $request)
    {
        $currentUser = $request->user() ?? auth()->user();

        return Tenant::query()
            ->when($currentUser?->isManager(), fn ($query) => $query->whereKey($currentUser->tenant_id))
            ->orderBy('name')
            ->get();
    }

    protected function resolveTenantId(Request $request): ?int
    {
        $currentUser = $request->user() ?? auth()->user();

        if ($currentUser?->isManager()) {
            return (int) $currentUser->tenant_id;
        }

        return $request->integer('tenant_id') ?: null;
    }

    protected function ensurePolicyAccessible(Request $request, PayrollPolicy $policy): void
    {
        $currentUser = $request->user() ?? auth()->user();

        if ($currentUser?->isManager() && $policy->tenant_id !== $currentUser->tenant_id) {
            abort(403);
        }
    }
}
