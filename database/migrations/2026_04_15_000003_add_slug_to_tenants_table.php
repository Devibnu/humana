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
        if (! Schema::hasColumn('tenants', 'slug')) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->string('slug')->nullable()->after('name');
            });
        }

        DB::table('tenants')->orderBy('id')->get()->each(function ($tenant) {
            $baseSlug = Str::slug($tenant->name);
            $slug = $baseSlug !== '' ? $baseSlug : 'tenant-'.$tenant->id;
            $counter = 1;

            while (DB::table('tenants')
                ->where('slug', $slug)
                ->where('id', '!=', $tenant->id)
                ->exists()) {
                $slug = ($baseSlug !== '' ? $baseSlug : 'tenant-'.$tenant->id).'-'.$counter;
                $counter++;
            }

            DB::table('tenants')->where('id', $tenant->id)->update([
                'slug' => $slug,
            ]);
        });

        Schema::table('tenants', function (Blueprint $table) {
            $table->string('slug')->nullable(false)->change();
            $table->unique('slug');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('tenants', 'slug')) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->dropUnique(['slug']);
                $table->dropColumn('slug');
            });
        }
    }
};