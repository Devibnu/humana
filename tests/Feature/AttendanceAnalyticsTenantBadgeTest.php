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

class AttendanceAnalyticsTenantBadgeTest extends TestCase
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

    public function test_badge_scope_tenant_tampil_di_halaman_analytics(): void
    {
        [$manager, $tenant] = $this->makeContext();

        $response = $this->actingAs($manager)->get(route('attendances.analytics'));

        $response->assertOk();
        $response->assertSee('data-testid="attendance-analytics-tenant-scope-badge"', false);
        $response->assertSee('data-testid="attendance-analytics-tenant-scope-description"', false);
        $response->assertSee('Tenant: '.$tenant->name);
        $response->assertSee('Data analitik dibatasi ke tenant aktif Anda.');
        $response->assertDontSee('data-testid="attendance-analytics-tenant-filter"', false);
    }

    public function test_label_badge_scope_sesuai_tenant_aktif(): void
    {
        [$manager, $tenant] = $this->makeContext();

        $response = $this->actingAs($manager)->get(route('attendances.analytics', [
            'year' => 2026,
            'month' => 4,
        ]));

        $response->assertOk();
        $this->assertSame($tenant->name, $response->viewData('tenantScopeLabel'));
        $this->assertTrue($response->viewData('isTenantScoped'));
    }

    protected function makeContext(): array
    {
        $tenant = Tenant::create([
            'name' => 'Attendance Analytics Active Tenant',
            'slug' => 'attendance-analytics-active-tenant',
            'domain' => 'attendance-analytics-active-tenant.test',
            'status' => 'active',
        ]);

        $manager = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Attendance Analytics Tenant Manager',
            'email' => 'attendance-analytics-tenant-manager@example.test',
            'password' => 'password',
            'role' => 'manager',
            'status' => 'active',
        ]);

        $workLocation = WorkLocation::create([
            'tenant_id' => $tenant->id,
            'name' => 'Attendance Analytics Tenant Office',
            'address' => 'Jakarta',
            'latitude' => -6.2000000,
            'longitude' => 106.8166667,
            'radius' => 250,
        ]);

        $employee = Employee::create([
            'tenant_id' => $tenant->id,
            'work_location_id' => $workLocation->id,
            'employee_code' => 'AAT-001',
            'name' => 'Attendance Analytics Tenant Employee',
            'email' => 'attendance-analytics-tenant-employee@example.test',
            'status' => 'active',
        ]);

        Attendance::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'date' => '2026-04-21',
            'check_in' => '08:00',
            'check_out' => '17:00',
            'status' => 'present',
        ]);

        return [$manager, $tenant];
    }
}