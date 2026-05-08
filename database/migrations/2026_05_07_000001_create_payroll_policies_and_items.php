<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('status')->default('active');
            $table->string('employment_type', 20)->nullable();
            $table->string('employee_level_code', 50)->nullable();
            $table->foreignId('position_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('priority')->default(100);
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'employment_type']);
            $table->index(['tenant_id', 'employee_level_code']);
        });

        Schema::create('payroll_policy_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_policy_id')->constrained()->cascadeOnDelete();
            $table->string('component_code', 80);
            $table->string('component_name');
            $table->string('component_type', 20)->default('earning');
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('calculation_method', 40)->default('flat');
            $table->string('leave_paid_behavior', 40)->default('keep');
            $table->string('leave_unpaid_behavior', 40)->default('deduct_daily');
            $table->boolean('attendance_based')->default(false);
            $table->boolean('taxable')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['payroll_policy_id', 'component_type']);
            $table->index(['payroll_policy_id', 'component_code']);
        });

        Schema::create('payroll_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payroll_policy_component_id')->nullable()->constrained()->nullOnDelete();
            $table->string('component_code', 80);
            $table->string('component_name');
            $table->string('component_type', 20);
            $table->decimal('amount', 15, 2)->default(0);
            $table->decimal('quantity', 10, 2)->nullable();
            $table->decimal('rate', 15, 2)->nullable();
            $table->text('note')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['payroll_id', 'component_type']);
            $table->index(['payroll_id', 'component_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_items');
        Schema::dropIfExists('payroll_policy_components');
        Schema::dropIfExists('payroll_policies');
    }
};
