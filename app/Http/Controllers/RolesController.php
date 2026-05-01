<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class RolesController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:roles');
    }

    public function index(Request $request)
    {
        $this->ensureDefaultRolesExist();

        $search = trim((string) $request->query('search', ''));
        $selectedUsage = $request->filled('usage') ? (string) $request->query('usage') : null;
        $usageOptions = [
            'assigned' => 'Sedang Dipakai',
            'unused' => 'Belum Dipakai',
        ];

        $baseQuery = Role::query()
            ->withCount(['permissions', 'users'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nestedQuery) use ($search) {
                    $nestedQuery
                        ->where('name', 'like', '%'.$search.'%')
                        ->orWhere('description', 'like', '%'.$search.'%');
                });
            })
            ->when($selectedUsage === 'assigned', fn ($query) => $query->has('users'))
            ->when($selectedUsage === 'unused', fn ($query) => $query->doesntHave('users'));

        $roles = (clone $baseQuery)
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        $summary = [
            'total' => (clone $baseQuery)->count(),
            'assigned' => (clone $baseQuery)->has('users')->count(),
            'permissions' => (clone $baseQuery)->get()->sum('permissions_count'),
        ];

        return view('roles.index', [
            'roles' => $roles,
            'search' => $search,
            'selectedUsage' => $selectedUsage,
            'usageOptions' => $usageOptions,
            'summary' => $summary,
        ]);
    }

    public function create()
    {
        $this->ensureDefaultRolesExist();

        return view('roles.create', [
            'role' => new Role(),
            'selectedPermissions' => [],
            'menuGroups' => $this->menuGroups(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateRequest($request);

        DB::transaction(function () use ($data) {
            $role = Role::create(Arr::only($data, ['name', 'description']));
            $this->syncPermissions($role, $data['permissions'] ?? []);
        });

        return redirect()->route('roles.index')->with('success', 'Role berhasil dibuat.');
    }

    public function edit(Role $role)
    {
        $role->load('permissions');

        return view('roles.edit', [
            'role' => $role,
            'selectedPermissions' => $role->permissions
                ->where('can_access', true)
                ->pluck('menu_key')
                ->all(),
            'menuGroups' => $this->menuGroups(),
        ]);
    }

    public function update(Request $request, Role $role)
    {
        $data = $this->validateRequest($request, $role);
        $oldSystemKey = $role->system_key;

        DB::transaction(function () use ($data, $oldSystemKey, $role) {
            $role->update(Arr::only($data, ['name', 'description']));
            $this->syncPermissions($role, $data['permissions'] ?? []);

            if ($oldSystemKey !== $role->system_key) {
                User::query()
                    ->where('role_id', $role->id)
                    ->update([
                        'role' => $role->system_key,
                        'role_id' => $role->id,
                    ]);
            }
        });

        return redirect()->route('roles.edit', $role)->with('success', 'Role berhasil diperbarui.');
    }

    public function destroy(Role $role)
    {
        if (User::query()->where('role_id', $role->id)->exists()) {
            return redirect()->route('roles.index')->with('error', 'Role tidak bisa dihapus karena masih digunakan oleh user.');
        }

        $role->delete();

        return redirect()->route('roles.index')->with('success', 'Role berhasil dihapus.');
    }

    protected function validateRequest(Request $request, ?Role $role = null): array
    {
        $menuKeys = array_keys($this->menuDefinitions());

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'name')->ignore($role?->id),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in($menuKeys)],
        ]);

        $data['name'] = Str::of($data['name'])->trim()->squish()->value();

        return $data;
    }

    protected function syncPermissions(Role $role, array $permissions): void
    {
        $selectedPermissions = array_values(array_unique($permissions));

        $role->permissions()->delete();

        if ($selectedPermissions === []) {
            return;
        }

        $role->permissions()->createMany(array_map(fn (string $menuKey) => [
            'menu_key' => $menuKey,
            'can_access' => true,
        ], $selectedPermissions));
    }

    protected function menuDefinitions(): array
    {
        return config('menu_permissions.definitions', []);
    }

    protected function menuGroups(): array
    {
        $groups = [
            'master' => 'Data Master',
            'operational' => 'Modul Operasional',
        ];

        $definitions = collect($this->menuDefinitions());

        return collect($groups)->mapWithKeys(function (string $label, string $group) use ($definitions) {
            return [$group => [
                'label' => $label,
                'menus' => $definitions
                    ->filter(fn (array $menu) => ($menu['group'] ?? null) === $group)
                    ->all(),
            ]];
        })->all();
    }

    protected function ensureDefaultRolesExist(): void
    {
        foreach ($this->defaultRoles() as $name => $attributes) {
            $role = Role::firstOrCreate(
                ['name' => $name],
                ['description' => $attributes['description']]
            );

            if ($role && ! $role->permissions()->exists()) {
                $this->syncPermissions($role, $attributes['permissions']);
            }
        }
    }

    protected function defaultRoles(): array
    {
        return [
            'Admin HR' => [
                'description' => 'Hak penuh akses',
                'permissions' => config('menu_permissions.defaults.admin_hr', []),
            ],
            'Manager' => [
                'description' => 'Kelola tim',
                'permissions' => config('menu_permissions.defaults.manager', []),
            ],
            'Employee' => [
                'description' => 'Akses terbatas',
                'permissions' => config('menu_permissions.defaults.employee', []),
            ],
        ];
    }
}