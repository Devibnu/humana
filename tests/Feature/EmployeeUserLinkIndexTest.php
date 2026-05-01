<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeUserLinkIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_hr_sees_linked_user_column_values(): void
    {
        $tenant = $this->makeTenant('employee-link-index-admin');
        $admin = $this->makeUser('admin_hr', $tenant, 'admin-employee-link-index@example.test', 'Admin Employee Link Index');
        $linkedUser = $this->makeUser('employee', $tenant, 'linked-index-user@example.test', 'Linked Index User');

        Employee::create([
            'tenant_id' => $tenant->id,
            'user_id' => $linkedUser->id,
            'employee_code' => 'EMP-IDX-1',
            'name' => 'Linked Employee Index',
            'email' => 'linked-employee-index@example.test',
            'status' => 'active',
        ]);

        Employee::create([
            'tenant_id' => $tenant->id,
            'employee_code' => 'EMP-IDX-2',
            'name' => 'Unlinked Employee Index',
            'email' => 'unlinked-employee-index@example.test',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->get(route('employees.index'));

        $response->assertOk();
        $response->assertSee('Linked User');
        $response->assertSee('linked-index-user@example.test');
        $response->assertSee('Not linked');
    }

    public function test_manager_sees_tenant_scoped_employees_with_valid_linked_user_column(): void
    {
        $tenantA = $this->makeTenant('employee-link-index-tenant-a');
        $tenantB = $this->makeTenant('employee-link-index-tenant-b');
        $manager = $this->makeUser('manager', $tenantA, 'manager-employee-link-index@example.test', 'Manager Employee Link Index');
        $linkedUserA = $this->makeUser('employee', $tenantA, 'tenant-a-linked-index@example.test', 'Tenant A Linked');
        $linkedUserB = $this->makeUser('employee', $tenantB, 'tenant-b-linked-index@example.test', 'Tenant B Linked');

        Employee::create([
            'tenant_id' => $tenantA->id,
            'user_id' => $linkedUserA->id,
            'employee_code' => 'EMP-TA-1',
            'name' => 'Tenant A Employee Index',
            'email' => 'tenant-a-employee-index@example.test',
            'status' => 'active',
        ]);

        Employee::create([
            'tenant_id' => $tenantB->id,
            'user_id' => $linkedUserB->id,
            'employee_code' => 'EMP-TB-1',
            'name' => 'Tenant B Employee Index',
            'email' => 'tenant-b-employee-index@example.test',
            'status' => 'active',
        ]);

        $response = $this->actingAs($manager)->get(route('employees.index', ['tenant_id' => $tenantB->id]));

        $response->assertOk();
        $response->assertSee('Linked User');
        $response->assertSee('Tenant A Employee Index');
        $response->assertSee('tenant-a-linked-index@example.test');
        $response->assertDontSee('Tenant B Employee Index');
        $response->assertDontSee('tenant-b-linked-index@example.test');
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