<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WorkLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkLocationAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_can_be_assigned_to_a_work_location_while_keeping_user_link(): void
    {
        $tenant = Tenant::create([
            'name' => 'Work Location Assignment Tenant',
            'slug' => 'work-location-assignment-tenant',
            'domain' => 'work-location-assignment-tenant.test',
            'status' => 'active',
        ]);

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Work Location Employee User',
            'email' => 'work-location-employee-user@example.test',
            'password' => 'password',
            'role' => 'employee',
            'status' => 'active',
        ]);

        $workLocation = WorkLocation::create([
            'tenant_id' => $tenant->id,
            'name' => 'Head Office',
            'address' => 'Central Business District',
            'latitude' => -6.2000000,
            'longitude' => 106.8166667,
            'radius' => 150,
        ]);

        $employee = Employee::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'work_location_id' => $workLocation->id,
            'employee_code' => 'WL-001',
            'name' => 'Work Location Employee',
            'email' => 'work-location-employee@example.test',
            'status' => 'active',
        ]);

        $this->assertSame($workLocation->id, $employee->work_location_id);
        $this->assertTrue($employee->workLocation->is($workLocation));
        $this->assertTrue($employee->user->is($user));
        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
            'work_location_id' => $workLocation->id,
            'user_id' => $user->id,
        ]);
    }
}