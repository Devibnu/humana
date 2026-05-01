<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    private function roleSystemKey(string $name): string
    {
        return Str::of($name)->lower()->replaceMatches('/[^a-z0-9]+/', '_')->trim('_')->value();
    }

    public function up(): void
    {
        if (! Schema::hasTable('roles') || ! Schema::hasTable('role_permissions')) {
            return;
        }

        $roles = DB::table('roles')->get(['id', 'name'])
            ->mapWithKeys(fn ($role) => [$this->roleSystemKey($role->name) => $role->id])
            ->all();

        $permissionsByRole = [
            'admin_hr' => ['employees.destroy', 'attendances.destroy', 'leaves.create', 'leaves.destroy', 'leaves.analytics', 'payroll.manage'],
            'manager' => ['leaves.analytics'],
            'employee' => ['leaves.create'],
        ];

        foreach ($permissionsByRole as $roleKey => $menuKeys) {
            $roleId = $roles[$roleKey] ?? null;

            if (! $roleId) {
                continue;
            }

            foreach ($menuKeys as $menuKey) {
                DB::table('role_permissions')->updateOrInsert(
                    ['role_id' => $roleId, 'menu_key' => $menuKey],
                    ['can_access' => true, 'updated_at' => now(), 'created_at' => now()]
                );
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('role_permissions')) {
            return;
        }

        DB::table('role_permissions')
            ->whereIn('menu_key', ['employees.destroy', 'attendances.destroy', 'leaves.create', 'leaves.destroy', 'leaves.analytics', 'payroll.manage'])
            ->delete();
    }
};