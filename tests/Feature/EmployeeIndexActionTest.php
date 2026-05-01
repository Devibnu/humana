<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeIndexActionTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesTableSeeder::class);

        $this->tenant = Tenant::create([
            'name' => 'Employee Index Action Tenant',
            'slug' => 'employee-index-action-tenant',
            'domain' => 'employee-index-action-tenant.test',
            'status' => 'active',
        ]);
    }

    public function test_index_shows_action_buttons_for_each_employee(): void
    {
        $user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'role_id' => Role::where('name', 'Admin HR')->firstOrFail()->id,
        ]);

        $firstEmployee = Employee::create([
            'tenant_id' => $this->tenant->id,
            'employee_code' => 'EMP-ACT-001',
            'name' => 'Tmp Employee One',
            'email' => 'tmp-employee-one@example.test',
            'status' => 'active',
        ]);

        $secondEmployee = Employee::create([
            'tenant_id' => $this->tenant->id,
            'employee_code' => 'EMP-ACT-002',
            'name' => 'Tmp Employee Two',
            'email' => 'tmp-employee-two@example.test',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->get('/employees');

        $response->assertOk();
        $response->assertSee('Action');
        $response->assertSee('data-testid="employee-actions-'.$firstEmployee->id.'"', false);
        $response->assertSee('data-testid="employee-actions-'.$secondEmployee->id.'"', false);
        $response->assertSee('title="Detail"', false);
        $response->assertSee('title="Edit"', false);
        $response->assertSee('title="Delete"', false);
        $response->assertSee('btn btn-link text-info p-0', false);
        $response->assertSee('btn btn-link text-warning p-0', false);
        $response->assertSee('btn btn-link text-danger p-0', false);
        $response->assertSee('fa-eye', false);
        $response->assertSee('fa-edit', false);
        $response->assertSee('fa-trash', false);
        $response->assertDontSee('>Detail<', false);
        $response->assertDontSee('>Edit<', false);
        $response->assertDontSee('>Delete<', false);
    }

    public function test_index_action_buttons_link_to_correct_routes(): void
    {
        $user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'role_id' => Role::where('name', 'Admin HR')->firstOrFail()->id,
        ]);

        $employee = Employee::create([
            'tenant_id' => $this->tenant->id,
            'employee_code' => 'EMP-ACT-003',
            'name' => 'Tmp Employee',
            'email' => 'tmp-employee@example.test',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->get('/employees');

        $response->assertOk();
        $response->assertSee(route('employees.show', $employee), false);
        $response->assertSee(route('employees.edit', $employee), false);
        $response->assertSee(route('employees.destroy', $employee), false);
    }
}