<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::updateOrCreate(
            ['domain' => 'default-tenant.test'],
            [
                'name' => 'Default Tenant',
                'slug' => 'default-tenant',
                'code' => 'DEFAULT',
                'status' => 'active',
                'subscription_plan' => 'basic',
                'description' => 'Tenant default untuk HRIS Humana',
            ]
        );

        $adminRole = Role::query()->get()->first(fn (Role $role) => $role->system_key === 'admin_hr');
        $managerRole = Role::query()->get()->first(fn (Role $role) => $role->system_key === 'manager');
        $employeeRole = Role::query()->get()->first(fn (Role $role) => $role->system_key === 'employee');

        User::updateOrCreate(
            ['email' => 'admin@humana.test'],
            [
                'name' => 'Admin HR',
                'password' => Hash::make('password'),
                'role' => 'admin_hr',
                'role_id' => $adminRole?->id,
                'tenant_id' => $tenant->id,
                'status' => 'active',
            ]
        );

        User::updateOrCreate(
            ['email' => 'manager@humana.test'],
            [
                'name' => 'Manager',
                'password' => Hash::make('password'),
                'role' => 'manager',
                'role_id' => $managerRole?->id,
                'tenant_id' => $tenant->id,
                'status' => 'active',
            ]
        );

        User::updateOrCreate(
            ['email' => 'employee@humana.test'],
            [
                'name' => 'Employee',
                'password' => Hash::make('password'),
                'role' => 'employee',
                'role_id' => $employeeRole?->id,
                'tenant_id' => $tenant->id,
                'status' => 'active',
            ]
        );
    }
}
