<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Leave;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class LeaveYearFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_summary_respects_selected_year(): void
    {
        $tenant = $this->makeTenant('leave-year-filter-tenant');
        $admin = $this->makeUser('admin_hr', $tenant, 'leave-year-filter-admin@example.test', 'Leave Year Filter Admin');
        $employee = $this->makeEmployee($tenant, 'LVE-YEAR-FLT-1', 'Leave Year Filter Employee', 'leave-year-filter-employee@example.test');

        $this->makeLeave($tenant, $employee, '2025-04-10', '2025-04-12', 'pending');
        $this->makeLeave($tenant, $employee, '2026-02-05', '2026-02-06', 'approved');
        $this->makeLeave($tenant, $employee, '2026-07-20', '2026-07-20', 'rejected');

        $response = $this->actingAs($admin)->get(route('leaves.index', ['year' => 2026]));

        $response->assertOk();

        $monthlySummary = collect($response->viewData('monthlySummary'));
        $annualSummary = collect($response->viewData('annualSummary'));
        $filteredSummary = collect($response->viewData('filteredSummary'));

        $this->assertCount(6, $monthlySummary);
        $this->assertSummaryRow($monthlySummary, '2026-02', 'approved', 1, 2);
        $this->assertSummaryRow($monthlySummary, '2026-07', 'rejected', 1, 1);
        $this->assertSummaryRow($annualSummary, '2026', 'approved', 1, 2);
        $this->assertSummaryRow($annualSummary, '2026', 'rejected', 1, 1);
        $this->assertFilterRow($filteredSummary, '2026', 'approved', 1, 2);
        $this->assertFilterRow($filteredSummary, '2026', 'rejected', 1, 1);
        $this->assertSame(['Feb 2026', 'Jul 2026'], $response->viewData('monthlyTrendChart')['labels']);
    }

    protected function assertSummaryRow(Collection $rows, string $periodKey, string $status, int $requests, int $days): void
    {
        $row = $rows->first(fn (array $row) => $row['period_key'] === $periodKey && $row['status'] === $status);

        $this->assertNotNull($row);
        $this->assertSame($requests, $row['requests']);
        $this->assertSame($days, $row['days']);
    }

    protected function assertFilterRow(Collection $rows, string $scope, string $status, int $requests, int $days): void
    {
        $row = $rows->first(fn (array $row) => $row['filter_scope'] === $scope && $row['status'] === $status);

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
            'reason' => 'Year filter '.$status,
            'status' => $status,
        ]);
    }
}