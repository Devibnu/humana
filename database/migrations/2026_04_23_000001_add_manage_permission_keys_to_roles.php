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
            ->mapWithKeys(fn ($r) => [$this->roleSystemKey($r->name) => $r->id])
            ->all();

        $adminHrId  = $roles['admin_hr']  ?? null;
        $managerId  = $roles['manager']   ?? null;

        $adminKeys   = ['attendances.manage', 'leaves.manage', 'users.manage'];
        $managerKeys = ['attendances.manage', 'leaves.manage'];

        foreach ($adminKeys as $key) {
            if ($adminHrId) {
                DB::table('role_permissions')->updateOrInsert(
                    ['role_id' => $adminHrId, 'menu_key' => $key],
                    ['can_access' => true, 'updated_at' => now(), 'created_at' => now()]
                );
            }
        }

        foreach ($managerKeys as $key) {
            if ($managerId) {
                DB::table('role_permissions')->updateOrInsert(
                    ['role_id' => $managerId, 'menu_key' => $key],
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
            ->whereIn('menu_key', ['attendances.manage', 'leaves.manage', 'users.manage'])
            ->delete();
    }
};
