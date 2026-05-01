<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Leave;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class LeaveAnnualSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_annual_summary_aggregates_requests_and_days_per_status(): void
    {
        $tenant = $this->makeTenant('leave-annual-summary-tenant');
        $admin = $this->makeUser('admin_hr', $tenant, 'leave-annual-summary-admin@example.test', 'Leave Annual Summary Admin');
        $employee = $this->makeEmployee($tenant, 'LVE-YEAR-1', 'Leave Annual Summary Employee', 'leave-annual-summary-employee@example.test');

        $this->makeLeave($tenant, $employee, '2025-03-01', '2025-03-02', 'pending');
        $this->makeLeave($tenant, $employee, '2025-07-10', '2025-07-12', 'approved');
        $this->makeLeave($tenant, $employee, '2026-01-05', '2026-01-08', 'approved');
        $this->makeLeave($tenant, $employee, '2026-09-09', '2026-09-09', 'rejected');

        $response = $this->actingAs($admin)->get(route('leaves.index'));

        $response->assertOk();

        $annualSummary = collect($response->viewData('annualSummary'));

        $this->assertSummaryRow($annualSummary, '2025', 'pending', 1, 2);
        $this->assertSummaryRow($annualSummary, '2025', 'approved', 1, 3);
        $this->assertSummaryRow($annualSummary, '2026', 'approved', 1, 4);
        $this->assertSummaryRow($annualSummary, '2026', 'rejected', 1, 1);
    }

    protected function assertSummaryRow(Collection $rows, string $periodKey, string $status, int $requests, int $days): void
    {
        $row = $rows->first(fn (array $row) => $row['period_key'] === $periodKey && $row['status'] === $status);

        $this->assertNotNull($row);
        $this->assertSame($requests, $row['requests']);
        $this->assertSame($days, $row['days']);
    }

    protected function makeTenant(string $slug): Tenant
    {
        return Tenant::create([
            'name' => ucwords(str_replace('-', ' ', $slug)),
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
            'password' => 'password',
            'role' => $role,
            'status' => 'active',
        ]);
    }

    protected function makeEmployee(Tenant $tenant, string $code, string $name, string $email): Employee
    {
        return Employee::create([
            'tenant_id' => $tenant->id,
            'employee_code' => $code,
            'name' => $name,
            'email' => $email,
            'status' => 'active',
        ]);
    }

    protected function makeLeave(Tenant $tenant, Employee $employee, string $startDate, string $endDate, string $status): Leave
    {
        return Leave::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'leave_type' => 'annual',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'reason' => 'Annual '.$status,
            'status' => $status,
        ]);
    }
}