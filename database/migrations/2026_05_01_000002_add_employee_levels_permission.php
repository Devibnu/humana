<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('roles') || ! Schema::hasTable('role_permissions')) {
            return;
        }

        $roleIds = DB::table('roles')
            ->get(['id', 'name'])
            ->filter(fn ($role) => in_array(Str::of($role->name)->lower()->replaceMatches('/[^a-z0-9]+/', '_')->trim('_')->value(), ['admin_hr', 'manager'], true))
            ->pluck('id');

        foreach ($roleIds as $roleId) {
            DB::table('role_permissions')->updateOrInsert(
                ['role_id' => $roleId, 'menu_key' => 'employee_levels'],
                ['can_access' => true, 'updated_at' => now(), 'created_at' => now()]
            );
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('role_permissions')) {
            return;
        }

        DB::table('role_permissions')->where('menu_key', 'employee_levels')->delete();
    }
};
