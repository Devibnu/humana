<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('payroll_day')->default(25);
            $table->unsignedTinyInteger('period_start_day')->default(1);
            $table->unsignedTinyInteger('period_end_day')->default(31);
            $table->enum('period_month_offset', ['current', 'previous'])->default('current');
            $table->boolean('publish_slips_on_approval')->default(false);
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('payroll_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payroll_setting_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->date('payroll_month');
            $table->date('period_start');
            $table->date('period_end');
            $table->date('payroll_date');
            $table->string('status')->default('draft');
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['tenant_id', 'payroll_month']);
        });

        Schema::table('payrolls', function (Blueprint $table) {
            $table->foreignId('payroll_period_id')->nullable()->after('id')->constrained('payroll_periods')->nullOnDelete();
            $table->string('status')->default('draft')->after('payroll_period_id');
            $table->timestamp('published_at')->nullable()->after('status');
            $table->unique(['payroll_period_id', 'employee_id']);
        });
    }

    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropUnique(['payroll_period_id', 'employee_id']);
            $table->dropConstrainedForeignId('payroll_period_id');
            $table->dropColumn(['status', 'published_at']);
        });

        Schema::dropIfExists('payroll_periods');
        Schema::dropIfExists('payroll_settings');
    }
};
