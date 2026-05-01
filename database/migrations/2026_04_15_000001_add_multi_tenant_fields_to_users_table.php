<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'tenant_id')) {
                $table->foreignId('tenant_id')->nullable()->after('id')->constrained('tenants')->nullOnDelete();
            }

            if (! Schema::hasColumn('users', 'role')) {
                $table->string('role')->default('employee')->after('password');
            }

            if (! Schema::hasColumn('users', 'status')) {
                $table->string('status')->default('active')->after('role');
            }
        });

        $tenantId = DB::table('tenants')->where('name', 'Default Tenant')->value('id');

        if (! $tenantId) {
            $tenantData = [
                'name' => 'Default Tenant',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (Schema::hasColumn('tenants', 'domain')) {
                $tenantData['domain'] = 'default-tenant.test';
            }

            if (Schema::hasColumn('tenants', 'slug')) {
                $tenantData['slug'] = 'default-tenant';
            }

            $tenantId = DB::table('tenants')->insertGetId($tenantData);
        }

        DB::table('users')->whereNull('tenant_id')->update([
            'tenant_id' => $tenantId,
        ]);

        DB::table('users')->update([
            'status' => 'active',
        ]);

        DB::table('users')->where('id', 1)->update([
            'role' => 'admin_hr',
        ]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'tenant_id')) {
                $table->dropConstrainedForeignId('tenant_id');
            }

            if (Schema::hasColumn('users', 'status')) {
                $table->dropColumn('status');
            }

            if (Schema::hasColumn('users', 'role')) {
                $table->dropColumn('role');
            }
        });
    }
};