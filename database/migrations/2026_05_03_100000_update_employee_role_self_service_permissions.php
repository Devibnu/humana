<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $employeePermissions = [
        'profile',
        'attendances',
        'leaves',
        'leaves.create',
        'lembur',
        'lembur.submit',
    ];

    public function up(): void
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
            ->whereNotIn('menu_key', $this->employeePermissions)
            ->delete();

        foreach ($this->employeePermissions as $menuKey) {
            DB::table('role_permissions')->updateOrInsert(
                [
                    'role_id' => $employeeRoleId,
                    'menu_key' => $menuKey,
                ],
                [
                    'can_access' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
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
            ->whereIn('menu_key', ['attendances', 'leaves', 'leaves.create'])
            ->delete();
    }
};
