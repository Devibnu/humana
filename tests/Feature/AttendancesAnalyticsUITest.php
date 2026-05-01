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

/**
 * Regression tests untuk UI/UX analytics absensi.
 * Memastikan elemen filter, export, summary cards, dan empty-state
 * tampil dengan benar dan konsisten.
 */
class AttendancesAnalyticsUITest extends TestCase
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

    // ─── Filter ─────────────────────────────────────────────────────────────

    public function test_filter_tampil_dengan_ikon_fa_filter_dan_fa_undo(): void
    {
        [$admin] = $this->makeContextWithAttendances();

        $response = $this->actingAs($admin)->get(route('attendances.analytics'));

        $response->assertOk();
        $response->assertSee('fa-filter', false);
        $response->assertSee('fa-undo', false);
        $response->assertSee('data-testid="attendance-analytics-filter-btn"', false);
        $response->assertSee('data-testid="attendance-analytics-reset-btn"', false);
        $response->assertSee('data-testid="attendance-analytics-filter-section"', false);
    }

    // ─── Summary cards ───────────────────────────────────────────────────────

    public function test_summary_cards_tampil_dengan_badge_persentase(): void
    {
        [$admin] = $this->makeContextWithAttendances();

        $response = $this->actingAs($admin)->get(route('attendances.analytics'));

        $response->assertOk();
        $response->assertSee('data-testid="attendance-analytics-card-month-total"', false);
        $response->assertSee('data-testid="attendance-analytics-card-year-total"', false);
        $response->assertSee('data-testid="attendance-analytics-card-month-pct-present"', false);
        $response->assertSee('data-testid="attendance-analytics-card-year-pct-present"', false);
        // Badge persentase harus berisi angka dan tanda %
        $response->assertSee('%');
    }

    // ─── Export buttons ───────────────────────────────────────────────────────

    public function test_export_buttons_tampil_dalam_group_dengan_ikon(): void
    {
        [$admin] = $this->makeContextWithAttendances();

        $response = $this->actingAs($admin)->get(route('attendances.analytics'));

        $response->assertOk();
        $response->assertSee('data-testid="attendance-analytics-export-group"', false);
        $response->assertSee('data-testid="attendance-analytics-export-pdf"', false);
        $response->assertSee('data-testid="attendance-analytics-export-xlsx"', false);
        $response->assertSee('fa-file-pdf', false);
        $response->assertSee('fa-file-excel', false);
    }

    // ─── Empty-state chart ───────────────────────────────────────────────────

    public function test_empty_state_chart_muncul_jika_tidak_ada_data(): void
    {
        [$admin] = $this->makeContextWithoutAttendances();

        $response = $this->actingAs($admin)->get(route('attendances.analytics'));

        $response->assertOk();
        $response->assertSee('data-testid="attendance-analytics-monthly-trend-empty-state"', false);
        $response->assertSee('data-testid="attendance-analytics-yearly-distribution-empty-state"', false);
        $response->assertSee('Belum ada data absensi untuk periode ini.');
        // Chart canvas tidak boleh tampil saat tidak ada data
        $response->assertDontSee('data-testid="attendance-analytics-monthly-trend-chart-container"', false);
        $response->assertDontSee('data-testid="attendance-analytics-yearly-distribution-chart-container"', false);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    /**
     * Buat konteks admin + tenant + attendance (data ada).
     */
    protected function makeContextWithAttendances(): array
    {
        [$admin, $tenant, $workLocation] = $this->makeAdminAndTenant('ui-with-data');

        $employee = Employee::create([
            'tenant_id'        => $tenant->id,
            'work_location_id' => $workLocation->id,
            'employee_code'    => 'UI-001',
            'name'             => 'UI Employee One',
            'email'            => 'ui-emp-1@example.test',
            'status'           => 'active',
        ]);

        Attendance::create([
            'tenant_id'   => $tenant->id,
            'employee_id' => $employee->id,
            'date'        => '2026-04-01',
            'check_in'    => '08:00',
            'check_out'   => '17:00',
            'status'      => 'present',
        ]);

        return [$admin, $tenant];
    }

    /**
     * Buat konteks admin + tenant tanpa data attendance (untuk empty-state).
     */
    protected function makeContextWithoutAttendances(): array
    {
        [$admin, $tenant] = $this->makeAdminAndTenant('ui-no-data');

        return [$admin, $tenant];
    }

    protected function makeAdminAndTenant(string $slug): array
    {
        $tenant = Tenant::create([
            'name'   => ucfirst(str_replace('-', ' ', $slug)) . ' Tenant',
            'slug'   => $slug,
            'domain' => $slug . '.test',
            'status' => 'active',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name'      => 'UI Admin ' . $slug,
            'email'     => 'ui-admin-' . $slug . '@example.test',
            'password'  => 'password',
            'role'      => 'admin_hr',
            'status'    => 'active',
        ]);

        $workLocation = WorkLocation::create([
            'tenant_id' => $tenant->id,
            'name'      => 'UI Office ' . $slug,
            'address'   => 'Jakarta',
            'latitude'  => -6.2,
            'longitude' => 106.8166667,
            'radius'    => 250,
        ]);

        return [$admin, $tenant, $workLocation];
    }
}
