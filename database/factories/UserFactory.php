<?php

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;

    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $tenantId = Tenant::query()->value('id');

        if (! $tenantId) {
            $tenantId = Tenant::create([
                'name' => 'Factory Tenant',
                'slug' => 'factory-tenant',
                'domain' => 'factory-tenant.test',
                'status' => 'active',
            ])->id;
        }

        return [
            'tenant_id' => $tenantId,
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => 'employee',
            'status' => 'active',
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function unverified()
    {
        return $this->state(fn (array $attributes) => []);
    }

    public function adminHr()
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'admin_hr',
        ]);
    }

    public function manager()
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'manager',
        ]);
    }

    public function employee()
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'employee',
        ]);
    }
}
