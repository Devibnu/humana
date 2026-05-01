<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $domainWasMissing = ! Schema::hasColumn('tenants', 'domain');

        if ($domainWasMissing) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->string('domain')->nullable()->after('name');
            });
        }

        DB::table('tenants')->orderBy('id')->get()->each(function ($tenant) {
            $domain = $tenant->domain;

            if (! $domain) {
                $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $tenant->name), '-'));
                $domain = ($slug !== '' ? $slug : 'tenant-'.$tenant->id).'.test';
            }

            $baseDomain = $domain;
            $counter = 1;

            while (DB::table('tenants')
                ->where('domain', $domain)
                ->where('id', '!=', $tenant->id)
                ->exists()) {
                $parts = pathinfo($baseDomain);
                $filename = $parts['filename'] ?? 'tenant-'.$tenant->id;
                $extension = isset($parts['extension']) ? '.'.$parts['extension'] : '';
                $domain = $filename.'-'.$counter.$extension;
                $counter++;
            }

            DB::table('tenants')->where('id', $tenant->id)->update([
                'domain' => $domain,
            ]);
        });

        if ($domainWasMissing) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->string('domain')->nullable(false)->change();
                $table->unique('domain');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('tenants', 'domain')) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->dropUnique(['domain']);
                $table->dropColumn('domain');
            });
        }
    }
};