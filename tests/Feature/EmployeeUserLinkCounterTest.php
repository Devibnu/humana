<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeUserLinkCounterTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_hr_sees_linked_and_unlinked_employee_counters(): void
    {
        $tenant = $this->makeTenant('employee-link-counter-admin');
        $admin = $this->makeUser('admin_hr', $tenant, 'admin-employee-link-counter@example.test', 'Admin Employee Link Counter');
        $linkedUserOne = $this->makeUser('employee', $tenant, 'employee-link-counter-1@example.test', 'Employee Link Counter One');
        $linkedUserTwo = $this->makeUser('employee', $tenant, 'employee-link-counter-2@example.test', 'Employee Link Counter Two');

        Employee::create([
            'tenant_id' => $tenant->id,
            'user_id' => $linkedUserOne->id,
            'employee_code' => 'EMP-LC-1',
            'name' => 'Linked Counter One',
            'email' => 'linked-counter-one@example.test',
            'status' => 'active',
        ]);

        Employee::create([
            'tenant_id' => $tenant->id,
            'user_id' => $linkedUserTwo->id,
            'employee_code' => 'EMP-LC-2',
            'name' => 'Linked Counter Two',
            'email' => 'linked-counter-two@example.test',
            'status' => 'active',
        ]);

        Employee::create([
            'tenant_id' => $tenant->id,
            'employee_code' => 'EMP-LC-3',
            'name' => 'Unlinked Counter One',
            'email' => 'unlinked-counter-one@example.test',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->get(route('employees.index'));

        $response->assertOk();
        $response->assertSee('Linked: 2');
        $response->assertSee('Unlinked: 1');
    }

    public function test_manager_counters_stay_tenant_scoped(): void
    {
        $tenantA = $this->makeTenant('employee-link-counter-tenant-a');
        $tenantB = $this->makeTenant('employee-link-counter-tenant-b');
        $manager = $this->makeUser('manager', $tenantA, 'manager-employee-link-counter@example.test', 'Manager Employee Link Counter');
        $linkedUserA = $this->makeUser('employee', $tenantA, 'employee-counter-a@example.test', 'Employee Counter A');
        $linkedUserB = $this->makeUser('employee', $tenantB, 'employee-counter-b@example.test', 'Employee Counter B');

        Employee::create([
            'tenant_id' => $tenantA->id,
            'user_id' => $linkedUserA->id,
            'employee_code' => 'EMP-LCTA-1',
            'name' => 'Tenant A Linked Counter',
            'email' => 'tenant-a-linked-counter@example.test',
            'status' => 'active',
        ]);

        Employee::create([
            'tenant_id' => $tenantA->id,
            'employee_code' => 'EMP-LCTA-2',
            'name' => 'Tenant A Unlinked Counter',
            'email' => 'tenant-a-unlinked-counter@example.test',
            'status' => 'active',
        ]);

        Employee::create([
            'tenant_id' => $tenantB->id,
            'user_id' => $linkedUserB->id,
            'employee_code' => 'EMP-LCTB-1',
            'name' => 'Tenant B Linked Counter',
            'email' => 'tenant-b-linked-counter@example.test',
            'status' => 'active',
        ]);

        $response = $this->actingAs($manager)->get(route('employees.index', ['tenant_id' => $tenantB->id]));

        $response->assertOk();
        $response->assertSee('Linked: 1');
        $response->assertSee('Unlinked: 1');
        $response->assertDontSee('Linked: 2');
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