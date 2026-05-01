<?php

namespace Database\Seeders;

use App\Models\Position;
use Illuminate\Database\Seeder;

class PositionSeeder extends Seeder
{
    public function run(): void
    {
        $positions = [
            'HR Manager',
            'Recruiter',
            'Payroll Specialist',
        ];

        foreach ($positions as $name) {
            Position::updateOrCreate(
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