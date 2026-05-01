<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_types', function (Blueprint $table) {
            $table->boolean('wajib_lampiran')->default(false)->after('is_paid');
            $table->boolean('wajib_persetujuan')->default(true)->after('wajib_lampiran');
            $table->enum('alur_persetujuan', ['single', 'multi', 'auto'])->default('single')->after('wajib_persetujuan');
        });

        Schema::table('leaves', function (Blueprint $table) {
            $table->string('attachment_path')->nullable()->after('reason');
            $table->enum('approval_stage', ['supervisor', 'manager', 'hr'])->nullable()->after('status');
            $table->enum('current_approval_role', ['supervisor', 'manager', 'hr'])->nullable()->after('approval_stage');
        });
    }

    public function down(): void
    {
        Schema::table('leaves', function (Blueprint $table) {
            $table->dropColumn(['attachment_path', 'approval_stage', 'current_approval_role']);
        });

        Schema::table('leave_types', function (Blueprint $table) {
            $table->dropColumn(['wajib_lampiran', 'wajib_persetujuan', 'alur_persetujuan']);
        });
    }
};
