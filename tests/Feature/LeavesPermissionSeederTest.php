<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeavesPermissionSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesTableSeeder::class);
    }

    public function test_admin_hr_has_leaves_permissions(): void
    {
        $adminHr = Role::where('name', 'Admin HR')->firstOrFail();

        $permissions = RolePermission::where('role_id', $adminHr->id)
            ->pluck('menu_key')
            ->toArray();

        $this->assertContains('leaves', $permissions);
        $this->assertContains('leaves.manage', $permissions);
    }

    public function test_admin_hr_can_access_leaves_analytics_route(): void
    {
        $user = User::factory()->create([
            'role_id' => Role::where('name', 'Admin HR')->firstOrFail()->id,
            'role' => 'admin_hr',
        ]);

        $this->actingAs($user)
            ->get('/leaves/analytics')
            ->assertOk();
    }
}