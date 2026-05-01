<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class UserSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_table_is_filled_after_migrate_fresh_seed(): void
    {
        Artisan::call('migrate:fresh', [
            '--seed' => true,
            '--force' => true,
        ]);

        $this->assertDatabaseCount('users', 3);
        $this->assertDatabaseHas('users', [
            'email' => 'admin@humana.test',
            'role' => 'admin_hr',
        ]);

        $this->assertDatabaseMissing('users', [
            'email' => 'admin@humana.test',
            'role_id' => null,
        ]);
    }

    public function test_reseeding_restores_login_even_after_users_are_truncated(): void
    {
        Artisan::call('migrate:fresh', [
            '--seed' => true,
            '--force' => true,
        ]);

        User::query()->delete();
        $this->assertDatabaseCount('users', 0);

        Artisan::call('db:seed', [
            '--class' => 'Database\\Seeders\\UserSeeder',
            '--force' => true,
        ]);

        $this->assertTrue(Auth::attempt([
            'email' => 'admin@humana.test',
            'password' => 'password',
        ]));
    }
}