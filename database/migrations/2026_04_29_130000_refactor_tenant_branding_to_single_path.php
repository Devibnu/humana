<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('tenants', 'branding_path')) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->string('branding_path')->nullable()->after('contact');
            });
        }

        DB::table('tenants')->update([
            'branding_path' => DB::raw('COALESCE(branding_path, logo_path, favicon_path)'),
        ]);

        $dropColumns = [];

        if (Schema::hasColumn('tenants', 'logo_path')) {
            $dropColumns[] = 'logo_path';
        }

        if (Schema::hasColumn('tenants', 'favicon_path')) {
            $dropColumns[] = 'favicon_path';
        }

        if ($dropColumns !== []) {
            Schema::table('tenants', function (Blueprint $table) use ($dropColumns) {
                $table->dropColumn($dropColumns);
            });
        }
    }

    public function down(): void
    {
        $hasLogoPath = Schema::hasColumn('tenants', 'logo_path');
        $hasFaviconPath = Schema::hasColumn('tenants', 'favicon_path');

        if (! $hasLogoPath || ! $hasFaviconPath) {
            Schema::table('tenants', function (Blueprint $table) use ($hasLogoPath, $hasFaviconPath) {
                if (! $hasLogoPath) {
                    $table->string('logo_path')->nullable()->after('contact');
                }

                if (! $hasFaviconPath) {
                    $table->string('favicon_path')->nullable()->after('logo_path');
                }
            });
        }

        if (Schema::hasColumn('tenants', 'branding_path')) {
            DB::table('tenants')->update([
                'logo_path' => DB::raw('COALESCE(logo_path, branding_path)'),
                'favicon_path' => DB::raw('COALESCE(favicon_path, branding_path)'),
            ]);

            Schema::table('tenants', function (Blueprint $table) {
                $table->dropColumn('branding_path');
            });
        }
    }
};
