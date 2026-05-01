<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected bool $menuPermissionMapResolved = false;

    protected ?array $cachedMenuPermissionMap = null;

    protected static function booted(): void
    {
        static::saving(function (self $user): void {
            $user->syncRoleAttributes();
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'tenant_id',
        'employee_id',
        'name',
        'email',
        'password',
        'avatar_path',
        'role_id',
        'role',
        'status',
        'phone',
        'location',
        'about_me',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'role_id' => 'integer',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function assignedEmployee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function employee()
    {
        return $this->hasOne(Employee::class);
    }

    public function managedRole()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function resolvedRole(): ?Role
    {
        if ($this->relationLoaded('managedRole') && $this->managedRole) {
            return $this->managedRole;
        }

        if ($this->role_id) {
            return $this->managedRole()->first();
        }

        if (! $this->role) {
            return null;
        }

        return Role::query()
            ->get()
            ->first(fn (Role $role) => $role->system_key === $this->role);
    }

    public function roleKey(): ?string
    {
        return $this->resolvedRole()?->system_key
            ?: ($this->role ? Role::toSystemKey($this->role) : null);
    }

    public function roleName(): ?string
    {
        return $this->resolvedRole()?->name;
    }

    public function isAdminHr()
    {
        return $this->roleKey() === 'admin_hr';
    }

    public function isManager()
    {
        return $this->roleKey() === 'manager';
    }

    public function isSupervisor()
    {
        return $this->roleKey() === 'supervisor';
    }

    public function isOwner()
    {
        return $this->roleKey() === 'owner';
    }

    public function isEmployee()
    {
        return $this->roleKey() === 'employee';
    }

    public function scopeWhereRoleKey($query, string $roleKey)
    {
        $roleId = Role::idForSystemKey($roleKey);

        return $query->when(
            $roleId,
            fn ($builder) => $builder->where('role_id', $roleId),
            fn ($builder) => $builder->where('role', $roleKey)
        );
    }

    public function scopeWhereRoleKeys($query, array $roleKeys)
    {
        $roleIds = array_values(array_filter(array_map(fn (string $roleKey) => Role::idForSystemKey($roleKey), $roleKeys)));

        if ($roleIds !== []) {
            return $query->whereIn('role_id', $roleIds);
        }

        return $query->whereIn('role', $roleKeys);
    }

    public function hasMenuAccess(string $menuKey): bool
    {
        if (! array_key_exists($menuKey, config('menu_permissions.definitions', []))) {
            return false;
        }

        return (bool) ($this->menuPermissionMap()[$menuKey] ?? false);
    }

    public function accessibleMenuKeys(): array
    {
        return array_values(array_filter(array_keys(config('menu_permissions.definitions', [])), fn (string $menuKey) => $this->hasMenuAccess($menuKey)));
    }

    protected function menuPermissionMap(): array
    {
        if ($this->menuPermissionMapResolved) {
            return $this->cachedMenuPermissionMap ?? [];
        }

        if (! $this->role_id) {
            $this->menuPermissionMapResolved = true;
            $this->cachedMenuPermissionMap = [];

            return $this->cachedMenuPermissionMap;
        }

        $role = $this->relationLoaded('managedRole') && $this->managedRole?->id === $this->role_id
            ? $this->managedRole
            : $this->managedRole()->first();

        if (! $role) {
            $this->menuPermissionMapResolved = true;
            $this->cachedMenuPermissionMap = [];

            return $this->cachedMenuPermissionMap;
        }

        $permissions = $role->relationLoaded('permissions')
            ? $role->permissions
            : $role->permissions()->get();

        if ($permissions->isEmpty()) {
            $this->menuPermissionMapResolved = true;
            $this->cachedMenuPermissionMap = [];

            return $this->cachedMenuPermissionMap;
        }

        $this->menuPermissionMapResolved = true;
        $this->cachedMenuPermissionMap = $permissions
            ->filter(fn (RolePermission $permission) => $permission->can_access)
            ->mapWithKeys(fn (RolePermission $permission) => [$permission->menu_key => true])
            ->all();

        return $this->cachedMenuPermissionMap;
    }

    protected function syncRoleAttributes(): void
    {
        if ($this->role_id) {
            $role = $this->relationLoaded('managedRole') && $this->managedRole?->id === $this->role_id
                ? $this->managedRole
                : Role::find($this->role_id);

            if ($role) {
                $this->setRelation('managedRole', $role);
                $this->role = $role->system_key;

                return;
            }
        }

        if ($this->role) {
            $role = Role::query()->get()->first(fn (Role $role) => $role->system_key === Role::toSystemKey($this->role));

            if ($role) {
                $this->role_id = $role->id;
                $this->role = $role->system_key;
                $this->setRelation('managedRole', $role);
            }
        }
    }
}
