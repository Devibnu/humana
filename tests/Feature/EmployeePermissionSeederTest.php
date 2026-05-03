<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeePermissionSeederTest extends TestCase
{
    use RefreshDatabase;

    protected Role $employeeRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesTableSeeder::class);
        $this->employeeRole = Role::where('name', 'Employee')->firstOrFail();
    }

    public function test_employee_has_limited_permissions(): void
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
            'payroll.slips',
        ], $permissions);
        $this->assertContains('profile', $permissions);
        $this->assertContains('attendances', $permissions);
        $this->assertContains('leaves', $permissions);
        $this->assertContains('leaves.create', $permissions);
        $this->assertContains('lembur.submit', $permissions);
        $this->assertContains('payroll.slips', $permissions);
        $this->assertNotContains('payroll', $permissions);
        $this->assertNotContains('payroll.manage', $permissions);
        $this->assertNotContains('leaves.manage', $permissions);
        $this->assertNotContains('leaves.analytics', $permissions);
    }

    public function test_employee_can_access_own_profile(): void
    {
        $user = User::factory()->create([
            'role_id' => $this->employeeRole->id,
            'role' => 'employee',
        ]);

        $response = $this->actingAs($user)->get(route('profile'));

        $response->assertOk();
        $response->assertSee($user->name, false);
    }

    public function test_employee_cannot_access_payroll_route(): void
    {
        $user = User::factory()->create([
            'role_id' => $this->employeeRole->id,
            'role' => 'employee',
        ]);

        $this->actingAs($user)
            ->get(route('payroll.index'))
            ->assertForbidden();
    }

    public function test_employee_cannot_access_leaves_analytics(): void
    {
        $user = User::factory()->create([
            'role_id' => $this->employeeRole->id,
            'role' => 'employee',
        ]);

        $this->actingAs($user)
            ->get(route('leaves.analytics'))
            ->assertForbidden();
    }

    public function test_sidebar_hides_payroll_and_leaves_analytics_for_employee(): void
    {
        $user = User::factory()->create([
            'role_id' => $this->employeeRole->id,
            'role' => 'employee',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Profil Saya', false);
        $response->assertSee('Absensi', false);
        $response->assertSee('Cuti / Izin', false);
        $response->assertSee('Pengajuan Lembur', false);
        $response->assertSee('Slip Gaji Saya', false);
        $response->assertDontSee('data-testid="sidebar-menu-payroll"', false);
        $response->assertDontSee('Analytics Cuti', false);
    }
}
