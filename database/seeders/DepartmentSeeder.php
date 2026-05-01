<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            'Human Resources',
            'Finance',
            'Operations',
        ];

        foreach ($departments as $name) {
            Department::updateOrCreate(
                [
                    'tenant_id' => 1,
                    'name' => $name,
                ],
                [
                    'status' => 'active',
                ]
            );
        }
    }
}