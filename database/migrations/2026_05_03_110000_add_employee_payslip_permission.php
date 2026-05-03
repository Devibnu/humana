<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('roles') || ! Schema::hasTable('role_permissions')) {
            return;
        }

        $employeeRoleId = DB::table('roles')->where('name', 'Employee')->value('id');

        if (! $employeeRoleId) {
            return;
        }

        DB::table('role_permissions')->updateOrInsert(
            [
                'role_id' => $employeeRoleId,
                'menu_key' => 'payroll.slips',
            ],
            [
                'can_access' => true,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        if (! Schema::hasTable('roles') || ! Schema::hasTable('role_permissions')) {
            return;
        }

        $employeeRoleId = DB::table('roles')->where('name', 'Employee')->value('id');

        if (! $employeeRoleId) {
            return;
        }

        DB::table('role_permissions')
            ->where('role_id', $employeeRoleId)
            ->where('menu_key', 'payroll.slips')
            ->delete();
    }
};
