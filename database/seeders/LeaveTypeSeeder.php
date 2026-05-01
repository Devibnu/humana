<?php

namespace Database\Seeders;

use App\Models\LeaveType;
use Illuminate\Database\Seeder;

class LeaveTypeSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = 1;

        $types = [
            ['name' => 'Cuti Tahunan', 'is_paid' => true, 'wajib_lampiran' => false, 'wajib_persetujuan' => true, 'alur_persetujuan' => 'single'],
            ['name' => 'Cuti Sakit', 'is_paid' => true, 'wajib_lampiran' => true, 'wajib_persetujuan' => true, 'alur_persetujuan' => 'multi'],
            ['name' => 'Izin Pribadi', 'is_paid' => false, 'wajib_lampiran' => false, 'wajib_persetujuan' => false, 'alur_persetujuan' => 'auto'],
        ];

        foreach ($types as $type) {
            LeaveType::query()->updateOrCreate(
                ['tenant_id' => $tenantId, 'name' => $type['name']],
                [
                    'is_paid' => $type['is_paid'],
                    'wajib_lampiran' => $type['wajib_lampiran'],
                    'wajib_persetujuan' => $type['wajib_persetujuan'],
                    'alur_persetujuan' => $type['alur_persetujuan'],
                ]
            );
        }
    }
}
