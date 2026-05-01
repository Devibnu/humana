<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('roles')) {
            return;
        }

        $defaultRoles = [
            'Admin HR' => [
                'description' => 'Hak penuh akses',
                'permissions' => ['users', 'employees', 'departments', 'positions', 'work_locations', 'tenants', 'roles', 'attendances', 'leaves', 'payroll', 'reports'],
            ],
            'Manager' => [
                'description' => 'Kelola tim',
                'permissions' => ['users', 'employees', 'work_locations', 'attendances', 'leaves'],
            ],
            'Employee' => [
                'description' => 'Akses terbatas',
                'permissions' => ['attendances', 'leaves'],
            ],
        ];

        foreach ($defaultRoles as $name => $roleData) {
            DB::table('roles')->updateOrInsert(
                ['name' => $name],
                [
                    'description' => $roleData['description'],
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        foreach (['admin_hr' => 'Admin HR', 'manager' => 'Manager', 'employee' => 'Employee'] as $legacyName => $displayName) {
            $existingRoleId = DB::table('roles')->where('name', $legacyName)->value('id');

            if ($existingRoleId && ! DB::table('roles')->where('name', $displayName)->exists()) {
                DB::table('roles')->where('id', $existingRoleId)->update([
                    'name' => $displayName,
                    'updated_at' => now(),
                ]);
            }
        }

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'role_id')) {
                $table->foreignId('role_id')->nullable()->after('role')->constrained('roles')->nullOnDelete();
            }
        });

        $rolesBySystemKey = DB::table('roles')
            ->get(['id', 'name'])
            ->mapWithKeys(fn ($role) => [Str::of($role->name)->lower()->replaceMatches('/[^a-z0-9]+/', '_')->trim('_')->value() => $role->id])
            ->all();

        foreach ($defaultRoles as $name => $roleData) {
            $roleId = DB::table('roles')->where('name', $name)->value('id');

            if (! $roleId || ! Schema::hasTable('role_permissions')) {
                continue;
            }

            foreach ($roleData['permissions'] as $menuKey) {
                DB::table('role_permissions')->updateOrInsert(
                    ['role_id' => $roleId, 'menu_key' => $menuKey],
                    ['can_access' => true, 'updated_at' => now(), 'created_at' => now()]
                );
            }
        }

        DB::table('users')->select(['id', 'role'])->orderBy('id')->get()->each(function ($user) use ($rolesBySystemKey) {
            $systemKey = Str::of((string) $user->role)->lower()->replaceMatches('/[^a-z0-9]+/', '_')->trim('_')->value();

            if ($systemKey && array_key_exists($systemKey, $rolesBySystemKey)) {
                DB::table('users')->where('id', $user->id)->update([
                    'role_id' => $rolesBySystemKey[$systemKey],
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'role_id')) {
                $table->dropConstrainedForeignId('role_id');
            }
        });
    }
};