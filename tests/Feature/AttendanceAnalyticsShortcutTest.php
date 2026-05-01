<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WorkLocation;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceAnalyticsShortcutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-04-21 10:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_tombol_shortcut_analytics_tampil_di_index_dan_mengarah_ke_halaman_analytics(): void
    {
        [$admin, $tenant, $employee] = $this->makeContext();

        Attendance::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'date' => '2026-04-21',
            'status' => 'present',
        ]);

        $response = $this->actingAs($admin)->get(route('attendances.index'));

        $response->assertOk();
        $response->assertSee('data-testid="btn-attendance-analytics-shortcut"', false);
        $response->assertSee('Buka analitik absensi bulanan/tahunan');
        $response->assertSee(route('attendances.analytics'), false);

        $analyticsResponse = $this->actingAs($admin)->get(route('attendances.analytics'));
        $analyticsResponse->assertOk();
    }

    public function test_tombol_shortcut_export_analytics_tampil_di_dashboard_dan_mengarah_ke_halaman_analytics(): void
    {
        [$admin, $tenant, $employee] = $this->makeContext();

        Attendance::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'date' => now()->toDateString(),
            'status' => 'present',
        ]);

        $response = $this->actingAs($admin)->get(route('attendances.dashboard'));

        $response->assertOk();
        $response->assertSee('data-testid="attendance-dashboard-shortcut-analytics"', false);
        $response->assertSee('Export Analytics');
        $response->assertSee(route('attendances.analytics'), false);

        $analyticsResponse = $this->actingAs($admin)->get(route('attendances.analytics'));
        $analyticsResponse->assertOk();
    }

    protected function makeContext(): array
    {
        $tenant = Tenant::create([
            'name' => 'Attendance Analytics Shortcut Tenant',
            'slug' => 'attendance-analytics-shortcut-tenant',
            'domain' => 'attendance-analytics-shortcut-tenant.test',
            'status' => 'active',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Attendance Analytics Shortcut Admin',
            'email' => 'attendance-analytics-shortcut-admin@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $workLocation = WorkLocation::create([
            'tenant_id' => $tenant->id,
            'name' => 'Attendance Analytics Shortcut Office',
            'address' => 'Jakarta',
            'latitude' => -6.2000000,
            'longitude' => 106.8166667,
            'radius' => 250,
        ]);

        $employee = Employee::create([
            'tenant_id' => $tenant->id,
            'work_location_id' => $workLocation->id,
            'employee_code' => 'ATS-001',
            'name' => 'Attendance Analytics Shortcut Employee',
            'email' => 'attendance-analytics-shortcut-employee@example.test',
            'status' => 'active',
        ]);

        return [$admin, $tenant, $employee];
    }
}