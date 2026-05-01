<?php

namespace Tests\Feature;

use App\Exports\EmployeesExport;
use App\Models\Employee;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class EmployeesExportCsvTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_hr_can_export_employees_csv_with_active_filters(): void
    {
        Carbon::setTestNow('2026-04-17 08:00:00');
        Excel::fake();

        $tenantA = $this->makeTenant('employees-export-tenant-a');
        $tenantB = $this->makeTenant('employees-export-tenant-b');
        $admin = $this->makeUser('admin_hr', $tenantA, 'employees-export-admin@example.test', 'Employees Export Admin');
        $linkedUser = $this->makeUser('employee', $tenantA, 'employees-export-linked@example.test', 'Employees Export Linked');
        $otherTenantLinked = $this->makeUser('employee', $tenantB, 'employees-export-other-tenant@example.test', 'Employees Export Other Tenant');

        Employee::create([
            'tenant_id' => $tenantA->id,
            'user_id' => $linkedUser->id,
            'employee_code' => 'EMP-EXP-1',
            'name' => 'Employees Export Linked',
            'email' => 'employees-export-linked-record@example.test',
            'status' => 'active',
        ]);

        Employee::create([
            'tenant_id' => $tenantA->id,
            'employee_code' => 'EMP-EXP-2',
            'name' => 'Employees Export Unlinked',
            'email' => 'employees-export-unlinked-record@example.test',
            'status' => 'active',
        ]);

        Employee::create([
            'tenant_id' => $tenantB->id,
            'user_id' => $otherTenantLinked->id,
            'employee_code' => 'EMP-EXP-3',
            'name' => 'Employees Export Other Tenant',
            'email' => 'employees-export-other-tenant-record@example.test',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->get(route('employees.export', [
            'tenant_id' => $tenantA->id,
            'linked' => 'only',
        ]));

        $response->assertOk();

        Excel::assertDownloaded('employees-export_20260417_tenant-employees-export-tenant-a_linked-only.csv', function (EmployeesExport $export) {
            $rows = $export->collection()->toArray();

            $this->assertCount(1, $rows);
            $this->assertSame('EMP-EXP-1', $rows[0]['employee_code']);
            $this->assertSame('employees-export-linked@example.test', $rows[0]['linked_user_email']);

            return true;
        });
    }

    public function test_manager_csv_export_remains_tenant_scoped(): void
    {
        Carbon::setTestNow('2026-04-17 08:00:00');
        Excel::fake();

        $tenantA = $this->makeTenant('employees-export-scope-tenant-a');
        $tenantB = $this->makeTenant('employees-export-scope-tenant-b');
        $manager = $this->makeUser('manager', $tenantA, 'employees-export-manager@example.test', 'Employees Export Manager');
        $tenantALinked = $this->makeUser('employee', $tenantA, 'employees-export-tenant-a-linked@example.test', 'Employees Export Tenant A Linked');
        $tenantBLinked = $this->makeUser('employee', $tenantB, 'employees-export-tenant-b-linked@example.test', 'Employees Export Tenant B Linked');

        Employee::create([
            'tenant_id' => $tenantA->id,
            'user_id' => $tenantALinked->id,
            'employee_code' => 'EMP-EXP-A1',
            'name' => 'Employees Export Tenant A Linked',
            'email' => 'employees-export-tenant-a-record@example.test',
            'status' => 'active',
        ]);

        Employee::create([
            'tenant_id' => $tenantB->id,
            'user_id' => $tenantBLinked->id,
            'employee_code' => 'EMP-EXP-B1',
            'name' => 'Employees Export Tenant B Linked',
            'email' => 'employees-export-tenant-b-record@example.test',
            'status' => 'active',
        ]);

        $response = $this->actingAs($manager)->get(route('employees.export', [
            'tenant_id' => $tenantB->id,
            'linked' => 'only',
        ]));

        $response->assertOk();

        Excel::assertDownloaded('employees-export_20260417_tenant-employees-export-scope-tenant-a_linked-only.csv', function (EmployeesExport $export) {
            $rows = $export->collection()->toArray();

            $this->assertCount(1, $rows);
            $this->assertSame('EMP-EXP-A1', $rows[0]['employee_code']);

            return true;
        });
    }

    public function test_employee_cannot_export_employees_csv(): void
    {
        $tenant = $this->makeTenant('employees-export-employee-forbidden');
        $employeeUser = $this->makeUser('employee', $tenant, 'employees-export-employee@example.test', 'Employees Export Employee');

        $this->actingAs($employeeUser)
            ->get(route('employees.export'))
            ->assertForbidden();
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
}