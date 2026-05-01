<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Leave;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeavesAnalyticsBadgeSummaryTest extends TestCase
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

    public function test_badge_summary_tampil_di_halaman_analytics(): void
    {
        [$admin] = $this->makeContext();

        $response = $this->actingAs($admin)->get(route('leaves.analytics'));

        $response->assertOk();
        $response->assertSee('data-testid="leave-analytics-summary-pending"', false);
        $response->assertSee('data-testid="leave-analytics-summary-approved"', false);
        $response->assertSee('data-testid="leave-analytics-summary-rejected"', false);
        $response->assertSee('Jumlah request / total hari cuti');
    }

    public function test_angka_badge_summary_sesuai_data_cuti(): void
    {
        [$admin] = $this->makeContext();

        $response = $this->actingAs($admin)->get(route('leaves.analytics'));

        $response->assertOk();
        $response->assertSee('Pending: 2 / 3 hari');
        $response->assertSee('Approved: 1 / 3 hari');
        $response->assertSee('Rejected: 1 / 4 hari');

        $summary = $response->viewData('summary');

        $this->assertSame(2, $summary['pending_count']);
        $this->assertSame(3, $summary['pending_days']);
        $this->assertSame(1, $summary['approved_count']);
        $this->assertSame(3, $summary['approved_days']);
        $this->assertSame(1, $summary['rejected_count']);
        $this->assertSame(4, $summary['rejected_days']);
    }

    public function test_warna_badge_summary_sesuai_status(): void
    {
        [$admin] = $this->makeContext();

        $response = $this->actingAs($admin)->get(route('leaves.analytics'));

        $response->assertOk();
        $response->assertSee('class="badge bg-warning text-dark me-2"', false);
        $response->assertSee('class="badge bg-success me-2"', false);
        $response->assertSee('class="badge bg-danger"', false);
    }

    protected function makeContext(): array
    {
        $tenant = Tenant::create([
            'name' => 'Leaves Analytics Badge Tenant',
            'slug' => 'leaves-analytics-badge-tenant',
            'domain' => 'leaves-analytics-badge-tenant.test',
            'status' => 'active',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Leaves Analytics Badge Admin',
            'email' => 'leaves-analytics-badge-admin@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $employeeA = Employee::create([
            'tenant_id' => $tenant->id,
            'employee_code' => 'LAB-001',
            'name' => 'Leaves Analytics Badge Employee A',
            'email' => 'leaves-analytics-badge-employee-a@example.test',
            'status' => 'active',
        ]);

        $employeeB = Employee::create([
            'tenant_id' => $tenant->id,
            'employee_code' => 'LAB-002',
            'name' => 'Leaves Analytics Badge Employee B',
            'email' => 'leaves-analytics-badge-employee-b@example.test',
            'status' => 'active',
        ]);

        Leave::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employeeA->id,
            'leave_type' => 'annual',
            'start_date' => '2026-04-01',
            'end_date' => '2026-04-02',
            'reason' => 'Pending pertama',
            'status' => 'pending',
        ]);

        Leave::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employeeB->id,
            'leave_type' => 'annual',
            'start_date' => '2026-04-04',
            'end_date' => '2026-04-04',
            'reason' => 'Pending kedua',
            'status' => 'pending',
        ]);

        Leave::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employeeA->id,
            'leave_type' => 'annual',
            'start_date' => '2026-04-10',
            'end_date' => '2026-04-12',
            'reason' => 'Approved',
            'status' => 'approved',
        ]);

        Leave::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employeeB->id,
            'leave_type' => 'annual',
            'start_date' => '2026-04-15',
            'end_date' => '2026-04-18',
            'reason' => 'Rejected',
            'status' => 'rejected',
        ]);

        return [$admin, $tenant];
    }
}