<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LemburSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'role_pengaju',
        'butuh_persetujuan',
        'tipe_tarif',
        'nilai_tarif',
        'multiplier',
        'catatan',
    ];

    protected $casts = [
        'butuh_persetujuan' => 'boolean',
        'nilai_tarif' => 'decimal:2',
        'multiplier' => 'decimal:2',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
