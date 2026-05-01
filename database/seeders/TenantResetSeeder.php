<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TenantResetSeeder extends Seeder
{
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();

        foreach ([
            'bank_accounts',
            'family_members',
            'attendance_logs',
            'attendances',
            'leaves',
            'employees',
            'work_locations',
            'departments',
            'positions',
            'users',
            'tenants',
        ] as $table) {
            DB::table($table)->truncate();
        }

        Schema::enableForeignKeyConstraints();

        Tenant::create([
            'name' => 'Default Tenant',
            'slug' => 'default-tenant',
            'domain' => 'default-tenant.test',
            'status' => 'active',
            'code' => 'DEFAULT',
            'address' => 'Tenant default untuk HRIS Humana',
            'contact' => null,
        ]);
    }
}