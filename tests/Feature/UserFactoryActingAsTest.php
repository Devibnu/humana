<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserFactoryActingAsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesTableSeeder::class);
    }

    public function test_acting_as_factory_user_authenticates_successfully(): void
    {
        $user = User::factory()->adminHr()->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $this->assertAuthenticatedAs($user);
    }

    public function test_leave_access_follows_factory_user_role(): void
    {
        $tenant = Tenant::create([
            'name' => 'Factory ActingAs Tenant',
            'slug' => 'factory-actingas-tenant',
            'domain' => 'factory-actingas-tenant.test',
            'status' => 'active',
        ]);

        $admin = User::factory()->adminHr()->create([
            'tenant_id' => $tenant->id,
        ]);

        $manager = User::factory()->manager()->create([
            'tenant_id' => $tenant->id,
        ]);

        $employeeUser = User::factory()->employee()->create([
            'tenant_id' => $tenant->id,
        ]);

        Employee::create([
            'tenant_id' => $tenant->id,
            'user_id' => $employeeUser->id,
            'employee_code' => 'EMP-FACT-001',
            'name' => 'Factory ActingAs Employee',
            'email' => 'factory-actingas-employee@example.test',
            'status' => 'active',
        ]);

        $this->actingAs($admin)->get(route('leaves.index'))->assertOk();
        $this->actingAs($admin)->get(route('leaves.create'))->assertOk();

        $this->actingAs($manager)->get(route('leaves.index'))->assertOk();
        $this->actingAs($manager)->get(route('leaves.create'))->assertForbidden();

        $this->actingAs($employeeUser)->get(route('leaves.index'))->assertForbidden();
        $this->actingAs($employeeUser)->get(route('leaves.create'))->assertForbidden();
        $this->actingAs($employeeUser)->get(route('leaves.export'))->assertForbidden();
    }
}