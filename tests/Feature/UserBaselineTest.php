<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UserBaselineTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_table_exists_after_migrate_fresh_seed(): void
    {
        Artisan::call('migrate:fresh', ['--seed' => true, '--force' => true]);

        $this->assertTrue(Schema::hasTable('users'));
        $this->assertDatabaseHas('users', [
            'email' => 'admin@humana.test',
            'role' => 'admin_hr',
        ]);
    }

    public function test_acting_as_factory_user_can_authenticate(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $this->assertAuthenticatedAs($user);
    }
}
