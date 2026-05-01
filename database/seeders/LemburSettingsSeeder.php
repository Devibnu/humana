<?php

namespace Database\Seeders;

use App\Models\LemburSetting;
use Illuminate\Database\Seeder;

class LemburSettingsSeeder extends Seeder
{
    public function run(): void
    {
        LemburSetting::query()->updateOrCreate(
            ['tenant_id' => 1],
            [
                'role_pengaju' => 'karyawan',
                'butuh_persetujuan' => true,
                'tipe_tarif' => 'per_jam',
                'nilai_tarif' => 50000,
                'multiplier' => 1.5,
                'catatan' => 'Default aturan lembur tenant',
            ]
        );
    }
}
