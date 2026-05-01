<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $hrManager = Position::where('tenant_id', 1)->where('name', 'HR Manager')->first();
        $recruiter = Position::where('tenant_id', 1)->where('name', 'Recruiter')->first();
        $humanResources = Department::where('tenant_id', 1)->where('name', 'Human Resources')->first();
        $operations = Department::where('tenant_id', 1)->where('name', 'Operations')->first();

        $employees = [
            [
                'employee_code' => 'EMP-1000',
                'name' => 'Employee Demo',
                'email' => 'employee-record@humana.test',
                'phone' => '081230001000',
                'user_id' => DB::table('users')->where('email', 'employee@humana.test')->value('id'),
                'position_id' => $hrManager?->id,
                'department_id' => $humanResources?->id,
            ],
            [
                'employee_code' => 'EMP-1001',
                'name' => 'Alya Humania',
                'email' => 'alya.humania@humana.test',
                'phone' => '081230001001',
                'user_id' => null,
                'position_id' => $hrManager?->id,
                'department_id' => $humanResources?->id,
            ],
            [
                'employee_code' => 'EMP-1002',
                'name' => 'Raka Pratama',
                'email' => 'raka.pratama@humana.test',
                'phone' => '081230001002',
                'user_id' => null,
                'position_id' => $recruiter?->id,
                'department_id' => $operations?->id,
            ],
        ];

        foreach ($employees as $employee) {
            Employee::updateOrCreate(
                [
                    'tenant_id' => 1,
                    'employee_code' => $employee['employee_code'],
                ],
                [
                    'user_id' => $employee['user_id'],
                    'name' => $employee['name'],
                    'email' => $employee['email'],
                    'phone' => $employee['phone'],
                    'position_id' => $employee['position_id'],
                    'department_id' => $employee['department_id'],
                    'status' => 'active',
                ]
            );
        }
    }
}