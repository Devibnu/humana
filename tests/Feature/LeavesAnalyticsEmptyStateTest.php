<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeavesAnalyticsEmptyStateTest extends TestCase
{
    use RefreshDatabase;

    public function test_analytics_breakdown_shows_empty_state_when_no_leaves(): void
    {
        $tenant = Tenant::create([
            'name' => 'JS',
            'slug' => 'js-empty-analytics',
            'domain' => 'js-empty-analytics.test',
            'status' => 'active',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Admin HR',
            'email' => 'admin@humana.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->get(route('leaves.analytics', [
            'tenant_id' => $tenant->id,
        ]));

        $response->assertStatus(200);
        $response->assertSee('Belum ada data jenis cuti pada periode ini.');
    }
}
