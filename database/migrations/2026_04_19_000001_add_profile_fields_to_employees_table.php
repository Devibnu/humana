<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('role', 50)->default('staff')->after('phone');
            $table->date('start_date')->nullable()->after('work_location_id');
            $table->string('avatar_path')->nullable()->after('start_date');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['role', 'start_date', 'avatar_path']);
        });
    }
};