<?php

namespace App\Http\Controllers;

use App\Models\EmployeeLevel;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class EmployeeLevelController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:employee_levels');
    }

    public function index(Request $request)
    {
        $currentUser = $request->user();
        $statuses = $this->statuses();
        $search = trim((string) $request->query('search', ''));
        $selectedTenantId = $currentUser?->isManager() ? $currentUser->tenant_id : ($request->integer('tenant_id') ?: null);
        $selectedStatus = $request->filled('status') && array_key_exists((string) $request->query('status'), $statuses)
            ? (string) $request->query('status')
            : null;

        $baseQuery = $this->buildScopeQuery($search, $selectedTenantId, $selectedStatus)
            ->with('tenant')
            ->withCount('employees');

        $employeeLevels = (clone $baseQuery)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        $summaryQuery = $this->buildScopeQuery($search, $selectedTenantId, $selectedStatus);
        $summary = [
            'total' => (clone $summaryQuery)->count(),
            'active' => (clone $summaryQuery)->where('status', 'active')->count(),
            'inactive' => (clone $summaryQuery)->where('status', 'inactive')->count(),
        ];

        $tenants = $currentUser?->isManager()
            ? Tenant::whereKey($currentUser->tenant_id)->get()
            : Tenant::orderBy('name')->get();

        return view('employee_levels.index', [
            'employeeLevels' => $employeeLevels,
            'employeeLevel' => new EmployeeLevel(['status' => 'active']),
            'tenants' => $tenants,
            'statuses' => $statuses,
            'summary' => $summary,
            'search' => $search,
            'selectedTenantId' => $selectedTenantId,
            'selectedTenantName' => $selectedTenantId ? optional($tenants->firstWhere('id', $selectedTenantId))->name : null,
            'selectedStatus' => $selectedStatus,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);

        EmployeeLevel::create($data);

        return redirect()->route('employee-levels.index')->with('success', 'Level karyawan berhasil ditambahkan.');
    }

    public function create()
    {
        return view('employee_levels.create', [
            'employeeLevel' => new EmployeeLevel(['status' => 'active', 'sort_order' => 0]),
            'tenants' => $this->tenantOptions(),
            'statuses' => $this->statuses(),
        ]);
    }

    public function edit(EmployeeLevel $employeeLevel)
    {
        $this->ensureManagerCanAccess($employeeLevel);

        return view('employee_levels.edit', [
            'employeeLevel' => $employeeLevel,
            'tenants' => $this->tenantOptions(),
            'statuses' => $this->statuses(),
        ]);
    }

    public function update(Request $request, EmployeeLevel $employeeLevel)
    {
        $this->ensureManagerCanAccess($employeeLevel);

        $employeeLevel->update($this->validatedData($request, $employeeLevel));

        return redirect()->route('employee-levels.index')->with('success', 'Level karyawan berhasil diperbarui.');
    }

    public function destroy(EmployeeLevel $employeeLevel)
    {
        $this->ensureManagerCanAccess($employeeLevel);

        if ($employeeLevel->employees()->exists()) {
            return redirect()
                ->route('employee-levels.index')
                ->withErrors(['employee_level' => 'Level karyawan masih digunakan oleh data karyawan.']);
        }

        $employeeLevel->delete();

        return redirect()->route('employee-levels.index')->with('success', 'Level karyawan berhasil dihapus.');
    }

    protected function validatedData(Request $request, ?EmployeeLevel $employeeLevel = null): array
    {
        $tenantId = $this->resolvedTenantId($request, $employeeLevel);
        $code = Str::slug((string) ($request->input('code') ?: $request->input('name')), '_');

        $request->merge([
            'tenant_id' => $tenantId,
            'code' => $code,
            'status' => $request->input('status', 'active'),
            'sort_order' => $request->integer('sort_order'),
        ]);

        return $request->validate([
            'tenant_id' => ['required', 'exists:tenants,id'],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('employee_levels', 'code')
                    ->where(fn ($query) => $query->where('tenant_id', $tenantId))
                    ->ignore($employeeLevel?->id),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('employee_levels', 'name')
                    ->where(fn ($query) => $query->where('tenant_id', $tenantId))
                    ->ignore($employeeLevel?->id),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', Rule::in(array_keys($this->statuses()))],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);
    }

    protected function buildScopeQuery(string $search, ?int $tenantId, ?string $status)
    {
        $currentUser = auth()->user();

        return EmployeeLevel::query()
            ->when($currentUser?->isManager(), fn ($query) => $query->where('tenant_id', $currentUser->tenant_id))
            ->when($tenantId, fn ($query) => $query->where('tenant_id', $tenantId))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nested) use ($search) {
                    $nested
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            });
    }

    protected function resolvedTenantId(Request $request, ?EmployeeLevel $employeeLevel = null): int
    {
        $currentUser = $request->user();

        if ($currentUser?->isManager()) {
            return (int) $currentUser->tenant_id;
        }

        return (int) ($request->integer('tenant_id') ?: $employeeLevel?->tenant_id ?: $currentUser?->tenant_id);
    }

    protected function ensureManagerCanAccess(EmployeeLevel $employeeLevel): void
    {
        $currentUser = auth()->user();

        if ($currentUser?->isManager() && $currentUser->tenant_id !== $employeeLevel->tenant_id) {
            abort(403);
        }
    }

    protected function tenantOptions()
    {
        $currentUser = auth()->user();

        return $currentUser?->isManager()
            ? Tenant::whereKey($currentUser->tenant_id)->get()
            : Tenant::orderBy('name')->get();
    }

    protected function statuses(): array
    {
        return [
            'active' => 'Aktif',
            'inactive' => 'Tidak Aktif',
        ];
    }
}
