<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add marital_status to family_members
        Schema::table('family_members', function (Blueprint $table) {
            $table->string('marital_status', 20)->nullable()->after('job');
        });

        // Add employment & marital fields to employees
        Schema::table('employees', function (Blueprint $table) {
            $table->string('marital_status', 20)->nullable()->after('address');
            $table->string('employment_type', 20)->nullable()->after('status');
            $table->date('contract_start_date')->nullable()->after('employment_type');
            $table->date('contract_end_date')->nullable()->after('contract_start_date');
        });
    }

    public function down(): void
    {
        Schema::table('family_members', function (Blueprint $table) {
            $table->dropColumn('marital_status');
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['marital_status', 'employment_type', 'contract_start_date', 'contract_end_date']);
        });
    }
};
