<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->foreignId('leave_id')
                ->nullable()
                ->after('employee_id')
                ->constrained('leaves')
                ->nullOnDelete();

            $table->index(['leave_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropIndex(['leave_id', 'date']);
            $table->dropConstrainedForeignId('leave_id');
        });
    }
};
