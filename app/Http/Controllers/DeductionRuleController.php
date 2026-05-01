<?php

namespace App\Http\Controllers;

use App\Models\DeductionRule;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class DeductionRuleController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:payroll');
    }

    public function index(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        [$search, $selectedSalaryType, $selectedRateType] = $this->resolveIndexFilters($request);

        $rulesQuery = DeductionRule::query()
            ->where('tenant_id', $tenantId)
            ->when($selectedSalaryType, fn ($query) => $query->where('salary_type', $selectedSalaryType))
            ->when($selectedRateType, fn ($query) => $query->where('rate_type', $selectedRateType))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery
                        ->where('salary_type', 'like', "%{$search}%")
                        ->orWhere('rate_type', 'like', "%{$search}%")
                        ->orWhere('working_hours_per_day', 'like', "%{$search}%")
                        ->orWhere('working_days_per_month', 'like', "%{$search}%")
                        ->orWhere('tolerance_minutes', 'like', "%{$search}%");
                });
            })
            ->orderBy('salary_type')
            ->orderBy('working_hours_per_day');

        $rules = (clone $rulesQuery)->paginate(10)->withQueryString();

        $summaryQuery = clone $rulesQuery;
        $summary = [
            'total' => (clone $summaryQuery)->count(),
            'daily' => (clone $summaryQuery)->where('salary_type', 'daily')->count(),
            'monthly' => (clone $summaryQuery)->where('salary_type', 'monthly')->count(),
            'alpha_full_day' => (clone $summaryQuery)->where('alpha_full_day', true)->count(),
        ];

        return view('deduction_rules.index', [
            'rule' => new DeductionRule(),
            'rules' => $rules,
            'summary' => $summary,
            'salaryTypes' => $this->salaryTypes(),
            'rateTypes' => $this->rateTypes(),
            'search' => $search,
            'selectedSalaryType' => $selectedSalaryType,
            'selectedRateType' => $selectedRateType,
        ]);
    }

    public function create()
    {
        return view('deduction_rules.create', [
            'rule' => new DeductionRule(),
            'salaryTypes' => $this->salaryTypes(),
            'rateTypes' => $this->rateTypes(),
        ]);
    }

    public function store(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        // debug log: record incoming payload
        Log::info('DeductionRuleController@store payload', [
            'user_id' => auth()->id(),
            'payload' => $request->all(),
        ]);

        try {
            $validated = $request->validate([
            'salary_type' => ['required', 'in:daily,monthly'],
            'working_hours_per_day' => ['required','integer','min:1'],
            'working_days_per_month' => ['required','integer','min:1'],
            'tolerance_minutes' => ['required','integer','min:0'],
            'rate_type' => ['required','in:flat,proportional'],
            'alpha_full_day' => ['sometimes','boolean'],
        ]);
        } catch (\Throwable $e) {
            Log::error('DeductionRuleController@store validation failed', ['error' => $e->getMessage()]);
            throw $e;
        }

        DeductionRule::create(array_merge($validated, [
            'alpha_full_day' => $request->has('alpha_full_day') ? (bool) $request->input('alpha_full_day') : true,
            'tenant_id' => $tenantId,
        ]));

        return redirect()->route('deduction_rules.index')->with('success', 'Master Potongan berhasil disimpan');
    }

    public function edit(DeductionRule $rule)
    {
        $this->authorizeRuleAccess($rule);
        return view('deduction_rules.edit', [
            'rule' => $rule,
            'salaryTypes' => $this->salaryTypes(),
            'rateTypes' => $this->rateTypes(),
        ]);
    }

    public function update(Request $request, DeductionRule $rule)
    {
        $this->authorizeRuleAccess($rule);

        $data = $request->validate([
            'salary_type' => ['required', 'in:daily,monthly'],
            'working_hours_per_day' => ['required','integer','min:1'],
            'working_days_per_month' => ['required','integer','min:1'],
            'tolerance_minutes' => ['required','integer','min:0'],
            'rate_type' => ['required','in:flat,proportional'],
            'alpha_full_day' => ['sometimes','boolean'],
        ]);

        $rule->update(array_merge($data, [
            'alpha_full_day' => $request->has('alpha_full_day') ? (bool) $request->input('alpha_full_day') : true,
        ]));

        return redirect()->route('deduction_rules.index')->with('success', 'Master Potongan berhasil diupdate');
    }

    public function destroy(DeductionRule $rule)
    {
        $this->authorizeRuleAccess($rule);
        $rule->delete();
        return redirect()->route('deduction_rules.index')->with('success', 'Master Potongan berhasil dihapus');
    }

    protected function authorizeRuleAccess(DeductionRule $rule): void
    {
        $tenantId = auth()->user()->tenant_id;
        if ($rule->tenant_id !== $tenantId) {
            abort(403);
        }
    }

    protected function resolveIndexFilters(Request $request): array
    {
        $search = trim((string) $request->string('search'));
        $selectedSalaryType = $request->string('salary_type')->value() ?: null;
        $selectedRateType = $request->string('rate_type')->value() ?: null;

        if ($selectedSalaryType !== null && ! $this->salaryTypes()->has($selectedSalaryType)) {
            $selectedSalaryType = null;
        }

        if ($selectedRateType !== null && ! $this->rateTypes()->has($selectedRateType)) {
            $selectedRateType = null;
        }

        return [$search, $selectedSalaryType, $selectedRateType];
    }

    protected function salaryTypes(): Collection
    {
        return collect([
            'daily' => 'Harian',
            'monthly' => 'Bulanan',
        ]);
    }

    protected function rateTypes(): Collection
    {
        return collect([
            'proportional' => 'Proporsional',
            'flat' => 'Flat',
        ]);
    }
}
