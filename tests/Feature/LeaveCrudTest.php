<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Leave;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_hr_has_full_leave_crud(): void
    {
        $tenantA = $this->makeTenant('leave-crud-tenant-a');
        $tenantB = $this->makeTenant('leave-crud-tenant-b');
        $admin = $this->makeUser('admin_hr', $tenantA, 'leave-crud-admin@example.test', 'Leave Crud Admin');
        $employeeA = $this->makeEmployee($tenantA, 'LVE-CRUD-A1', 'Leave Crud Employee A', 'leave-crud-employee-a@example.test');
        $employeeB = $this->makeEmployee($tenantB, 'LVE-CRUD-B1', 'Leave Crud Employee B', 'leave-crud-employee-b@example.test');

        $this->actingAs($admin)->get(route('leaves.index'))->assertOk();
        $this->actingAs($admin)->get(route('leaves.create'))->assertOk();

        $this->actingAs($admin)->post(route('leaves.store'), [
            'tenant_id' => $tenantA->id,
            'employee_id' => $employeeA->id,
            'leave_type' => 'annual',
            'start_date' => '2026-04-20',
            'end_date' => '2026-04-22',
            'reason' => 'Annual break',
            'status' => 'approved',
        ])->assertRedirect(route('leaves.index'));

        $leave = Leave::where('employee_id', $employeeA->id)->firstOrFail();

        $this->assertDatabaseHas('leaves', [
            'id' => $leave->id,
            'tenant_id' => $tenantA->id,
            'employee_id' => $employeeA->id,
            'leave_type' => 'annual',
            'status' => 'pending',
        ]);

        $this->actingAs($admin)->get(route('leaves.edit', ['leaf' => $leave->id]))->assertOk();

        $this->actingAs($admin)->put(route('leaves.update', ['leaf' => $leave->id]), [
            'tenant_id' => $tenantB->id,
            'employee_id' => $employeeB->id,
            'leave_type' => 'sick',
            'start_date' => '2026-04-25',
            'end_date' => '2026-04-26',
            'reason' => 'Medical leave',
            'status' => 'approved',
        ])->assertRedirect(route('leaves.index'));

        $this->assertDatabaseHas('leaves', [
            'id' => $leave->id,
            'tenant_id' => $tenantB->id,
            'employee_id' => $employeeB->id,
            'leave_type' => 'sick',
            'status' => 'approved',
        ]);

        $this->actingAs($admin)->delete(route('leaves.destroy', ['leaf' => $leave->id]))->assertRedirect(route('leaves.index'));

        $this->assertDatabaseMissing('leaves', ['id' => $leave->id]);
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
}