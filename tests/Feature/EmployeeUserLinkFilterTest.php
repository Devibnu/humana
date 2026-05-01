<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeUserLinkFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_hr_can_filter_employees_by_link_status(): void
    {
        $tenant = $this->makeTenant('employee-link-filter-admin');
        $admin = $this->makeUser('admin_hr', $tenant, 'admin-employee-link-filter@example.test', 'Admin Employee Link Filter');
        $linkedUser = $this->makeUser('employee', $tenant, 'employee-link-filter-linked@example.test', 'Employee Link Filter Linked');

        Employee::create([
            'tenant_id' => $tenant->id,
            'user_id' => $linkedUser->id,
            'employee_code' => 'EMP-LF-1',
            'name' => 'Linked Employee Filter',
            'email' => 'linked-employee-filter@example.test',
            'status' => 'active',
        ]);

        Employee::create([
            'tenant_id' => $tenant->id,
            'employee_code' => 'EMP-LF-2',
            'name' => 'Unlinked Employee Filter',
            'email' => 'unlinked-employee-filter@example.test',
            'status' => 'active',
        ]);

        $linkedResponse = $this->actingAs($admin)->get(route('employees.index', ['linked' => 'only']));

        $linkedResponse->assertOk();
        $linkedResponse->assertSee('Linked only');
        $linkedResponse->assertSee('Linked Employee Filter');
        $linkedResponse->assertDontSee('Unlinked Employee Filter');

        $unlinkedResponse = $this->actingAs($admin)->get(route('employees.index', ['linked' => 'unlinked']));

        $unlinkedResponse->assertOk();
        $unlinkedResponse->assertSee('Unlinked only');
        $unlinkedResponse->assertSee('Unlinked Employee Filter');
        $unlinkedResponse->assertDontSee('Linked Employee Filter');
    }

    public function test_manager_link_filter_stays_tenant_scoped(): void
    {
        $tenantA = $this->makeTenant('employee-link-filter-tenant-a');
        $tenantB = $this->makeTenant('employee-link-filter-tenant-b');
        $manager = $this->makeUser('manager', $tenantA, 'manager-employee-link-filter@example.test', 'Manager Employee Link Filter');
        $linkedUserA = $this->makeUser('employee', $tenantA, 'employee-link-filter-a@example.test', 'Employee Link Filter A');
        $linkedUserB = $this->makeUser('employee', $tenantB, 'employee-link-filter-b@example.test', 'Employee Link Filter B');

        Employee::create([
            'tenant_id' => $tenantA->id,
            'user_id' => $linkedUserA->id,
            'employee_code' => 'EMP-LFA-1',
            'name' => 'Tenant A Linked Employee Filter',
            'email' => 'tenant-a-linked-employee-filter@example.test',
            'status' => 'active',
        ]);

        Employee::create([
            'tenant_id' => $tenantA->id,
            'employee_code' => 'EMP-LFA-2',
            'name' => 'Tenant A Unlinked Employee Filter',
            'email' => 'tenant-a-unlinked-employee-filter@example.test',
            'status' => 'active',
        ]);

        Employee::create([
            'tenant_id' => $tenantB->id,
            'user_id' => $linkedUserB->id,
            'employee_code' => 'EMP-LFB-1',
            'name' => 'Tenant B Linked Employee Filter',
            'email' => 'tenant-b-linked-employee-filter@example.test',
            'status' => 'active',
        ]);

        $linkedResponse = $this->actingAs($manager)->get(route('employees.index', ['tenant_id' => $tenantB->id, 'linked' => 'only']));

        $linkedResponse->assertOk();
        $linkedResponse->assertSee('Tenant A Linked Employee Filter');
        $linkedResponse->assertDontSee('Tenant A Unlinked Employee Filter');
        $linkedResponse->assertDontSee('Tenant B Linked Employee Filter');

        $unlinkedResponse = $this->actingAs($manager)->get(route('employees.index', ['tenant_id' => $tenantB->id, 'linked' => 'unlinked']));

        $unlinkedResponse->assertOk();
        $unlinkedResponse->assertSee('Tenant A Unlinked Employee Filter');
        $unlinkedResponse->assertDontSee('Tenant A Linked Employee Filter');
        $unlinkedResponse->assertDontSee('Tenant B Linked Employee Filter');
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