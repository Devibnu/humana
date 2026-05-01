<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'name' => 'Owner',
                'description' => 'Akses global panel owner',
                'permissions' => [],
            ],
            [
                'name' => 'Admin HR',
                'description' => 'Hak penuh akses',
                'permissions' => ['profile', 'users', 'users.manage', 'employees', 'employees.destroy', 'departments', 'positions', 'work_locations', 'tenants', 'roles', 'attendances', 'attendances.manage', 'attendances.destroy', 'leaves', 'leaves.create', 'leaves.manage', 'leaves.destroy', 'leaves.analytics', 'leaves.reports', 'leaves.approval.hr', 'lembur', 'lembur.submit', 'lembur.approval', 'lembur.reports', 'payroll', 'payroll.manage', 'payroll.reports', 'reports'],
            ],
            [
                'name' => 'Supervisor',
                'description' => 'Persetujuan tahap awal tim',
                'permissions' => ['profile', 'employees', 'attendances', 'leaves', 'leaves.approval.supervisor'],
            ],
            [
                'name' => 'Manager',
                'description' => 'Kelola tim',
                'permissions' => ['profile', 'users', 'employees', 'work_locations', 'attendances', 'attendances.manage', 'leaves', 'leaves.manage', 'leaves.analytics', 'leaves.reports', 'leaves.approval.manager', 'lembur', 'lembur.submit', 'lembur.approval'],
            ],
            [
                'name' => 'Employee',
                'description' => 'Akses terbatas',
                'permissions' => ['profile', 'lembur', 'lembur.submit'],
            ],
        ];

        foreach ($roles as $role) {
            $model = Role::updateOrCreate(
                ['name' => $role['name']],
                ['description' => $role['description']]
            );

            $model->permissions()->delete();
            $model->permissions()->createMany(array_map(fn (string $menuKey) => [
                'menu_key' => $menuKey,
                'can_access' => true,
            ], $role['permissions']));
        }
    }
}