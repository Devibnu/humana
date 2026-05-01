<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveAttendanceSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_approved_leave_creates_attendance_leave_rows_for_each_day(): void
    {
        $tenant = $this->makeTenant('leave-sync-approved-tenant');
        $employee = $this->makeEmployee($tenant, 'LVS-001', 'Leave Sync Employee', 'leave-sync-employee@example.test');

        $leave = Leave::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'leave_type_id' => $this->resolveLeaveTypeId($tenant, 'annual'),
            'start_date' => '2026-04-20',
            'end_date' => '2026-04-22',
            'reason' => 'Annual leave',
            'status' => 'approved',
        ]);

        $this->assertDatabaseHas('attendances', [
            'employee_id' => $employee->id,
            'date' => '2026-04-20',
            'status' => 'leave',
            'leave_id' => $leave->id,
        ]);

        $this->assertDatabaseHas('attendances', [
            'employee_id' => $employee->id,
            'date' => '2026-04-21',
            'status' => 'leave',
            'leave_id' => $leave->id,
        ]);

        $this->assertDatabaseHas('attendances', [
            'employee_id' => $employee->id,
            'date' => '2026-04-22',
            'status' => 'leave',
            'leave_id' => $leave->id,
        ]);
    }

    public function test_rejecting_leave_removes_auto_synced_leave_attendances(): void
    {
        $tenant = $this->makeTenant('leave-sync-reject-tenant');
        $employee = $this->makeEmployee($tenant, 'LVS-002', 'Leave Sync Employee 2', 'leave-sync-employee-2@example.test');

        $leave = Leave::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'leave_type_id' => $this->resolveLeaveTypeId($tenant, 'sick'),
            'start_date' => '2026-04-23',
            'end_date' => '2026-04-24',
            'reason' => 'Medical leave',
            'status' => 'approved',
        ]);

        $this->assertSame(2, Attendance::where('leave_id', $leave->id)->count());

        $leave->update([
            'status' => 'rejected',
        ]);

        $this->assertSame(0, Attendance::where('leave_id', $leave->id)->count());
    }

    protected function makeTenant(string $slug): Tenant
    {
        return Tenant::create([
            'name' => ucwords(str_replace('-', ' ', $slug)),
            'slug' => $slug,
            'domain' => $slug.'.test',
            'status' => 'active',
        ]);
    }

    protected function makeEmployee(Tenant $tenant, string $code, string $name, string $email): Employee
    {
        return Employee::create([
            'tenant_id' => $tenant->id,
            'employee_code' => $code,
            'name' => $name,
            'email' => $email,
            'status' => 'active',
        ]);
    }

    protected function resolveLeaveTypeId(Tenant $tenant, string $type): int
    {
        $definition = LeaveType::definitionFromInput($type);

        return (int) LeaveType::query()->firstOrCreate(
            ['tenant_id' => $tenant->id, 'name' => $definition['name']],
            ['is_paid' => $definition['is_paid']]
        )->id;
    }
}
