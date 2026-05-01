<?php

use App\Models\Leave;
use App\Models\LeaveType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->boolean('is_paid')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'name']);
        });

        Schema::table('leaves', function (Blueprint $table) {
            $table->foreignId('leave_type_id')
                ->nullable()
                ->after('employee_id')
                ->constrained('leave_types')
                ->cascadeOnDelete();
        });

        $this->migrateLegacyLeaveTypes();

        Schema::table('leaves', function (Blueprint $table) {
            $table->dropColumn('leave_type');
        });
    }

    public function down(): void
    {
        Schema::table('leaves', function (Blueprint $table) {
            $table->string('leave_type')->nullable()->after('employee_id');
        });

        $leaveTypeRows = DB::table('leave_types')->get(['id', 'name']);
        $byId = [];

        foreach ($leaveTypeRows as $row) {
            $byId[(int) $row->id] = Leave::canonicalLeaveTypeCode($row->name) ?? 'annual';
        }

        DB::table('leaves')->orderBy('id')->chunkById(200, function ($leaves) use ($byId): void {
            foreach ($leaves as $leave) {
                DB::table('leaves')
                    ->where('id', $leave->id)
                    ->update([
                        'leave_type' => $byId[(int) ($leave->leave_type_id ?? 0)] ?? 'annual',
                    ]);
            }
        });

        Schema::table('leaves', function (Blueprint $table) {
            $table->dropConstrainedForeignId('leave_type_id');
        });

        Schema::dropIfExists('leave_types');
    }

    protected function migrateLegacyLeaveTypes(): void
    {
        $defaults = LeaveType::defaults();

        DB::table('leaves')->orderBy('id')->chunkById(200, function ($leaves) use ($defaults): void {
            foreach ($leaves as $leave) {
                $tenantId = (int) $leave->tenant_id;
                $legacyType = (string) ($leave->leave_type ?? '');
                $definition = LeaveType::definitionFromInput($legacyType);

                if ($legacyType !== '') {
                    $normalized = strtolower(trim($legacyType));

                    if (isset($defaults[$normalized])) {
                        $definition = $defaults[$normalized];
                    }
                }

                $existingType = DB::table('leave_types')
                    ->where('tenant_id', $tenantId)
                    ->where('name', $definition['name'])
                    ->first();

                if (! $existingType) {
                    $leaveTypeId = DB::table('leave_types')->insertGetId([
                        'tenant_id' => $tenantId,
                        'name' => $definition['name'],
                        'is_paid' => $definition['is_paid'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } else {
                    $leaveTypeId = (int) $existingType->id;
                }

                DB::table('leaves')
                    ->where('id', $leave->id)
                    ->update(['leave_type_id' => $leaveTypeId]);
            }
        });
    }
};
