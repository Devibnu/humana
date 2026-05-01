<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->decimal('deduction_tax', 15, 2)->nullable()->after('allowance_health');
            $table->decimal('deduction_bpjs', 15, 2)->nullable()->after('deduction_tax');
            $table->decimal('deduction_loan', 15, 2)->nullable()->after('deduction_bpjs');
        });
    }

    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropColumn(['deduction_tax', 'deduction_bpjs', 'deduction_loan']);
        });
    }
};
