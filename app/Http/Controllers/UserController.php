<?php

namespace App\Http\Controllers;

use App\Exports\UsersExport;
use App\Models\Employee;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:users')->only(['index', 'export']);
        $this->middleware('permission:users.manage')->only(['create', 'show', 'edit', 'store', 'update', 'destroy', 'linkEmployee']);
    }

    public function index(Request $request)
    {
        $user = $request->user() ?? auth()->user();
        [$tenantId, $role, $linked] = $this->resolveIndexFilters($request, $user);

        $userScopeQuery = $this->buildUserScopeQuery($tenantId, $role);

        $linkedEmployeeCount = (clone $userScopeQuery)
            ->whereHas('employee')
            ->count();

        $unlinkedEmployeeCount = (clone $userScopeQuery)
            ->whereDoesntHave('employee')
            ->count();

        $users = $userScopeQuery
            ->with(['tenant', 'employee'])
            ->tap(fn ($query) => $this->applyLinkedFilter($query, $linked))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $selectedTenantName = $tenantId
            ? Tenant::whereKey($tenantId)->value('name')
            : null;

        return view('users.index', [
            'users' => $users,
            'tenants' => $user->isManager()
                ? Tenant::whereKey($user->tenant_id)->get()
                : Tenant::orderBy('name')->get(),
            'selectedTenantId' => $tenantId,
            'selectedTenantName' => $selectedTenantName,
            'selectedRole' => $role,
            'selectedLinked' => $linked,
            'linkedEmployeeCount' => $linkedEmployeeCount,
            'unlinkedEmployeeCount' => $unlinkedEmployeeCount,
            'roles' => $this->roles(),
        ]);
    }

    public function export(Request $request)
    {
        $currentUser = $request->user() ?? auth()->user();
        [$tenantId, $role, $linked] = $this->resolveIndexFilters($request, $currentUser);
        $format = $this->resolveExportFormat($request);
        $tenantName = $tenantId ? Tenant::whereKey($tenantId)->value('name') : null;

        return Excel::download(
            new UsersExport($this->getUsersForExport($tenantId, $role, $linked), [
                'tenant' => $tenantId,
                'tenant_name' => $tenantName,
                'role' => $role,
                'linked' => $linked,
            ]),
            $this->buildExportFilename('users-export', $format, [
                'tenant' => $tenantName,
                'role' => $role,
                'linked' => $linked,
            ]),
            $format === 'xlsx' ? ExcelFormat::XLSX : ExcelFormat::CSV
        );
    }

    public function create()
    {
        return view('users.create', $this->getFormData(new User()));
    }

    public function show(User $user)
    {
        $user->load(['tenant', 'employee.department', 'employee.position', 'employee.workLocation']);

        return view('users.show', [
            'user' => $user,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'tenant_id'             => ['required', 'exists:tenants,id'],
            'employee_id'           => ['required', 'exists:employees,id'],
            'name'                  => ['required', 'string', 'max:255'],
            'email'                 => ['required', 'email', 'max:255', 'unique:users,email'],
            'password'              => ['required', 'string', 'min:8', 'confirmed'],
            'role_id'               => ['nullable', 'exists:roles,id'],
            'role'                  => ['nullable', Rule::in(array_keys($this->roles()))],
            'status'                => ['required', Rule::in(array_keys($this->statuses()))],
            'avatar'                => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif', 'max:2048'],
        ]);

        $selectedRole = $this->resolveSelectedRole($data);
        $data['role_id'] = $selectedRole?->id;
        $data['role'] = $selectedRole?->system_key ?? ($data['role'] ?? 'employee');

        if ($request->hasFile('avatar')) {
            $data['avatar_path'] = $request->file('avatar')->store('avatars', 'public');
        }
        unset($data['avatar']);

        $employee = Employee::query()
            ->whereKey($data['employee_id'])
            ->where('tenant_id', $data['tenant_id'])
            ->whereNull('user_id')
            ->firstOrFail();

        DB::transaction(function () use (&$data, $employee): void {
            $data['tenant_id'] = $employee->tenant_id;
            $user = User::create($data);
            $employee->update(['user_id' => $user->id]);
        });

        return redirect()->route('users.index')->with('success', 'User berhasil dibuat.');
    }

    public function edit(User $user)
    {
        $this->ensureManagerCanAccessUser($user);

        return view('users.edit', $this->getFormData($user));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'tenant_id'             => ['required', 'exists:tenants,id'],
            'name'                  => ['required', 'string', 'max:255'],
            'email'                 => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password'              => ['nullable', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['nullable', 'string'],
            'role_id'               => ['nullable', 'exists:roles,id'],
            'role'                  => ['nullable', Rule::in(array_keys($this->roles()))],
            'status'                => ['required', Rule::in(array_keys($this->statuses()))],
            'avatar'                => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif', 'max:2048'],
            'remove_avatar'         => ['nullable', 'boolean'],
        ]);

        $selectedRole = $this->resolveSelectedRole($data, $user);
        $data['role_id'] = $selectedRole?->id;
        $data['role'] = $selectedRole?->system_key ?? ($data['role'] ?? $user->role);

        $removeAvatar = (bool) ($data['remove_avatar'] ?? false);

        if ($removeAvatar && $user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
            $data['avatar_path'] = null;
        }

        if ($request->hasFile('avatar')) {
            if ($user->avatar_path) {
                Storage::disk('public')->delete($user->avatar_path);
            }
            $data['avatar_path'] = $request->file('avatar')->store('avatars', 'public');
        }

        if (empty($data['password'])) {
            unset($data['password']);
        }

        unset($data['avatar'], $data['remove_avatar'], $data['password_confirmation']);

        $user->update($data);

        return redirect()->route('users.index')->with('success', 'User berhasil diperbarui.');
    }

    public function destroy(User $user)
    {
        try {
            $user->delete();

            return redirect()->route('users.index')->with('success', 'User berhasil dihapus.');
        } catch (\Throwable $e) {
            return redirect()->route('users.index')->with('error', 'User gagal dihapus, silakan coba lagi.');
        }
    }

    public function linkEmployee(User $user)
    {
        if ($user->employee) {
            return redirect()
                ->route('users.show-profile', $user)
                ->withErrors(['user' => 'This user is already linked to an employee record.']);
        }

        return redirect()->route('employees.create', [
            'user_id' => $user->id,
            'tenant_id' => $user->tenant_id,
        ]);
    }

    protected function roles()
    {
        return Role::query()
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (Role $role) => [$role->system_key => $role->name])
            ->all();
    }

    protected function resolveIndexFilters(Request $request, User $currentUser): array
    {
        $tenantId = $request->integer('tenant_id');
        $role = $request->string('role')->value();
        $linked = $request->string('linked')->value();

        if (! in_array($role, array_keys($this->roles()), true)) {
            $role = null;
        }

        if (! in_array($linked, ['only', 'unlinked'], true)) {
            $linked = null;
        }

        if ($currentUser->isManager()) {
            $tenantId = $currentUser->tenant_id;
        }

        return [$tenantId, $role, $linked];
    }

    protected function buildUserScopeQuery(?int $tenantId, ?string $role)
    {
        return User::query()
            ->with('managedRole')
            ->when($tenantId, function ($query) use ($tenantId) {
                $query->where('tenant_id', $tenantId);
            })
            ->when($role, function ($query) use ($role) {
                $query->whereRoleKey($role);
            });
    }

    protected function getUsersForExport(?int $tenantId, ?string $role, ?string $linked): Collection
    {
        return $this->buildUserScopeQuery($tenantId, $role)
            ->with(['tenant', 'employee'])
            ->tap(fn ($query) => $this->applyLinkedFilter($query, $linked))
            ->orderBy('id')
            ->get();
    }

    protected function applyLinkedFilter($query, ?string $linked): void
    {
        if ($linked === 'only') {
            $query->whereHas('employee');
        }

        if ($linked === 'unlinked') {
            $query->whereDoesntHave('employee');
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

    protected function statuses()
    {
        return [
            'active' => 'Active',
            'inactive' => 'Inactive',
        ];
    }

    protected function ensureManagerCanAccessUser(User $user): void
    {
        $currentUser = auth()->user();

        if ($currentUser?->isManager() && $currentUser->tenant_id !== $user->tenant_id) {
            abort(403);
        }
    }

    protected function getFormData(User $user): array
    {
        $currentUser = auth()->user();
        $isManager = (bool) $currentUser?->isManager();
        $tenantId = $isManager
            ? $currentUser?->tenant_id
            : ($user->tenant_id ?? $currentUser?->tenant_id);

        $linkableEmployees = Employee::query()
            ->whereNull('user_id')
            ->when($tenantId, fn ($query) => $query->where('tenant_id', $tenantId))
            ->with(['department', 'position'])
            ->orderBy('employee_code')
            ->get();

        return [
            'user' => $user,
            'currentUser' => $currentUser,
            'isTenantLocked' => $isManager,
            'scopedTenantId' => $tenantId,
            'tenants' => $isManager && $tenantId
                ? Tenant::whereKey($tenantId)->get()
                : Tenant::orderBy('name')->get(),
            'linkableEmployees' => $linkableEmployees,
            'assignableRoles' => Role::query()->orderBy('name')->get(),
            'roles' => $this->roles(),
            'statuses' => $this->statuses(),
        ];
    }

    protected function resolveSelectedRole(array $data, ?User $user = null): ?Role
    {
        if (! empty($data['role_id'])) {
            return Role::find($data['role_id']);
        }

        if (! empty($data['role'])) {
            return Role::query()
                ->get()
                ->first(fn (Role $role) => $role->system_key === $data['role']);
        }

        return $user?->resolvedRole();
    }
}