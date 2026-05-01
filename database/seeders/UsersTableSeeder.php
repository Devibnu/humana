<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::find(1);

        if (! $tenant) {
            $tenantId = DB::table('tenants')->insertGetId([
                'name'       => 'Default Tenant',
                'slug'       => 'default-tenant',
                'domain'     => 'default-tenant.test',
                'status'     => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $tenant = Tenant::findOrFail($tenantId);
        }

        $adminRole    = Role::where('name', 'Admin HR')->first();
        $managerRole  = Role::where('name', 'Manager')->first();
        $employeeRole = Role::where('name', 'Employee')->first();

        User::updateOrCreate(
            ['email' => 'admin@humana.test'],
            [
                'tenant_id' => $tenant->id,
                'name'      => 'Admin HR',
                'password'  => Hash::make('password'),
                'role_id'   => $adminRole?->id,
                'status'    => 'active',
            ]
        );

        User::updateOrCreate(
            ['email' => 'manager@humana.test'],
            [
                'tenant_id' => $tenant->id,
                'name'      => 'Manager',
                'password'  => Hash::make('password'),
                'role_id'   => $managerRole?->id,
                'status'    => 'active',
            ]
        );

        User::updateOrCreate(
            ['email' => 'employee@humana.test'],
            [
                'tenant_id' => $tenant->id,
                'name'      => 'Employee',
                'password'  => Hash::make('password'),
                'role_id'   => $employeeRole?->id,
                'status'    => 'active',
            ]
        );
    }
}
