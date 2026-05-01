<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::find(1);

        if (! $tenant) {
            DB::table('tenants')->insert([
                'id' => 1,
                'name' => 'Default Tenant',
                'slug' => 'default-tenant',
                'domain' => 'default-tenant.test',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return;
        }

        $tenant->update([
            'name' => 'Default Tenant',
            'slug' => 'default-tenant',
            'domain' => 'default-tenant.test',
            'status' => 'active',
        ]);
    }
}