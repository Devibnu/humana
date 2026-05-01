<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('overtime_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->enum('salary_type', ['daily', 'monthly']);
            $table->integer('standard_hours_per_day')->default(8);
            $table->decimal('rate_first_hour', 8, 2)->default(1.5);
            $table->decimal('rate_next_hours', 8, 2)->default(2.0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('overtime_rules');
    }
};