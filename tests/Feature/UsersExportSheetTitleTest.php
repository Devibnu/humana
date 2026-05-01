<?php

namespace Tests\Feature;

use App\Exports\UsersExport;
use App\Models\Employee;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsersExportSheetTitleTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_export_sheet_title_contains_tenant_name(): void
    {
        $tenant = Tenant::create([
            'name' => 'North Branch Office',
            'slug' => 'north-branch-office',
            'domain' => 'north-branch-office.test',
            'status' => 'active',
        ]);

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Sheet Title User',
            'email' => 'sheet-title-user@example.test',
            'password' => 'password',
            'role' => 'employee',
            'status' => 'active',
        ]);

        Employee::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'employee_code' => 'USR-TTL-1',
            'name' => 'Sheet Title Employee',
            'email' => 'sheet-title-employee@example.test',
            'status' => 'active',
        ]);

        $export = new UsersExport(User::with(['tenant', 'employee'])->get(), [
            'tenant' => $tenant->id,
            'tenant_name' => $tenant->name,
            'role' => 'employee',
            'linked' => 'only',
        ]);

        $this->assertStringContainsString('North', $export->title());
        $this->assertStringContainsString('Users', $export->title());
    }
}