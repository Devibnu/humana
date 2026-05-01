<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('absence_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->integer('working_hours_per_day')->default(8);
            $table->integer('working_days_per_month')->default(22);
            $table->integer('tolerance_minutes')->default(15);
            $table->enum('rate_type', ['flat', 'proportional'])->default('proportional');
            $table->boolean('alpha_full_day')->default(true);
            $table->timestamps();
            $table->unique(['tenant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absence_rules');
    }
};
