<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Leave;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_hr_sees_full_summary_days(): void
    {
        [$tenantA, $tenantB, $employeeA, $employeeB] = $this->seedSummaryData();
        $admin = $this->makeUser('admin_hr', $tenantA, 'leave-summary-admin@example.test', 'Leave Summary Admin');

        $response = $this->actingAs($admin)->get(route('leaves.index'));

        $response->assertOk();
    $response->assertSee('Pending: 2 permintaan / 6 hari');
    $response->assertSee('Approved: 2 permintaan / 5 hari');
    $response->assertSee('Rejected: 1 permintaan / 3 hari');
    }

    public function test_manager_sees_tenant_scoped_summary_days(): void
    {
        [$tenantA, $tenantB, $employeeA, $employeeB] = $this->seedSummaryData();
        $manager = $this->makeUser('manager', $tenantA, 'leave-summary-manager@example.test', 'Leave Summary Manager');

        $response = $this->actingAs($manager)->get(route('leaves.index'));

        $response->assertOk();
    $response->assertSee('Pending: 1 permintaan / 4 hari');
    $response->assertSee('Approved: 1 permintaan / 3 hari');
    $response->assertSee('Rejected: 1 permintaan / 3 hari');
    }

    public function test_employee_sees_only_own_summary_days(): void
    {
        $tenant = $this->makeTenant('leave-summary-employee-tenant');
        $employeeUser = $this->makeUser('employee', $tenant, 'leave-summary-employee@example.test', 'Leave Summary Employee');
        $otherUser = $this->makeUser('employee', $tenant, 'leave-summary-other@example.test', 'Leave Summary Other');
        $employee = $this->makeEmployee($tenant, 'LVE-SUM-E1', 'Leave Summary Employee A', 'leave-summary-employee-a@example.test', $employeeUser);
        $otherEmployee = $this->makeEmployee($tenant, 'LVE-SUM-E2', 'Leave Summary Employee B', 'leave-summary-employee-b@example.test', $otherUser);

        $this->makeLeave($tenant, $employee, '2026-04-10', '2026-04-11', 'pending');
        $this->makeLeave($tenant, $employee, '2026-04-12', '2026-04-14', 'approved');
        $this->makeLeave($tenant, $otherEmployee, '2026-04-15', '2026-04-18', 'rejected');

        $response = $this->actingAs($employeeUser)->get(route('leaves.index'));

        $response->assertOk();
    $response->assertSee('Pending: 1 permintaan / 2 hari');
    $response->assertSee('Approved: 1 permintaan / 3 hari');
    $response->assertSee('Rejected: 0 permintaan / 0 hari');
    $response->assertDontSee('Pending: 2 permintaan / 4 hari');
    }

    protected function seedSummaryData(): array
    {
        $tenantA = $this->makeTenant('leave-summary-tenant-a');
        $tenantB = $this->makeTenant('leave-summary-tenant-b');
        $employeeA = $this->makeEmployee($tenantA, 'LVE-SUM-A1', 'Leave Summary Employee A', 'leave-summary-a@example.test');
        $employeeB = $this->makeEmployee($tenantB, 'LVE-SUM-B1', 'Leave Summary Employee B', 'leave-summary-b@example.test');

        $this->makeLeave($tenantA, $employeeA, '2026-04-10', '2026-04-13', 'pending');
        $this->makeLeave($tenantA, $employeeA, '2026-04-14', '2026-04-16', 'approved');
        $this->makeLeave($tenantA, $employeeA, '2026-04-17', '2026-04-19', 'rejected');
        $this->makeLeave($tenantB, $employeeB, '2026-04-20', '2026-04-21', 'pending');
        $this->makeLeave($tenantB, $employeeB, '2026-04-22', '2026-04-23', 'approved');

        return [$tenantA, $tenantB, $employeeA, $employeeB];
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

    protected function makeLeave(Tenant $tenant, Employee $employee, string $startDate, string $endDate, string $status): Leave
    {
        return Leave::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'leave_type' => 'annual',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'reason' => 'Summary '.$status,
            'status' => $status,
        ]);
    }
}