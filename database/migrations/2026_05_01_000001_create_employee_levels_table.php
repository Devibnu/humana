<?php

use App\Models\EmployeeLevel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('code', 50);
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status', 20)->default('active');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['tenant_id', 'code']);
            $table->unique(['tenant_id', 'name']);
        });

        $now = now();
        $rows = [];

        DB::table('tenants')->orderBy('id')->get(['id'])->each(function ($tenant) use (&$rows, $now): void {
            foreach (EmployeeLevel::defaults() as $level) {
                $rows[] = [
                    'tenant_id' => $tenant->id,
                    'code' => $level['code'],
                    'name' => $level['name'],
                    'description' => null,
                    'status' => 'active',
                    'sort_order' => $level['sort_order'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        });

        DB::table('employees')
            ->whereNotNull('role')
            ->select('tenant_id', 'role')
            ->distinct()
            ->orderBy('tenant_id')
            ->get()
            ->each(function ($employeeRole) use (&$rows, $now): void {
                $code = Str::slug((string) $employeeRole->role, '_');

                if ($code === '') {
                    return;
                }

                $exists = collect($rows)->contains(fn (array $row) => (int) $row['tenant_id'] === (int) $employeeRole->tenant_id && $row['code'] === $code);

                if ($exists) {
                    return;
                }

                $rows[] = [
                    'tenant_id' => $employeeRole->tenant_id,
                    'code' => $code,
                    'name' => Str::headline((string) $employeeRole->role),
                    'description' => 'Level dari data karyawan existing.',
                    'status' => 'active',
                    'sort_order' => 100,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            });

        if ($rows !== []) {
            DB::table('employee_levels')->insert($rows);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_levels');
    }
};
