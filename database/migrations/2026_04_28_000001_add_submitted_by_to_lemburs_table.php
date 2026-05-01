<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lemburs', function (Blueprint $table): void {
            $table->foreignId('submitted_by')
                ->nullable()
                ->after('employee_id')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('lemburs', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('submitted_by');
        });
    }
};