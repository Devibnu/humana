<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RolesMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_roles_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('roles'));
        $this->assertTrue(Schema::hasColumn('users', 'role_id'));
    }

    public function test_roles_seeder_inserts_three_default_roles(): void
    {
        Artisan::call('db:seed', [
            '--class' => RolesTableSeeder::class,
            '--force' => true,
        ]);

        $this->assertDatabaseHas('roles', ['name' => 'Admin HR', 'description' => 'Hak penuh akses']);
        $this->assertDatabaseHas('roles', ['name' => 'Manager', 'description' => 'Kelola tim']);
        $this->assertDatabaseHas('roles', ['name' => 'Employee', 'description' => 'Akses terbatas']);
        $this->assertSame(3, Role::query()->whereIn('name', ['Admin HR', 'Manager', 'Employee'])->count());
    }

    public function test_user_can_be_assigned_with_role_id(): void
    {
        Artisan::call('db:seed', [
            '--class' => RolesTableSeeder::class,
            '--force' => true,
        ]);

        $tenant = Tenant::create([
            'name' => 'Role Assignment Tenant',
            'slug' => 'role-assignment-tenant',
            'domain' => 'role-assignment-tenant.test',
            'status' => 'active',
        ]);

        $adminRole = Role::where('name', 'Admin HR')->firstOrFail();
        $employeeRole = Role::where('name', 'Employee')->firstOrFail();

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Admin Role Assignment',
            'email' => 'admin-role-assignment@example.test',
            'password' => 'password123',
            'role_id' => $adminRole->id,
            'role' => $adminRole->system_key,
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->post(route('users.store'), [
            'tenant_id' => $tenant->id,
            'name' => 'Assigned Role User',
            'email' => 'assigned-role-user@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role_id' => $employeeRole->id,
            'status' => 'active',
        ]);

        $response->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'assigned-role-user@example.test',
            'role_id' => $employeeRole->id,
            'role' => 'employee',
        ]);
    }
}