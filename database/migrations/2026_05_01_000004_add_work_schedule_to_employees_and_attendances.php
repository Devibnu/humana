<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('work_schedule_id')
                ->nullable()
                ->after('work_location_id')
                ->constrained('work_schedules')
                ->nullOnDelete();
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->foreignId('work_schedule_id')
                ->nullable()
                ->after('employee_id')
                ->constrained('work_schedules')
                ->nullOnDelete();
            $table->time('scheduled_check_in')->nullable()->after('check_out');
            $table->time('scheduled_check_out')->nullable()->after('scheduled_check_in');
            $table->unsignedSmallInteger('late_minutes')->default(0)->after('scheduled_check_out');
            $table->unsignedSmallInteger('early_leave_minutes')->default(0)->after('late_minutes');
        });

        DB::table('work_schedules')
            ->where('code', 'office_hour')
            ->get(['id', 'tenant_id'])
            ->each(function ($schedule): void {
                DB::table('employees')
                    ->where('tenant_id', $schedule->tenant_id)
                    ->whereNull('work_schedule_id')
                    ->update(['work_schedule_id' => $schedule->id]);
            });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropConstrainedForeignId('work_schedule_id');
            $table->dropColumn([
                'scheduled_check_in',
                'scheduled_check_out',
                'late_minutes',
                'early_leave_minutes',
            ]);
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->dropConstrainedForeignId('work_schedule_id');
        });
    }
};
