<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            TenantSeeder::class,
            LeaveTypeSeeder::class,
            LemburSettingsSeeder::class,
            RolesSeeder::class,
            UsersTableSeeder::class,
            PositionSeeder::class,
            DepartmentSeeder::class,
            EmployeeSeeder::class,
        ]);
    }
}
