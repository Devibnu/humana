<?php

namespace Tests\Feature;

use App\Exports\LeavesExport;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveDurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_leave_duration_is_calculated_inclusive_of_both_dates(): void
    {
        $tenant = Tenant::create([
            'name' => 'Leave Duration Tenant',
            'slug' => 'leave-duration-tenant',
            'domain' => 'leave-duration-tenant.test',
            'status' => 'active',
        ]);

        $employee = Employee::create([
            'tenant_id' => $tenant->id,
            'employee_code' => 'LVE-DUR-1',
            'name' => 'Leave Duration Employee',
            'email' => 'leave-duration-employee@example.test',
            'status' => 'active',
        ]);

        $leave = Leave::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'leave_type' => 'annual',
            'start_date' => '2026-04-20',
            'end_date' => '2026-04-22',
            'reason' => 'Three day leave',
            'status' => 'pending',
        ]);

        $this->assertSame(3, $leave->duration);

        $rows = (new LeavesExport(Leave::with(['tenant', 'employee'])->get()))->collection()->toArray();

        $this->assertSame(3, $rows[0]['duration_days']);
    }
}