<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->decimal('allowance_thr', 15, 2)->nullable()->after('allowance_health');
            $table->decimal('allowance_other', 15, 2)->nullable()->after('allowance_thr');
        });
    }

    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropColumn(['allowance_thr', 'allowance_other']);
        });
    }
};
