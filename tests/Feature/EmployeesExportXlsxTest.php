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

class EmployeesExportXlsxTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_hr_can_export_employees_xlsx_with_active_filters(): void
    {
        Carbon::setTestNow('2026-04-17 08:00:00');
        Excel::fake();

        $tenantA = $this->makeTenant('employees-xlsx-tenant-a');
        $tenantB = $this->makeTenant('employees-xlsx-tenant-b');
        $admin = $this->makeUser('admin_hr', $tenantA, 'employees-xlsx-admin@example.test', 'Employees Xlsx Admin');
        $linkedUser = $this->makeUser('employee', $tenantA, 'employees-xlsx-linked@example.test', 'Employees Xlsx Linked');
        $otherTenantLinked = $this->makeUser('employee', $tenantB, 'employees-xlsx-other@example.test', 'Employees Xlsx Other');

        Employee::create([
            'tenant_id' => $tenantA->id,
            'user_id' => $linkedUser->id,
            'employee_code' => 'EMP-XL-1',
            'name' => 'Employees Xlsx Linked',
            'email' => 'employees-xlsx-linked-record@example.test',
            'status' => 'active',
        ]);

        Employee::create([
            'tenant_id' => $tenantA->id,
            'employee_code' => 'EMP-XL-2',
            'name' => 'Employees Xlsx Unlinked',
            'email' => 'employees-xlsx-unlinked-record@example.test',
            'status' => 'active',
        ]);

        Employee::create([
            'tenant_id' => $tenantB->id,
            'user_id' => $otherTenantLinked->id,
            'employee_code' => 'EMP-XL-3',
            'name' => 'Employees Xlsx Other Tenant',
            'email' => 'employees-xlsx-other-tenant-record@example.test',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->get(route('employees.export', [
            'tenant_id' => $tenantA->id,
            'linked' => 'only',
            'format' => 'xlsx',
        ]));

        $response->assertOk();

        Excel::assertDownloaded('employees-export_20260417_tenant-employees-xlsx-tenant-a_linked-only.xlsx', function (EmployeesExport $export) {
            $rows = $export->collection()->toArray();

            $this->assertCount(1, $rows);
            $this->assertSame('EMP-XL-1', $rows[0]['employee_code']);
            $this->assertSame('employees-xlsx-linked@example.test', $rows[0]['linked_user_email']);

            return true;
        });
    }

    public function test_manager_xlsx_export_remains_tenant_scoped(): void
    {
        Carbon::setTestNow('2026-04-17 08:00:00');
        Excel::fake();

        $tenantA = $this->makeTenant('employees-xlsx-scope-a');
        $tenantB = $this->makeTenant('employees-xlsx-scope-b');
        $manager = $this->makeUser('manager', $tenantA, 'employees-xlsx-manager@example.test', 'Employees Xlsx Manager');
        $tenantALinked = $this->makeUser('employee', $tenantA, 'employees-xlsx-tenant-a@example.test', 'Employees Xlsx Tenant A');
        $tenantBLinked = $this->makeUser('employee', $tenantB, 'employees-xlsx-tenant-b@example.test', 'Employees Xlsx Tenant B');

        Employee::create([
            'tenant_id' => $tenantA->id,
            'user_id' => $tenantALinked->id,
            'employee_code' => 'EMP-XLA-1',
            'name' => 'Employees Xlsx Tenant A Linked',
            'email' => 'employees-xlsx-tenant-a-record@example.test',
            'status' => 'active',
        ]);

        Employee::create([
            'tenant_id' => $tenantB->id,
            'user_id' => $tenantBLinked->id,
            'employee_code' => 'EMP-XLB-1',
            'name' => 'Employees Xlsx Tenant B Linked',
            'email' => 'employees-xlsx-tenant-b-record@example.test',
            'status' => 'active',
        ]);

        $response = $this->actingAs($manager)->get(route('employees.export', [
            'tenant_id' => $tenantB->id,
            'linked' => 'only',
            'format' => 'xlsx',
        ]));

        $response->assertOk();

        Excel::assertDownloaded('employees-export_20260417_tenant-employees-xlsx-scope-a_linked-only.xlsx', function (EmployeesExport $export) {
            $rows = $export->collection()->toArray();

            $this->assertCount(1, $rows);
            $this->assertSame('EMP-XLA-1', $rows[0]['employee_code']);

            return true;
        });
    }

    public function test_employee_cannot_export_employees_xlsx(): void
    {
        $tenant = $this->makeTenant('employees-xlsx-forbidden');
        $employeeUser = $this->makeUser('employee', $tenant, 'employees-xlsx-employee@example.test', 'Employees Xlsx Employee');

        $this->actingAs($employeeUser)
            ->get(route('employees.export', ['format' => 'xlsx']))
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