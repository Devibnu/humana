<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Leave;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveOverlapFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_overlap_filter_includes_leaves_that_intersect_the_period(): void
    {
        $tenant = $this->makeTenant('leave-overlap-tenant');
        $admin = $this->makeUser('admin_hr', $tenant, 'leave-overlap-admin@example.test', 'Leave Overlap Admin');
        $employee = $this->makeEmployee($tenant, 'LVE-OVR-1', 'Overlap Employee', 'leave-overlap-employee@example.test');

        $this->makeLeave($tenant, $employee, '2026-04-08', '2026-04-11', 'Starts before, ends inside');
        $this->makeLeave($tenant, $employee, '2026-04-12', '2026-04-14', 'Fully inside');
        $this->makeLeave($tenant, $employee, '2026-04-15', '2026-04-18', 'Starts inside, ends after');
        $this->makeLeave($tenant, $employee, '2026-04-19', '2026-04-20', 'Outside after');

        $response = $this->actingAs($admin)->get(route('leaves.index', [
            'tenant_id' => $tenant->id,
            'start_date' => '2026-04-11',
            'end_date' => '2026-04-15',
        ]));

        $response->assertOk();
        $response->assertSee('Starts before, ends inside');
        $response->assertSee('Fully inside');
        $response->assertSee('Starts inside, ends after');
        $response->assertDontSee('Outside after');
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