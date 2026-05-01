<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeavesAnalyticsSidebarTest extends TestCase
{
    use RefreshDatabase;

    public function test_menu_analytics_cuti_muncul_di_sidebar_untuk_admin_dan_manager(): void
    {
        $tenant = $this->makeTenant('leave-analytics-sidebar-admin-manager');
        $admin = $this->makeUser('admin_hr', $tenant, 'leave-analytics-sidebar-admin@example.test', 'Leave Analytics Sidebar Admin');
        $manager = $this->makeUser('manager', $tenant, 'leave-analytics-sidebar-manager@example.test', 'Leave Analytics Sidebar Manager');

        $adminResponse = $this->actingAs($admin)->get(route('dashboard'));

        $adminResponse->assertOk();
        $adminResponse->assertSee('data-testid="sidebar-menu-leave-analytics"', false);
        $adminResponse->assertSee('Analytics Cuti');
        $adminResponse->assertSee('Buka analitik cuti lintas tenant atau tenant aktif');
        $adminResponse->assertSee(route('leaves.analytics'), false);

        auth()->logout();

        $managerResponse = $this->actingAs($manager)->get(route('dashboard'));

        $managerResponse->assertOk();
        $managerResponse->assertSee('data-testid="sidebar-menu-leave-analytics"', false);
        $managerResponse->assertSee('Analytics Cuti');
        $managerResponse->assertSee(route('leaves.analytics'), false);
    }

    public function test_employee_tidak_melihat_menu_analytics_cuti_di_sidebar(): void
    {
        $tenant = $this->makeTenant('leave-analytics-sidebar-employee');
        $employee = $this->makeUser('employee', $tenant, 'leave-analytics-sidebar-employee@example.test', 'Leave Analytics Sidebar Employee');

        $response = $this->actingAs($employee)->get(route('dashboard'));

        $response->assertOk();
        $response->assertDontSee('data-testid="sidebar-menu-leave-analytics"', false);
        $response->assertDontSee('Analytics Cuti');
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