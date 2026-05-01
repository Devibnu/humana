<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Leave;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesTableSeeder::class);
    }

    public function test_employee_cannot_access_leave_request_module_when_role_is_profile_only(): void
    {
        $tenantA = $this->makeTenant('leave-request-tenant-a');
        $employeeUser = $this->makeUser('employee', $tenantA, 'leave-request-employee@example.test', 'Leave Request Employee');
        $employeeA = $this->makeEmployee($tenantA, 'LVE-REQ-A1', 'Leave Request Employee A', 'leave-request-employee-a@example.test', $employeeUser);

        $ownLeave = Leave::create([
            'tenant_id' => $tenantA->id,
            'employee_id' => $employeeA->id,
            'leave_type' => 'annual',
            'start_date' => '2026-04-20',
            'end_date' => '2026-04-21',
            'reason' => 'Own leave',
            'status' => 'pending',
        ]);

        $this->actingAs($employeeUser)->get(route('leaves.index'))->assertForbidden();
        $this->actingAs($employeeUser)->get(route('leaves.create'))->assertForbidden();
        $this->actingAs($employeeUser)->get(route('leaves.edit', ['leaf' => $ownLeave->id]))->assertForbidden();
        $this->actingAs($employeeUser)->get(route('leaves.export'))->assertForbidden();
        $this->actingAs($employeeUser)->post(route('leaves.store'), [
            'tenant_id' => $tenantA->id,
            'employee_id' => $employeeA->id,
            'leave_type' => 'permission',
            'start_date' => '2026-04-25',
            'end_date' => '2026-04-25',
            'reason' => 'Personal errand',
            'status' => 'approved',
        ])->assertForbidden();

        $this->assertDatabaseMissing('leaves', [
            'tenant_id' => $tenantA->id,
            'employee_id' => $employeeA->id,
            'leave_type' => 'permission',
            'reason' => 'Personal errand',
        ]);
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

    protected function makeEmployee(Tenant $tenant, string $code, string $name, string $email, User $user): Employee
    {
        return Employee::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'employee_code' => $code,
            'name' => $name,
            'email' => $email,
            'status' => 'active',
        ]);
    }
}