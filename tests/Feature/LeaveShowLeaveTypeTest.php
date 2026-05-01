<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveShowLeaveTypeTest extends TestCase
{
    use RefreshDatabase;

    public function test_leave_detail_shows_leave_type_name(): void
    {
        $tenant = Tenant::create([
            'name' => 'JS',
            'slug' => 'js',
            'domain' => 'js.test',
            'status' => 'active',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Leave Show Admin',
            'email' => 'leave-show-admin@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $employee = Employee::create([
            'tenant_id' => $tenant->id,
            'employee_code' => 'LSH-001',
            'name' => 'Budi',
            'email' => 'leave-show-employee@example.test',
            'status' => 'active',
        ]);

        $leaveType = LeaveType::create([
            'tenant_id' => $tenant->id,
            'name' => 'Cuti Tahunan',
            'is_paid' => true,
        ]);

        Leave::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-05-01',
            'end_date' => '2026-05-02',
            'reason' => 'Cuti tahunan keluarga',
            'status' => 'approved',
        ]);

        $response = $this->actingAs($admin)->get(route('employees.leaves.show', [
            'employee' => $employee,
        ]));

        $response->assertStatus(200);
        $response->assertSee('Cuti Tahunan');
    }
}
