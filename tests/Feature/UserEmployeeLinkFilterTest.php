<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserEmployeeLinkFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_hr_can_filter_users_by_link_status(): void
    {
        $tenant = $this->makeTenant('user-link-filter-admin');
        $admin = $this->makeUser('admin_hr', $tenant, 'admin-user-link-filter@example.test', 'Admin User Link Filter');
        $linkedUser = $this->makeUser('employee', $tenant, 'user-link-filter-linked@example.test', 'User Link Filter Linked');
        $unlinkedUser = $this->makeUser('employee', $tenant, 'user-link-filter-unlinked@example.test', 'User Link Filter Unlinked');

        Employee::create([
            'tenant_id' => $tenant->id,
            'user_id' => $linkedUser->id,
            'employee_code' => 'USR-LF-1',
            'name' => 'Linked Employee User Filter',
            'email' => 'linked-employee-user-filter@example.test',
            'status' => 'active',
        ]);

        $linkedResponse = $this->actingAs($admin)->get(route('users.index', ['linked' => 'only']));

        $linkedResponse->assertOk();
        $linkedResponse->assertSee('Linked only');
        $linkedResponse->assertSee($linkedUser->email);
        $linkedResponse->assertDontSee($unlinkedUser->email);

        $unlinkedResponse = $this->actingAs($admin)->get(route('users.index', ['linked' => 'unlinked']));

        $unlinkedResponse->assertOk();
        $unlinkedResponse->assertSee('Unlinked only');
        $unlinkedResponse->assertSee($unlinkedUser->email);
        $unlinkedResponse->assertDontSee($linkedUser->email);
    }

    public function test_manager_link_filter_stays_tenant_scoped(): void
    {
        $tenantA = $this->makeTenant('user-link-filter-tenant-a');
        $tenantB = $this->makeTenant('user-link-filter-tenant-b');
        $manager = $this->makeUser('manager', $tenantA, 'manager-user-link-filter@example.test', 'Manager User Link Filter');
        $linkedUserA = $this->makeUser('employee', $tenantA, 'user-link-filter-a@example.test', 'User Link Filter A');
        $unlinkedUserA = $this->makeUser('employee', $tenantA, 'user-link-filter-a-unlinked@example.test', 'User Link Filter A Unlinked');
        $linkedUserB = $this->makeUser('employee', $tenantB, 'user-link-filter-b@example.test', 'User Link Filter B');

        Employee::create([
            'tenant_id' => $tenantA->id,
            'user_id' => $linkedUserA->id,
            'employee_code' => 'USR-LFA-1',
            'name' => 'Tenant A Linked User Filter',
            'email' => 'tenant-a-linked-user-filter@example.test',
            'status' => 'active',
        ]);

        Employee::create([
            'tenant_id' => $tenantB->id,
            'user_id' => $linkedUserB->id,
            'employee_code' => 'USR-LFB-1',
            'name' => 'Tenant B Linked User Filter',
            'email' => 'tenant-b-linked-user-filter@example.test',
            'status' => 'active',
        ]);

        $linkedResponse = $this->actingAs($manager)->get(route('users.index', ['tenant_id' => $tenantB->id, 'linked' => 'only']));

        $linkedResponse->assertOk();
        $linkedResponse->assertSee($linkedUserA->email);
        $linkedResponse->assertDontSee($unlinkedUserA->email);
        $linkedResponse->assertDontSee($linkedUserB->email);

        $unlinkedResponse = $this->actingAs($manager)->get(route('users.index', ['tenant_id' => $tenantB->id, 'linked' => 'unlinked']));

        $unlinkedResponse->assertOk();
        $unlinkedResponse->assertSee($unlinkedUserA->email);
        $unlinkedResponse->assertDontSee($linkedUserA->email);
        $unlinkedResponse->assertDontSee($linkedUserB->email);
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