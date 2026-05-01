<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollPermissionSeederTest extends TestCase
{
    use RefreshDatabase;

    protected Role $adminHr;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesTableSeeder::class);
        $this->adminHr = Role::where('name', 'Admin HR')->firstOrFail();
    }

    public function test_admin_hr_has_payroll_permissions_in_seeder(): void
    {
        $permissions = RolePermission::where('role_id', $this->adminHr->id)
            ->pluck('menu_key')
            ->toArray();

        $this->assertContains('payroll', $permissions);
        $this->assertContains('payroll.manage', $permissions);
    }

    public function test_admin_hr_can_access_payroll_route(): void
    {
        $user = User::factory()->create([
            'role_id' => $this->adminHr->id,
            'role' => 'admin_hr',
        ]);

        $this->actingAs($user)
            ->get(route('payroll.index'))
            ->assertOk();
    }

    public function test_sidebar_shows_payroll_menu_for_admin_hr(): void
    {
        $user = User::factory()->create([
            'role_id' => $this->adminHr->id,
            'role' => 'admin_hr',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Payroll', false);
        $response->assertSee(route('payroll.index'), false);
    }
}