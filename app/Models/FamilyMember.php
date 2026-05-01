<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FamilyMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'name',
        'relationship',
        'dob',
        'education',
        'job',
        'marital_status',
    ];

    protected $casts = [
        'dob' => 'date',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public static function relationships(): array
    {
        return [
            'pasangan'   => 'Pasangan (Suami/Istri)',
            'anak'       => 'Anak',
            'orang_tua'  => 'Orang Tua',
            'saudara'    => 'Saudara Kandung',
            'lainnya'    => 'Lainnya',
        ];
    }

    public static function modalRelationships(): array
    {
        return [
            'pasangan'  => 'Pasangan',
            'anak'      => 'Anak',
            'orang_tua' => 'Orang Tua',
        ];
    }

    public function relationshipLabel(): string
    {
        return self::relationships()[$this->relationship] ?? ucfirst($this->relationship);
    }

    public static function maritalStatuses(): array
    {
        return [
            'belum_menikah' => 'Belum Menikah',
            'menikah'       => 'Menikah',
            'cerai'         => 'Cerai',
            'duda_janda'    => 'Duda/Janda',
        ];
    }

    public function maritalStatusLabel(): string
    {
        return self::maritalStatuses()[$this->marital_status] ?? ($this->marital_status ? ucfirst($this->marital_status) : '—');
    }
}
