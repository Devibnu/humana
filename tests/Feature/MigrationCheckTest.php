<?php

namespace Tests\Feature;

use App\Models\Employee;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MigrationCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_table_exists_after_migrate_fresh_and_seed(): void
    {
        Artisan::call('migrate:fresh', [
            '--seed' => true,
            '--force' => true,
        ]);

        $this->assertTrue(Schema::hasTable('users'));
        $this->assertDatabaseHas('users', [
            'email' => 'admin@humana.test',
            'role' => 'admin_hr',
        ]);
    }

    public function test_employee_user_tenant_relationship_is_consistent_after_seed(): void
    {
        $this->seed(DatabaseSeeder::class);

        $employee = Employee::with(['tenant', 'user.tenant'])
            ->where('employee_code', 'EMP-1000')
            ->firstOrFail();

        $this->assertNotNull($employee->user);
        $this->assertNotNull($employee->tenant);
        $this->assertNotNull($employee->user->tenant);
        $this->assertSame($employee->tenant_id, $employee->user->tenant_id);
        $this->assertTrue($employee->tenant->is($employee->user->tenant));
    }
}