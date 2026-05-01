<?php

namespace Tests\Feature;

use App\Models\EmployeeLevel;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeLevelsMasterTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_gets_default_employee_levels_and_admin_can_open_master(): void
    {
        $this->seed(RolesTableSeeder::class);
        $tenant = $this->makeTenant();
        $admin = $this->makeUser($tenant, 'Admin HR');

        $this->assertDatabaseHas('employee_levels', [
            'tenant_id' => $tenant->id,
            'code' => 'staff',
            'name' => 'Staff',
        ]);

        $response = $this->actingAs($admin)->get(route('employee-levels.index'));

        $response->assertOk();
        $response->assertSee('Level Karyawan');
        $response->assertSee('Staff');
        $response->assertSee('Supervisor');
        $response->assertSee('Manager');
    }

    public function test_employee_form_uses_employee_level_master_options(): void
    {
        $this->seed(RolesTableSeeder::class);
        $tenant = $this->makeTenant();
        $admin = $this->makeUser($tenant, 'Admin HR');

        EmployeeLevel::create([
            'tenant_id' => $tenant->id,
            'code' => 'lead',
            'name' => 'Team Lead',
            'status' => 'active',
            'sort_order' => 15,
        ]);

        $response = $this->actingAs($admin)->get(route('employees.create'));

        $response->assertOk();
        $response->assertSee('Level Karyawan');
        $response->assertSee('Team Lead');
        $response->assertSee('value="lead"', false);
    }

    protected function makeTenant(): Tenant
    {
        return Tenant::create([
            'name' => 'Employee Level Tenant',
            'slug' => 'employee-level-tenant',
            'domain' => 'employee-level-tenant.test',
            'status' => 'active',
        ]);
    }

    protected function makeUser(Tenant $tenant, string $roleName): User
    {
        $role = Role::where('name', $roleName)->firstOrFail();

        return User::factory()->create([
            'tenant_id' => $tenant->id,
            'role_id' => $role->id,
            'role' => $role->system_key,
        ]);
    }
}
