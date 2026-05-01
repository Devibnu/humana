<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->decimal('allowance_transport', 15, 2)->nullable()->after('daily_wage');
            $table->decimal('allowance_meal', 15, 2)->nullable()->after('allowance_transport');
            $table->decimal('allowance_health', 15, 2)->nullable()->after('allowance_meal');
        });
    }

    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropColumn(['allowance_transport', 'allowance_meal', 'allowance_health']);
        });
    }
};
