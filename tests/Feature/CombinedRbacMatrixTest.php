<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CombinedRbacMatrixTest extends TestCase
{
    use RefreshDatabase;

    protected Role $adminHr;
    protected Role $manager;
    protected Role $employeeRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesTableSeeder::class);

        $this->adminHr = Role::where('name', 'Admin HR')->firstOrFail();
        $this->manager = Role::where('name', 'Manager')->firstOrFail();
        $this->employeeRole = Role::where('name', 'Employee')->firstOrFail();
    }

    public function test_admin_hr_full_access(): void
    {
        $permissions = RolePermission::where('role_id', $this->adminHr->id)
            ->pluck('menu_key')
            ->toArray();

        foreach (['profile', 'users', 'employees', 'departments', 'positions', 'work_locations', 'tenants', 'roles', 'attendances', 'leaves', 'leaves.manage', 'payroll', 'payroll.manage'] as $key) {
            $this->assertContains($key, $permissions);
        }

        $user = User::factory()->create([
            'role_id' => $this->adminHr->id,
            'role' => 'admin_hr',
        ]);

        $this->actingAs($user)->get('/payroll')->assertOk();
        $this->actingAs($user)->get('/leaves/analytics')->assertOk();
        $dashboardResponse = $this->actingAs($user)->get('/dashboard');
        $dashboardResponse->assertSee('Payroll', false);
    }

    public function test_manager_limited_access(): void
    {
        $permissions = RolePermission::where('role_id', $this->manager->id)
            ->pluck('menu_key')
            ->toArray();

        $this->assertContains('profile', $permissions);
        $this->assertContains('attendances', $permissions);
        $this->assertContains('leaves', $permissions);
        $this->assertNotContains('payroll.manage', $permissions);
        $this->assertNotContains('employees.destroy', $permissions);

        $user = User::factory()->create([
            'role_id' => $this->manager->id,
            'role' => 'manager',
        ]);

        $this->actingAs($user)->get('/payroll')->assertForbidden();
        $dashboardResponse = $this->actingAs($user)->get('/dashboard');
        $dashboardResponse->assertSee('Absensi', false);
        $dashboardResponse->assertSee('Cuti / Izin', false);
        $dashboardResponse->assertDontSee('Payroll', false);
    }

    public function test_employee_self_service_access(): void
    {
        $permissions = RolePermission::where('role_id', $this->employeeRole->id)
            ->pluck('menu_key')
            ->toArray();

        $this->assertEqualsCanonicalizing([
            'profile',
            'attendances',
            'leaves',
            'leaves.create',
            'lembur',
            'lembur.submit',
        ], $permissions);
        $this->assertContains('profile', $permissions);
        $this->assertContains('attendances', $permissions);
        $this->assertContains('leaves', $permissions);
        $this->assertNotContains('payroll', $permissions);
        $this->assertNotContains('leaves.manage', $permissions);

        $user = User::factory()->create([
            'role_id' => $this->employeeRole->id,
            'role' => 'employee',
        ]);

        Employee::query()->create([
            'tenant_id' => $user->tenant_id,
            'user_id' => $user->id,
            'employee_code' => 'RBAC-EMP-001',
            'name' => $user->name,
            'email' => $user->email,
            'status' => 'active',
        ]);

        $this->actingAs($user)->get('/profile')->assertOk();
        $this->actingAs($user)->get('/attendances')->assertOk();
        $this->actingAs($user)->get('/leaves')->assertOk();
        $this->actingAs($user)->get('/payroll')->assertForbidden();
        $this->actingAs($user)->get('/leaves/analytics')->assertForbidden();

        $dashboardResponse = $this->actingAs($user)->get('/dashboard');
        $dashboardResponse->assertSee('Profil Saya', false);
        $dashboardResponse->assertSee('Absensi', false);
        $dashboardResponse->assertSee('Cuti / Izin', false);
        $dashboardResponse->assertSee('Pengajuan Lembur', false);
        $dashboardResponse->assertDontSee('Payroll', false);
        $dashboardResponse->assertDontSee('Analytics Cuti', false);
    }
}
