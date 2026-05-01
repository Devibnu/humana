<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'Manager Demo',
                'email' => 'manager@humana.test',
                'role' => 'manager',
            ],
            [
                'name' => 'Employee Demo',
                'email' => 'employee@humana.test',
                'role' => 'employee',
            ],
        ];

        foreach ($users as $user) {
            DB::table('users')->updateOrInsert([
                'email' => $user['email'],
            ], [
                'tenant_id' => 1,
                'name' => $user['name'],
                'password' => Hash::make('secret'),
                'role' => $user['role'],
                'status' => 'active',
                'updated_at' => now(),
                'created_at' => now(),
            ]);
        }
    }
}