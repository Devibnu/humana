<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeUiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesTableSeeder::class);
    }

    public function test_employee_does_not_see_management_menu_or_admin_actions_on_dashboard_and_profile(): void
    {
        $tenant = Tenant::create([
            'name' => 'Employee Tenant',
            'slug' => 'employee-tenant',
            'domain' => 'employee-tenant.test',
            'status' => 'active',
        ]);

        $employee = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Employee UI',
            'email' => 'employee-ui@example.test',
            'password' => 'password',
            'role' => 'employee',
            'status' => 'active',
        ]);

        $dashboardResponse = $this->actingAs($employee)->get('/dashboard');

        $dashboardResponse->assertOk();
        $dashboardResponse->assertSee('Profil Saya');
        $dashboardResponse->assertDontSee('Absensi');
        $dashboardResponse->assertDontSee('Cuti / Izin');
        $dashboardResponse->assertDontSee('Data Master');
        $dashboardResponse->assertDontSee('Pengguna');
        $dashboardResponse->assertDontSee('Tenant');
        $dashboardResponse->assertDontSee(route('users.index'), false);
        $dashboardResponse->assertDontSee(route('tenants.index'), false);
        $dashboardResponse->assertDontSee(route('users.create'), false);

        $profileResponse = $this->actingAs($employee)->get('/user-profile');

        $profileResponse->assertOk();
        $profileResponse->assertSee('Profil Saya');
        $profileResponse->assertDontSee('Absensi');
        $profileResponse->assertDontSee('Cuti / Izin');
        $profileResponse->assertDontSee('Data Master');
        $profileResponse->assertDontSee('Pengguna');
        $profileResponse->assertDontSee('Tenant');
        $profileResponse->assertDontSee(route('users.index'), false);
        $profileResponse->assertDontSee(route('tenants.index'), false);
        $profileResponse->assertDontSee(route('users.create'), false);
    }
}