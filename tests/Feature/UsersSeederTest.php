<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class UsersSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('db:seed', [
            '--class' => 'Database\\Seeders\\RolesTableSeeder',
            '--force' => true,
        ]);

        Artisan::call('db:seed', [
            '--class' => 'Database\\Seeders\\UsersTableSeeder',
            '--force' => true,
        ]);
    }

    public function test_admin_hr_has_correct_role_id(): void
    {
        $expectedRoleId = Role::where('name', 'Admin HR')->value('id');

        $this->assertNotNull($expectedRoleId, 'Role "Admin HR" must exist in roles table');

        $this->assertDatabaseHas('users', [
            'email'   => 'admin@humana.test',
            'role_id' => $expectedRoleId,
        ]);
    }

    public function test_manager_has_correct_role_id(): void
    {
        $expectedRoleId = Role::where('name', 'Manager')->value('id');

        $this->assertNotNull($expectedRoleId, 'Role "Manager" must exist in roles table');

        $this->assertDatabaseHas('users', [
            'email'   => 'manager@humana.test',
            'role_id' => $expectedRoleId,
        ]);
    }

    public function test_employee_has_correct_role_id(): void
    {
        $expectedRoleId = Role::where('name', 'Employee')->value('id');

        $this->assertNotNull($expectedRoleId, 'Role "Employee" must exist in roles table');

        $this->assertDatabaseHas('users', [
            'email'   => 'employee@humana.test',
            'role_id' => $expectedRoleId,
        ]);
    }

    public function test_admin_hr_can_login_with_baseline_password(): void
    {
        $this->assertTrue(Auth::attempt([
            'email'    => 'admin@humana.test',
            'password' => 'password',
        ]));
    }

    public function test_manager_can_login_with_baseline_password(): void
    {
        $this->assertTrue(Auth::attempt([
            'email'    => 'manager@humana.test',
            'password' => 'password',
        ]));
    }

    public function test_employee_can_login_with_baseline_password(): void
    {
        $this->assertTrue(Auth::attempt([
            'email'    => 'employee@humana.test',
            'password' => 'password',
        ]));
    }

    public function test_role_key_resolves_from_role_id_not_legacy_string(): void
    {
        $adminRoleId = Role::where('name', 'Admin HR')->value('id');

        /** @var User $user */
        $user = User::where('email', 'admin@humana.test')->firstOrFail();

        $this->assertEquals($adminRoleId, $user->role_id);
        $this->assertEquals('admin_hr', $user->roleKey());
        $this->assertEquals('Admin HR', $user->roleName());
        $this->assertTrue($user->isAdminHr());
    }
}
