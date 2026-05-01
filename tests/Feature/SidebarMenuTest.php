<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SidebarMenuTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesTableSeeder::class);
    }

    public function test_admin_sees_indonesian_sidebar_labels_icons_and_tooltips(): void
    {
        $tenant = $this->makeTenant('sidebar-admin');
        $admin = $this->makeUser('admin_hr', $tenant, 'admin-sidebar@example.test', 'Admin Sidebar');

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Data Master');
        $response->assertSee('Operasional Absensi');
        $response->assertSee('Operasional Lembur');
        $response->assertSee('Operasional Payroll');
        $response->assertSee('Profil Saya');
        $response->assertSee('Pengguna');
        $response->assertSee('Karyawan');
        $response->assertSee('Departemen');
        $response->assertSee('Posisi');
        $response->assertSee('Lokasi Kerja');
        $response->assertSee('Tenant');
        $response->assertSee('Role');
        $response->assertSee('Absensi Karyawan');
        $response->assertSee('Cuti / Izin');
        $response->assertSee('Jenis Cuti');
        $response->assertSee('Payroll');
        $response->assertSee('Potongan');
        $response->assertSee('Laporan Payroll');
        $response->assertDontSee('Laravel Examples');
        $response->assertDontSee('User Management');
        $response->assertSee('fa-user-shield', false);
        $response->assertSee('fa-users', false);
        $response->assertSee('fa-building', false);
        $response->assertSee('fa-briefcase', false);
        $response->assertSee('fa-map-marker-alt', false);
        $response->assertSee('fa-layer-group', false);
        $response->assertSee('fa-id-badge', false);
        $response->assertSee('fa-calendar-check', false);
        $response->assertSee('fa-plane-departure', false);
        $response->assertSee('fa-money-bill-wave', false);
        $response->assertSee('fa-chart-line', false);
        $response->assertSee('Kelola data master pengguna');
        $response->assertSee('Kelola data karyawan');
        $response->assertSee('Kelola data departemen');
        $response->assertSee('Kelola payroll');
        $response->assertSee('Kelola master potongan payroll');
        $response->assertSee('Lihat laporan payroll');
    }

    public function test_manager_does_not_see_admin_only_sidebar_menus(): void
    {
        $tenant = $this->makeTenant('sidebar-manager');
        $manager = $this->makeUser('manager', $tenant, 'manager-sidebar@example.test', 'Manager Sidebar');

        $response = $this->actingAs($manager)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Data Master');
        $response->assertSee('Pengguna');
        $response->assertSee('Karyawan');
        $response->assertSee('Lokasi Kerja');
        $response->assertSee('Absensi Karyawan');
        $response->assertSee('Cuti / Izin');
        $response->assertDontSee('Departemen');
        $response->assertDontSee('Posisi');
        $response->assertDontSee('Tenant');
        $response->assertDontSee('Role');
        $response->assertDontSee('Payroll');
        $response->assertDontSee('Laporan Payroll');
    }

    public function test_employee_only_sees_profile_menu_when_role_is_profile_only(): void
    {
        $tenant = $this->makeTenant('sidebar-employee');
        $employee = $this->makeUser('employee', $tenant, 'employee-sidebar@example.test', 'Employee Sidebar');

        $response = $this->actingAs($employee)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Profil Saya');
        $response->assertSee('data-testid="sidebar-menu-profile"', false);
        $response->assertDontSee('data-testid="sidebar-menu-attendances"', false);
        $response->assertDontSee('data-testid="sidebar-menu-leaves"', false);
        $response->assertDontSee('data-testid="sidebar-menu-users"', false);
        $response->assertDontSee('data-testid="sidebar-menu-employees"', false);
        $response->assertDontSee('data-testid="sidebar-menu-departments"', false);
        $response->assertDontSee('data-testid="sidebar-menu-positions"', false);
        $response->assertDontSee('data-testid="sidebar-menu-work-locations"', false);
        $response->assertDontSee('data-testid="sidebar-menu-tenants"', false);
        $response->assertDontSee('data-testid="sidebar-menu-roles"', false);
        $response->assertDontSee('data-testid="sidebar-menu-payroll"', false);
    }

    protected function makeTenant(string $slug): Tenant
    {
        return Tenant::create([
            'name' => ucfirst(str_replace('-', ' ', $slug)).' Tenant',
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
            'password' => 'password123',
            'role' => $role,
            'status' => 'active',
        ]);
    }
}