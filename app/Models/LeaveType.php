<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveType extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'is_paid',
        'wajib_lampiran',
        'wajib_persetujuan',
        'alur_persetujuan',
    ];

    protected $casts = [
        'is_paid' => 'boolean',
        'wajib_lampiran' => 'boolean',
        'wajib_persetujuan' => 'boolean',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function leaves()
    {
        return $this->hasMany(Leave::class);
    }

    public static function defaults(): array
    {
        return [
            'annual' => ['name' => 'Cuti Tahunan', 'is_paid' => true, 'wajib_lampiran' => false, 'wajib_persetujuan' => true, 'alur_persetujuan' => 'single'],
            'sick' => ['name' => 'Cuti Sakit', 'is_paid' => true, 'wajib_lampiran' => true, 'wajib_persetujuan' => true, 'alur_persetujuan' => 'multi'],
            'permission' => ['name' => 'Izin Pribadi', 'is_paid' => false, 'wajib_lampiran' => false, 'wajib_persetujuan' => false, 'alur_persetujuan' => 'auto'],
            'unpaid' => ['name' => 'Cuti Tanpa Upah', 'is_paid' => false, 'wajib_lampiran' => false, 'wajib_persetujuan' => true, 'alur_persetujuan' => 'single'],
        ];
    }

    public static function definitionFromInput(string $input): array
    {
        $normalized = strtolower(trim($input));

        return match ($normalized) {
            'annual', 'cuti tahunan' => self::defaults()['annual'],
            'sick', 'cuti sakit', 'sakit' => self::defaults()['sick'],
            'permission', 'izin', 'izin pribadi' => self::defaults()['permission'],
            'unpaid', 'cuti tanpa upah' => self::defaults()['unpaid'],
            default => [
                'name' => ucwords(str_replace(['-', '_'], ' ', $input)),
                'is_paid' => true,
                'wajib_lampiran' => false,
                'wajib_persetujuan' => true,
                'alur_persetujuan' => 'single',
            ],
        };
    }
}
