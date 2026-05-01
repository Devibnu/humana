<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_approve_and_reject_tenant_scoped_leave_requests(): void
    {
        $tenantA = $this->makeTenant('leave-approval-tenant-a');
        $tenantB = $this->makeTenant('leave-approval-tenant-b');
        $manager = $this->makeUser('manager', $tenantA, 'leave-approval-manager@example.test', 'Leave Approval Manager');
        $employeeA = $this->makeEmployee($tenantA, 'LVE-APP-A1', 'Leave Approval Employee A', 'leave-approval-employee-a@example.test');
        $employeeB = $this->makeEmployee($tenantB, 'LVE-APP-B1', 'Leave Approval Employee B', 'leave-approval-employee-b@example.test');

        $tenantALeaveApprove = $this->makeLeave($tenantA, $employeeA, 'annual', '2026-04-20', '2026-04-21', 'Approve this leave');
        $tenantALeaveReject = $this->makeLeave($tenantA, $employeeA, 'sick', '2026-04-22', '2026-04-23', 'Reject this leave');
        $tenantBLeave = $this->makeLeave($tenantB, $employeeB, 'permission', '2026-04-24', '2026-04-24', 'Other tenant leave');

        $response = $this->actingAs($manager)->get(route('leaves.index', ['tenant_id' => $tenantB->id]));

        $response->assertOk();
        $response->assertSee($employeeA->name);
        $response->assertDontSee($employeeB->name);

        $this->actingAs($manager)->get(route('leaves.edit', ['leaf' => $tenantALeaveApprove->id]))->assertOk();
        $this->actingAs($manager)->get(route('leaves.edit', ['leaf' => $tenantBLeave->id]))->assertForbidden();
        $this->actingAs($manager)->get(route('leaves.create'))->assertForbidden();

        $this->actingAs($manager)->put(route('leaves.update', ['leaf' => $tenantALeaveApprove->id]), [
            'status' => 'approved',
        ])->assertRedirect(route('leaves.index'));

        $this->actingAs($manager)->put(route('leaves.update', ['leaf' => $tenantALeaveReject->id]), [
            'status' => 'rejected',
        ])->assertRedirect(route('leaves.index'));

        $this->assertDatabaseHas('leaves', [
            'id' => $tenantALeaveApprove->id,
            'status' => 'approved',
        ]);

        $this->assertDatabaseHas('leaves', [
            'id' => $tenantALeaveReject->id,
            'status' => 'rejected',
        ]);

        $this->actingAs($manager)->put(route('leaves.update', ['leaf' => $tenantBLeave->id]), [
            'status' => 'approved',
        ])->assertForbidden();

        $this->actingAs($manager)->delete(route('leaves.destroy', ['leaf' => $tenantALeaveApprove->id]))->assertForbidden();
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

    protected function makeUser(string $role, Tenant $tenant, string $email, string $name): User
    {
        return User::create([
            'tenant_id' => $tenant->id,
            'name' => $name,
            'email' => $email,
            'password' => 'password',
            'role' => $role,
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

    protected function makeLeave(Tenant $tenant, Employee $employee, string $type, string $startDate, string $endDate, string $reason): Leave
    {
        return Leave::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'leave_type_id' => $this->resolveLeaveTypeId($tenant, $type),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'reason' => $reason,
            'status' => 'pending',
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