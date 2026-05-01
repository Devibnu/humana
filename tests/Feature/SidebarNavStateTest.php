<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SidebarNavStateTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Saat berada di /attendances, hanya menu Absensi yang aktif.
     * Menu Analytics Absensi tidak ikut aktif (employee tidak melihat analytics).
     */
    public function test_absensi_menu_active_only_on_attendances_index(): void
    {
        $tenant   = $this->makeTenant('navstate-absensi');
        $employee = $this->makeUser('employee', $tenant, 'navstate-emp@example.test', 'Nav Employee');

        $response = $this->actingAs($employee)->get('/attendances');

        $response->assertOk();
        $response->assertSee('<a class="nav-link active"', false);
        $response->assertDontSee('Analytics Absensi');
    }

    /**
     * Saat berada di /attendances/analytics, menu Analytics Absensi aktif.
     * Menu Absensi (index) TIDAK boleh ikut aktif — inilah yang diperbaiki.
     */
    public function test_analytics_absensi_menu_active_only_on_attendances_analytics(): void
    {
        $tenant  = $this->makeTenant('navstate-analytics');
        $manager = $this->makeUser('manager', $tenant, 'navstate-mgr@example.test', 'Nav Manager');

        $response = $this->actingAs($manager)->get('/attendances/analytics');

        $response->assertOk();
        $response->assertSee('Analytics Absensi');
        // Absensi (index) tidak boleh punya class 'active' — hanya exact match route yang aktif
        $response->assertDontSee('nav-link active" href="' . route('attendances.index') . '"', false);
    }

    /**
     * Saat berada di /leaves, hanya menu Cuti / Izin yang aktif.
     * Analytics Cuti terlihat di sidebar tapi tidak boleh punya class 'active'.
     */
    public function test_cuti_menu_active_only_on_leaves_index(): void
    {
        $tenant  = $this->makeTenant('navstate-cuti');
        $manager = $this->makeUser('manager', $tenant, 'navstate-cuti-mgr@example.test', 'Nav Cuti Manager');

        $response = $this->actingAs($manager)->get('/leaves');

        $response->assertOk();
        $response->assertSee('<a class="nav-link active"', false);
        // Analytics Cuti link tidak boleh aktif saat di halaman leaves index
        $response->assertDontSee('nav-link active" href="' . route('leaves.analytics') . '"', false);
    }

    /**
     * Saat berada di /leaves/analytics, menu Analytics Cuti aktif.
     * Menu Cuti / Izin (index) TIDAK boleh ikut aktif.
     */
    public function test_analytics_cuti_menu_active_only_on_leaves_analytics(): void
    {
        $tenant  = $this->makeTenant('navstate-cuti-analytics');
        $manager = $this->makeUser('manager', $tenant, 'navstate-cuti-mgr@example.test', 'Nav Cuti Manager');

        $response = $this->actingAs($manager)->get('/leaves/analytics');

        $response->assertOk();
        $response->assertSee('Analytics Cuti');
        // Cuti / Izin (index) tidak boleh punya class 'active'
        $response->assertDontSee('nav-link active" href="' . route('leaves.index') . '"', false);
    }

    protected function makeTenant(string $slug): Tenant
    {
        return Tenant::create([
            'name'   => ucfirst(str_replace('-', ' ', $slug)) . ' Tenant',
            'slug'   => $slug,
            'domain' => $slug . '.test',
            'status' => 'active',
        ]);
    }

    protected function makeUser(string $role, Tenant $tenant, string $email, string $name): User
    {
        return User::create([
            'tenant_id' => $tenant->id,
            'name'      => $name,
            'email'     => $email,
            'password'  => 'password123',
            'role'      => $role,
            'status'    => 'active',
        ]);
    }
}
