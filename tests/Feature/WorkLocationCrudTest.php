<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Models\WorkLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkLocationCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_work_locations(): void
    {
        $tenant = Tenant::create([
            'name' => 'Work Location Crud Tenant',
            'slug' => 'work-location-crud-tenant',
            'domain' => 'work-location-crud-tenant.test',
            'status' => 'active',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Work Location Crud Admin',
            'email' => 'work-location-crud-admin@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $indexResponse = $this->actingAs($admin)->get(route('work_locations.index'));
        $indexResponse->assertOk();
        $indexResponse->assertSee('Daftar Lokasi Kerja');
        $indexResponse->assertSee('data-testid="work-locations-summary-total"', false);
        $indexResponse->assertSee('data-testid="work-locations-filter-form"', false);

        $this->actingAs($admin)->post(route('work_locations.store'), [
            'tenant_id' => $tenant->id,
            'name' => 'Head Office',
            'address' => 'Main Building',
            'latitude' => -6.2000000,
            'longitude' => 106.8166667,
            'radius' => 150,
        ])->assertRedirect(route('work_locations.index'));

        $workLocation = WorkLocation::where('name', 'Head Office')->firstOrFail();

        $this->assertDatabaseHas('work_locations', [
            'id' => $workLocation->id,
            'tenant_id' => $tenant->id,
            'radius' => 150,
        ]);

        $this->actingAs($admin)->get(route('work_locations.index'))
            ->assertOk()
            ->assertSee('Head Office');

        $this->actingAs($admin)->get(route('work_locations.edit', $workLocation))->assertOk();

        $this->actingAs($admin)->put(route('work_locations.update', $workLocation), [
            'tenant_id' => $tenant->id,
            'name' => 'Head Office Updated',
            'address' => 'Main Building 2',
            'latitude' => -6.2010000,
            'longitude' => 106.8170000,
            'radius' => 200,
        ])->assertRedirect(route('work_locations.index'));

        $this->assertDatabaseHas('work_locations', [
            'id' => $workLocation->id,
            'name' => 'Head Office Updated',
            'address' => 'Main Building 2',
            'radius' => 200,
        ]);

        $this->actingAs($admin)->delete(route('work_locations.destroy', $workLocation))
            ->assertRedirect(route('work_locations.index'));

        $this->assertDatabaseMissing('work_locations', [
            'id' => $workLocation->id,
        ]);
    }
}
