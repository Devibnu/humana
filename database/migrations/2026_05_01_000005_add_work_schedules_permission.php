<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $roleNames = ['Admin HR', 'Manager'];

        DB::table('roles')
            ->whereIn('name', $roleNames)
            ->orderBy('id')
            ->pluck('id')
            ->each(function ($roleId): void {
                DB::table('role_permissions')->updateOrInsert(
                    ['role_id' => $roleId, 'menu_key' => 'work_schedules'],
                    ['can_access' => true, 'updated_at' => now(), 'created_at' => now()]
                );
            });
    }

    public function down(): void
    {
        DB::table('role_permissions')->where('menu_key', 'work_schedules')->delete();
    }
};
