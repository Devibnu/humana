<?php

use App\Models\Tenant;
use App\Models\WorkSchedule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('code', 50);
            $table->string('name');
            $table->time('check_in_time');
            $table->time('check_out_time');
            $table->unsignedSmallInteger('late_tolerance_minutes')->default(0);
            $table->unsignedSmallInteger('early_leave_tolerance_minutes')->default(0);
            $table->text('description')->nullable();
            $table->string('status', 20)->default('active');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['tenant_id', 'code']);
            $table->unique(['tenant_id', 'name']);
            $table->index(['tenant_id', 'status']);
        });

        $now = now();
        $rows = [];

        foreach (Tenant::query()->get(['id']) as $tenant) {
            foreach (WorkSchedule::defaults() as $schedule) {
                $rows[] = [
                    'tenant_id' => $tenant->id,
                    'code' => $schedule['code'],
                    'name' => $schedule['name'],
                    'check_in_time' => $schedule['check_in_time'],
                    'check_out_time' => $schedule['check_out_time'],
                    'late_tolerance_minutes' => $schedule['late_tolerance_minutes'],
                    'early_leave_tolerance_minutes' => $schedule['early_leave_tolerance_minutes'],
                    'description' => null,
                    'status' => 'active',
                    'sort_order' => $schedule['sort_order'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if ($rows !== []) {
            DB::table('work_schedules')->insert($rows);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('work_schedules');
    }
};
