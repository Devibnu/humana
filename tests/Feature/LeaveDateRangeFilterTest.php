<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Leave;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveDateRangeFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_hr_can_filter_leaves_by_date_range(): void
    {
        [$tenantA, $tenantB, $employeeA, $employeeB] = $this->seedLeaveDateRangeData();
        $admin = $this->makeUser('admin_hr', $tenantA, 'leave-date-admin@example.test', 'Leave Date Admin');

        $response = $this->actingAs($admin)->get(route('leaves.index', [
            'tenant_id' => $tenantA->id,
            'start_date' => '2026-04-11',
            'end_date' => '2026-04-15',
        ]));

        $response->assertOk();
        $response->assertSee($employeeA->name);
        $response->assertDontSee('Admin Before Range');
        $response->assertSee('Admin In Range');
        $response->assertDontSee('Admin After Range');
        $response->assertDontSee('Tenant B In Range');
    }

    public function test_manager_date_range_filter_remains_tenant_scoped(): void
    {
        [$tenantA, $tenantB, $employeeA, $employeeB] = $this->seedLeaveDateRangeData();
        $manager = $this->makeUser('manager', $tenantA, 'leave-date-manager@example.test', 'Leave Date Manager');

        $response = $this->actingAs($manager)->get(route('leaves.index', [
            'tenant_id' => $tenantB->id,
            'start_date' => '2026-04-11',
            'end_date' => '2026-04-15',
        ]));

        $response->assertOk();
        $response->assertSee($employeeA->name);
        $response->assertDontSee('Admin Before Range');
        $response->assertSee('Admin In Range');
        $response->assertDontSee($employeeB->name);
        $response->assertDontSee('Tenant B In Range');
    }

    public function test_employee_date_range_filter_only_shows_own_leave_requests(): void
    {
        [$tenantA, $tenantB, $employeeA, $employeeB, $employeeUser] = $this->seedEmployeeOwnedLeaveDateRangeData();

        $response = $this->actingAs($employeeUser)->get(route('leaves.index', [
            'start_date' => '2026-04-11',
            'end_date' => '2026-04-15',
        ]));

        $response->assertOk();
        $response->assertSee($employeeA->name);
        $response->assertDontSee('Employee Before Range');
        $response->assertSee('Employee In Range');
        $response->assertDontSee($employeeB->name);
        $response->assertDontSee('Other Employee In Range');
    }

    protected function seedLeaveDateRangeData(): array
    {
        $tenantA = $this->makeTenant('leave-date-range-tenant-a');
        $tenantB = $this->makeTenant('leave-date-range-tenant-b');
        $employeeA = $this->makeEmployee($tenantA, 'LVE-DAT-A1', 'Date Range Employee A', 'leave-date-a@example.test');
        $employeeB = $this->makeEmployee($tenantB, 'LVE-DAT-B1', 'Date Range Employee B', 'leave-date-b@example.test');

        $this->makeLeave($tenantA, $employeeA, '2026-04-09', '2026-04-10', 'Admin Before Range');
        $this->makeLeave($tenantA, $employeeA, '2026-04-12', '2026-04-14', 'Admin In Range');
        $this->makeLeave($tenantA, $employeeA, '2026-04-16', '2026-04-18', 'Admin After Range');
        $this->makeLeave($tenantB, $employeeB, '2026-04-12', '2026-04-14', 'Tenant B In Range');

        return [$tenantA, $tenantB, $employeeA, $employeeB];
    }

    protected function seedEmployeeOwnedLeaveDateRangeData(): array
    {
        $tenantA = $this->makeTenant('leave-date-range-employee-tenant-a');
        $tenantB = $this->makeTenant('leave-date-range-employee-tenant-b');
        $employeeUser = $this->makeUser('employee', $tenantA, 'leave-date-employee@example.test', 'Leave Date Employee');
        $otherUser = $this->makeUser('employee', $tenantB, 'leave-date-other@example.test', 'Leave Date Other');
        $employeeA = $this->makeEmployee($tenantA, 'LVE-DAT-E1', 'Employee Date Range A', 'leave-date-employee-a@example.test', $employeeUser);
        $employeeB = $this->makeEmployee($tenantB, 'LVE-DAT-E2', 'Employee Date Range B', 'leave-date-employee-b@example.test', $otherUser);

        $this->makeLeave($tenantA, $employeeA, '2026-04-09', '2026-04-10', 'Employee Before Range');
        $this->makeLeave($tenantA, $employeeA, '2026-04-12', '2026-04-14', 'Employee In Range');
        $this->makeLeave($tenantB, $employeeB, '2026-04-12', '2026-04-14', 'Other Employee In Range');

        return [$tenantA, $tenantB, $employeeA, $employeeB, $employeeUser];
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

    protected function makeEmployee(Tenant $tenant, string $code, string $name, string $email, ?User $user = null): Employee
    {
        return Employee::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user?->id,
            'employee_code' => $code,
            'name' => $name,
            'email' => $email,
            'status' => 'active',
        ]);
    }

    protected function makeLeave(Tenant $tenant, Employee $employee, string $startDate, string $endDate, string $reason): Leave
    {
        return Leave::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'leave_type' => 'annual',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'reason' => $reason,
            'status' => 'pending',
        ]);
    }
}