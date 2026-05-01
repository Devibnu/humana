<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRoleLinkedFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_hr_can_filter_users_by_role_and_link_status(): void
    {
        $tenant = $this->makeTenant('user-role-linked-filter-admin');
        $admin = $this->makeUser('admin_hr', $tenant, 'admin-user-role-linked@example.test', 'Admin User Role Linked');
        $linkedEmployeeUser = $this->makeUser('employee', $tenant, 'linked-employee-role-filter@example.test', 'Linked Employee Role Filter');
        $unlinkedEmployeeUser = $this->makeUser('employee', $tenant, 'standalone-employee-role-filter@example.test', 'Unlinked Employee Role Filter');
        $linkedManagerUser = $this->makeUser('manager', $tenant, 'linked-manager-role-filter@example.test', 'Linked Manager Role Filter');

        Employee::create([
            'tenant_id' => $tenant->id,
            'user_id' => $linkedEmployeeUser->id,
            'employee_code' => 'USR-RL-1',
            'name' => 'Linked Employee Role Record',
            'email' => 'linked-employee-role-record@example.test',
            'status' => 'active',
        ]);

        Employee::create([
            'tenant_id' => $tenant->id,
            'user_id' => $linkedManagerUser->id,
            'employee_code' => 'USR-RL-2',
            'name' => 'Linked Manager Role Record',
            'email' => 'linked-manager-role-record@example.test',
            'status' => 'active',
        ]);

        $linkedEmployeesResponse = $this->actingAs($admin)->get(route('users.index', [
            'role' => 'employee',
            'linked' => 'only',
        ]));

        $linkedEmployeesResponse->assertOk();
        $linkedEmployeesResponse->assertSee('Linked only');
        $linkedEmployeesResponse->assertSee('Admin HR');
        $linkedEmployeesResponse->assertSee($linkedEmployeeUser->email);
        $linkedEmployeesResponse->assertDontSee($unlinkedEmployeeUser->email);
        $linkedEmployeesResponse->assertDontSee($linkedManagerUser->email);

        $unlinkedEmployeesResponse = $this->actingAs($admin)->get(route('users.index', [
            'role' => 'employee',
            'linked' => 'unlinked',
        ]));

        $unlinkedEmployeesResponse->assertOk();
        $unlinkedEmployeesResponse->assertSee('Unlinked only');
        $unlinkedEmployeesResponse->assertSee($unlinkedEmployeeUser->email);
        $unlinkedEmployeesResponse->assertDontSee($linkedEmployeeUser->email);
        $unlinkedEmployeesResponse->assertDontSee($linkedManagerUser->email);
    }

    public function test_manager_role_and_link_filter_stays_tenant_scoped(): void
    {
        $tenantA = $this->makeTenant('user-role-linked-filter-tenant-a');
        $tenantB = $this->makeTenant('user-role-linked-filter-tenant-b');
        $manager = $this->makeUser('manager', $tenantA, 'manager-user-role-linked@example.test', 'Manager User Role Linked');
        $linkedEmployeeUserA = $this->makeUser('employee', $tenantA, 'tenant-a-linked-employee-role-filter@example.test', 'Tenant A Linked Employee Role');
        $unlinkedEmployeeUserA = $this->makeUser('employee', $tenantA, 'tenant-a-standalone-employee-role-filter@example.test', 'Tenant A Unlinked Employee Role');
        $linkedEmployeeUserB = $this->makeUser('employee', $tenantB, 'tenant-b-linked-employee-role-filter@example.test', 'Tenant B Linked Employee Role');

        Employee::create([
            'tenant_id' => $tenantA->id,
            'user_id' => $linkedEmployeeUserA->id,
            'employee_code' => 'USR-RLA-1',
            'name' => 'Tenant A Linked Employee Role Record',
            'email' => 'tenant-a-linked-employee-role-record@example.test',
            'status' => 'active',
        ]);

        Employee::create([
            'tenant_id' => $tenantB->id,
            'user_id' => $linkedEmployeeUserB->id,
            'employee_code' => 'USR-RLB-1',
            'name' => 'Tenant B Linked Employee Role Record',
            'email' => 'tenant-b-linked-employee-role-record@example.test',
            'status' => 'active',
        ]);

        $linkedResponse = $this->actingAs($manager)->get(route('users.index', [
            'tenant_id' => $tenantB->id,
            'role' => 'employee',
            'linked' => 'only',
        ]));

        $linkedResponse->assertOk();
        $linkedResponse->assertSee($linkedEmployeeUserA->email);
        $linkedResponse->assertDontSee($unlinkedEmployeeUserA->email);
        $linkedResponse->assertDontSee($linkedEmployeeUserB->email);

        $unlinkedResponse = $this->actingAs($manager)->get(route('users.index', [
            'tenant_id' => $tenantB->id,
            'role' => 'employee',
            'linked' => 'unlinked',
        ]));

        $unlinkedResponse->assertOk();
        $unlinkedResponse->assertSee($unlinkedEmployeeUserA->email);
        $unlinkedResponse->assertDontSee($linkedEmployeeUserA->email);
        $unlinkedResponse->assertDontSee($linkedEmployeeUserB->email);
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
}