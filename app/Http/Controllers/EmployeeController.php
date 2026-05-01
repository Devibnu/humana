<?php

namespace App\Http\Controllers;

use App\Exports\EmployeesExport;
use App\Http\Requests\EmployeeRequest;
use App\Models\Attendance;
use App\Models\Department;
use App\Models\Employee;
use App\Models\FamilyMember;
use App\Models\Leave;
use App\Models\Position;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WorkLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;

class EmployeeController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:employees')->except('destroy');
        $this->middleware('permission:employees.destroy')->only('destroy');
    }

    public function index(Request $request)
    {
        $currentUser = $request->user() ?? auth()->user();
        [$tenantId, $linked] = $this->resolveIndexFilters($request, $currentUser);

        $employeeScopeQuery = $this->buildEmployeeScopeQuery($tenantId);

        $linkedCount = (clone $employeeScopeQuery)
            ->whereNotNull('user_id')
            ->count();

        $unlinkedCount = (clone $employeeScopeQuery)
            ->whereNull('user_id')
            ->count();

        $employees = $employeeScopeQuery
            ->with(['tenant', 'position', 'department', 'user'])
            ->tap(fn ($query) => $this->applyLinkedFilter($query, $linked))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $selectedTenantName = $tenantId
            ? Tenant::whereKey($tenantId)->value('name')
            : null;

        return view('employees.index', [
            'employees' => $employees,
            'tenants' => $currentUser->isManager()
                ? Tenant::whereKey($currentUser->tenant_id)->get()
                : Tenant::orderBy('name')->get(),
            'selectedTenantId' => $tenantId,
            'selectedTenantName' => $selectedTenantName,
            'selectedLinked' => $linked,
            'linkedCount' => $linkedCount,
            'unlinkedCount' => $unlinkedCount,
        ]);
    }

    public function export(Request $request)
    {
        $currentUser = $request->user() ?? auth()->user();
        [$tenantId, $linked] = $this->resolveIndexFilters($request, $currentUser);
        $format = $this->resolveExportFormat($request);
        $tenantName = $tenantId ? Tenant::whereKey($tenantId)->value('name') : null;

        return Excel::download(
            new EmployeesExport($this->getEmployeesForExport($tenantId, $linked), [
                'tenant' => $tenantId,
                'tenant_name' => $tenantName,
                'linked' => $linked,
            ]),
            $this->buildExportFilename('employees-export', $format, [
                'tenant' => $tenantName,
                'linked' => $linked,
            ]),
            $format === 'xlsx' ? ExcelFormat::XLSX : ExcelFormat::CSV
        );
    }

    public function create(Request $request)
    {
        $currentUser = $request->user() ?? auth()->user();
        $linkedUserId = $request->integer('user_id');

        $employee = new Employee();

        if ($linkedUserId) {
            $employeeUser = User::query()
                ->whereKey($linkedUserId)
                ->whereRoleKey('employee')
                ->when($currentUser?->isManager(), fn ($query) => $query->where('tenant_id', $currentUser->tenant_id))
                ->first();

            if ($employeeUser) {
                $employee->fill([
                    'user_id' => $employeeUser->id,
                    'tenant_id' => $employeeUser->tenant_id,
                    'name' => $employeeUser->name,
                    'email' => $employeeUser->email,
                    'status' => 'active',
                ]);
            }
        }

        return view('employees.create', $this->getFormData($employee));
    }

    public function store(EmployeeRequest $request)
    {
        $tenantId = $request->resolvedTenantId();

        $data = $request->validated();
        $familyMembers = collect($data['family_members'] ?? [])
            ->filter(fn (array $familyMember) => collect($familyMember)
                ->only(['name', 'relationship', 'dob', 'education', 'job', 'marital_status'])
                ->contains(fn ($value) => $value !== null && $value !== ''))
            ->map(fn (array $familyMember) => [
                'name' => $familyMember['name'],
                'relationship' => $familyMember['relationship'],
                'dob' => $familyMember['dob'],
                'education' => $familyMember['education'] ?: null,
                'job' => $familyMember['job'] ?: null,
                'marital_status' => $familyMember['marital_status'],
            ])
            ->values();
        $bankAccounts = collect($data['bank_accounts'] ?? [])
            ->filter(fn (array $bankAccount) => collect($bankAccount)
                ->only(['bank_name', 'account_number', 'account_holder'])
                ->contains(fn ($value) => $value !== null && $value !== ''))
            ->map(fn (array $bankAccount) => [
                'bank_name' => $bankAccount['bank_name'],
                'account_number' => $bankAccount['account_number'],
                'account_holder' => $bankAccount['account_holder'],
            ])
            ->values();

        $data['tenant_id'] = $tenantId;

        if ($request->hasFile('avatar')) {
            $data['avatar_path'] = $request->file('avatar')->store('employee-avatars', 'public');
        }

        unset($data['family_members']);
        unset($data['bank_accounts']);
        unset($data['avatar']);

        DB::transaction(function () use ($data, $familyMembers, $bankAccounts): void {
            $employee = Employee::create($data);

            if ($familyMembers->isNotEmpty()) {
                $employee->familyMembers()->createMany($familyMembers->all());
            }

            if ($bankAccounts->isNotEmpty()) {
                $employee->bankAccounts()->createMany($bankAccounts->all());
            }
        });

        return redirect()->route('employees.index')->with('success', 'Employee berhasil dibuat.');
    }

    public function show(Employee $employee)
    {
        $this->ensureManagerCanAccessEmployee($employee);

        $employee->load(['tenant', 'position', 'department', 'workLocation', 'user', 'familyMembers', 'bankAccounts']);
        $attendances = Attendance::query()
            ->with(['attendanceLog.workLocation'])
            ->where('employee_id', $employee->id)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();
        $leaves = Leave::query()
            ->with('leaveType')
            ->where('employee_id', $employee->id)
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->get();

        return view('employees.show', [
            'employee'            => $employee,
            'attendances'         => $attendances,
            'leaves'              => $leaves,
            'familyRelationships' => \App\Models\FamilyMember::relationships(),
            'familyModalRelationships' => \App\Models\FamilyMember::modalRelationships(),
            'familyMaritalStatuses' => \App\Models\FamilyMember::maritalStatuses(),
            'educationOptions'    => ['SD', 'SMP', 'SMA', 'SMK', 'D3', 'S1', 'S2', 'S3'],
            'maritalStatusOptions' => ['belum_menikah' => 'Belum Menikah', 'menikah' => 'Menikah', 'cerai_hidup' => 'Cerai Hidup', 'cerai_mati' => 'Cerai Mati'],
            'employmentTypeOptions' => ['tetap' => 'Tetap', 'kontrak' => 'Kontrak'],
        ]);
    }

    public function edit(Employee $employee)
    {
        $this->ensureManagerCanAccessEmployee($employee);

        return view('employees.edit', $this->getFormData($employee));
    }

    public function update(EmployeeRequest $request, Employee $employee)
    {
        $this->ensureManagerCanAccessEmployee($employee);

        $tenantId = $request->resolvedTenantId();

        $data = $request->validated();

        $data['tenant_id'] = $tenantId;

        if ($request->hasFile('avatar')) {
            if ($employee->avatar_path) {
                Storage::disk('public')->delete($employee->avatar_path);
            }

            $data['avatar_path'] = $request->file('avatar')->store('employee-avatars', 'public');
        }

        unset($data['avatar']);

        $employee->update($data);

        return redirect()->route('employees.index')->with('success', 'Data karyawan berhasil diperbarui.');
    }

    public function destroy(Employee $employee)
    {
        $employee->delete();

        return redirect()->route('employees.index')->with('success', 'Employee deleted successfully.');
    }

    protected function statuses()
    {
        return [
            'active' => 'Aktif',
            'inactive' => 'Tidak Aktif',
        ];
    }

    protected function roles(): array
    {
        return [
            'staff' => 'Staff',
            'supervisor' => 'Supervisor',
            'manager' => 'Manager',
        ];
    }

    protected function resolveIndexFilters(Request $request, User $currentUser): array
    {
        $tenantId = $request->integer('tenant_id');
        $linked = $request->string('linked')->value();

        if (! in_array($linked, ['only', 'unlinked'], true)) {
            $linked = null;
        }

        if ($currentUser->isManager()) {
            $tenantId = $currentUser->tenant_id;
        }

        return [$tenantId, $linked];
    }

    protected function buildEmployeeScopeQuery(?int $tenantId)
    {
        return Employee::query()
            ->when($tenantId, function ($query) use ($tenantId) {
                $query->where('tenant_id', $tenantId);
            });
    }

    protected function getEmployeesForExport(?int $tenantId, ?string $linked): Collection
    {
        return $this->buildEmployeeScopeQuery($tenantId)
            ->with(['tenant', 'position', 'department', 'user'])
            ->tap(fn ($query) => $this->applyLinkedFilter($query, $linked))
            ->orderBy('employee_code')
            ->get();
    }

    protected function applyLinkedFilter($query, ?string $linked): void
    {
        if ($linked === 'only') {
            $query->whereNotNull('user_id');
        }

        if ($linked === 'unlinked') {
            $query->whereNull('user_id');
        }
    }

    protected function resolveExportFormat(Request $request): string
    {
        $format = strtolower($request->string('format')->value() ?: 'csv');

        return in_array($format, ['csv', 'xlsx'], true) ? $format : 'csv';
    }

    protected function buildExportFilename(string $base, string $format, array $filters): string
    {
        $parts = [$base, now()->format('Ymd')];

        foreach ($filters as $key => $value) {
            if ($value !== null && $value !== '') {
                $parts[] = $key.'-'.Str::slug((string) $value, '-');
            }
        }

        if (count($parts) === 2) {
            $parts[] = 'all';
        }

        return implode('_', $parts).'.'.$format;
    }

    protected function ensureManagerCanAccessEmployee(Employee $employee): void
    {
        $currentUser = auth()->user();

        if ($currentUser?->isManager() && $currentUser->tenant_id !== $employee->tenant_id) {
            abort(403);
        }
    }

    protected function getFormData(Employee $employee): array
    {
        $currentUser = auth()->user();
        $isManager = (bool) $currentUser?->isManager();
        $selectedTenantId = old('tenant_id');
        $tenantId = $isManager
            ? $currentUser?->tenant_id
            : ($selectedTenantId ?: $employee->tenant_id ?: $currentUser?->tenant_id);

        return [
            'employee' => $employee,
            'currentUser' => $currentUser,
            'isTenantLocked' => $isManager,
            'scopedTenantId' => $tenantId,
            'tenants' => $isManager && $tenantId
                ? Tenant::whereKey($tenantId)->get()
                : Tenant::orderBy('name')->get(),
            'positions' => Position::with('tenant')
                ->when($tenantId, fn ($query) => $query->where('tenant_id', $tenantId))
                ->orderBy('name')
                ->get(),
            'departments' => Department::with('tenant')
                ->when($tenantId, fn ($query) => $query->where('tenant_id', $tenantId))
                ->orderBy('name')
                ->get(),
            'employeeUsers' => User::query()
                ->whereRoleKey('employee')
                ->when($tenantId, fn ($query) => $query->where('tenant_id', $tenantId))
                ->orderBy('name')
                ->get(),
            'workLocations' => WorkLocation::query()
                ->when($tenantId, fn ($query) => $query->where('tenant_id', $tenantId))
                ->orderBy('name')
                ->get(),
            'roles' => $this->roles(),
            'statuses' => $this->statuses(),
        ];
    }
}