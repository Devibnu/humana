<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeSelfScopeTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenantA;

    protected Tenant $tenantB;

    public function setUp(): void
    {
        parent::setUp();

        $this->tenantA = Tenant::create([
            'name' => 'Self Scope Tenant A',
            'slug' => 'self-scope-tenant-a',
            'domain' => 'self-scope-tenant-a.test',
            'status' => 'active',
        ]);

        $this->tenantB = Tenant::create([
            'name' => 'Self Scope Tenant B',
            'slug' => 'self-scope-tenant-b',
            'domain' => 'self-scope-tenant-b.test',
            'status' => 'active',
        ]);
    }

    public function test_employee_only_views_own_attendance_via_user_id(): void
    {
        $employeeUser = $this->makeUser('employee', $this->tenantA, 'linked-user@example.test', 'Linked Employee User');

        $linkedEmployee = Employee::create([
            'user_id' => $employeeUser->id,
            'tenant_id' => $this->tenantA->id,
            'employee_code' => 'SELF-EMP-1',
            'name' => 'Linked Employee Record',
            'email' => 'different-employee-record@example.test',
            'status' => 'active',
        ]);

        $sameEmailButNotLinked = Employee::create([
            'tenant_id' => $this->tenantA->id,
            'employee_code' => 'SELF-EMP-2',
            'name' => 'Same Email But Not Linked',
            'email' => 'linked-user@example.test',
            'status' => 'active',
        ]);

        Attendance::create([
            'tenant_id' => $this->tenantA->id,
            'employee_id' => $linkedEmployee->id,
            'date' => '2026-04-17',
            'check_in' => '08:00',
            'check_out' => '17:00',
            'status' => 'present',
        ]);

        Attendance::create([
            'tenant_id' => $this->tenantA->id,
            'employee_id' => $sameEmailButNotLinked->id,
            'date' => '2026-04-18',
            'check_in' => '08:10',
            'check_out' => '17:10',
            'status' => 'late',
        ]);

        $response = $this->actingAs($employeeUser)->get(route('attendances.index'));

        $response->assertOk();
        $response->assertSee($linkedEmployee->name);
        $response->assertDontSee($sameEmailButNotLinked->name);
    }

    public function test_manager_still_views_attendance_scoped_by_tenant(): void
    {
        $manager = $this->makeUser('manager', $this->tenantA, 'self-scope-manager@example.test', 'Self Scope Manager');

        $employeeA = Employee::create([
            'tenant_id' => $this->tenantA->id,
            'employee_code' => 'SELF-MGR-1',
            'name' => 'Tenant A Attendance',
            'email' => 'tenant-a-attendance@example.test',
            'status' => 'active',
        ]);

        $employeeB = Employee::create([
            'tenant_id' => $this->tenantB->id,
            'employee_code' => 'SELF-MGR-2',
            'name' => 'Tenant B Attendance',
            'email' => 'tenant-b-attendance@example.test',
            'status' => 'active',
        ]);

        Attendance::create([
            'tenant_id' => $this->tenantA->id,
            'employee_id' => $employeeA->id,
            'date' => '2026-04-19',
            'check_in' => '08:00',
            'check_out' => '17:00',
            'status' => 'present',
        ]);

        Attendance::create([
            'tenant_id' => $this->tenantB->id,
            'employee_id' => $employeeB->id,
            'date' => '2026-04-20',
            'check_in' => '08:00',
            'check_out' => '17:00',
            'status' => 'present',
        ]);

        $response = $this->actingAs($manager)->get(route('attendances.index', ['tenant_id' => $this->tenantB->id]));

        $response->assertOk();
        $response->assertSee($employeeA->name);
        $response->assertDontSee($employeeB->name);
    }

    public function test_admin_hr_can_still_view_across_tenants(): void
    {
        $admin = $this->makeUser('admin_hr', $this->tenantA, 'self-scope-admin@example.test', 'Self Scope Admin');

        $employeeA = Employee::create([
            'tenant_id' => $this->tenantA->id,
            'employee_code' => 'SELF-ADM-1',
            'name' => 'Admin Tenant A Attendance',
            'email' => 'admin-tenant-a@example.test',
            'status' => 'active',
        ]);

        $employeeB = Employee::create([
            'tenant_id' => $this->tenantB->id,
            'employee_code' => 'SELF-ADM-2',
            'name' => 'Admin Tenant B Attendance',
            'email' => 'admin-tenant-b@example.test',
            'status' => 'active',
        ]);

        Attendance::create([
            'tenant_id' => $this->tenantA->id,
            'employee_id' => $employeeA->id,
            'date' => '2026-04-21',
            'check_in' => '08:00',
            'check_out' => '17:00',
            'status' => 'present',
        ]);

        Attendance::create([
            'tenant_id' => $this->tenantB->id,
            'employee_id' => $employeeB->id,
            'date' => '2026-04-22',
            'check_in' => '08:05',
            'check_out' => '17:05',
            'status' => 'present',
        ]);

        $response = $this->actingAs($admin)->get(route('attendances.index'));

        $response->assertOk();
        $response->assertSee($employeeA->name);
        $response->assertSee($employeeB->name);
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
}