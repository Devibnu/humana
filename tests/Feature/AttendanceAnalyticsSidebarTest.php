<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceAnalyticsSidebarTest extends TestCase
{
    use RefreshDatabase;

    public function test_menu_analytics_absensi_muncul_di_sidebar_untuk_admin(): void
    {
        $tenant = $this->makeTenant('attendance-analytics-sidebar-admin');
        $admin = $this->makeUser('admin_hr', $tenant, 'attendance-analytics-sidebar-admin@example.test', 'Attendance Analytics Sidebar Admin');

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('data-testid="sidebar-menu-attendance-analytics"', false);
        $response->assertSee('Analytics Absensi');
        $response->assertSee('Buka analitik absensi bulanan dan tahunan');
        $response->assertSee(route('attendances.analytics'), false);
    }

    public function test_employee_tidak_melihat_menu_analytics_absensi_di_sidebar(): void
    {
        $tenant = $this->makeTenant('attendance-analytics-sidebar-employee');
        $employee = $this->makeUser('employee', $tenant, 'attendance-analytics-sidebar-employee@example.test', 'Attendance Analytics Sidebar Employee');

        $response = $this->actingAs($employee)->get(route('dashboard'));

        $response->assertOk();
        $response->assertDontSee('data-testid="sidebar-menu-attendance-analytics"', false);
        $response->assertDontSee('Analytics Absensi');
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