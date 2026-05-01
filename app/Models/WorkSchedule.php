<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'code',
        'name',
        'check_in_time',
        'check_out_time',
        'late_tolerance_minutes',
        'early_leave_tolerance_minutes',
        'description',
        'status',
        'sort_order',
    ];

    protected $casts = [
        'late_tolerance_minutes' => 'integer',
        'early_leave_tolerance_minutes' => 'integer',
        'sort_order' => 'integer',
    ];

    public static function defaults(): array
    {
        return [
            [
                'code' => 'office_hour',
                'name' => 'Office Hour',
                'check_in_time' => '08:00:00',
                'check_out_time' => '17:00:00',
                'late_tolerance_minutes' => 0,
                'early_leave_tolerance_minutes' => 0,
                'sort_order' => 10,
            ],
            [
                'code' => 'shift_pagi',
                'name' => 'Shift Pagi',
                'check_in_time' => '07:00:00',
                'check_out_time' => '15:00:00',
                'late_tolerance_minutes' => 0,
                'early_leave_tolerance_minutes' => 0,
                'sort_order' => 20,
            ],
            [
                'code' => 'shift_siang',
                'name' => 'Shift Siang',
                'check_in_time' => '15:00:00',
                'check_out_time' => '23:00:00',
                'late_tolerance_minutes' => 0,
                'early_leave_tolerance_minutes' => 0,
                'sort_order' => 30,
            ],
            [
                'code' => 'shift_malam',
                'name' => 'Shift Malam',
                'check_in_time' => '23:00:00',
                'check_out_time' => '07:00:00',
                'late_tolerance_minutes' => 0,
                'early_leave_tolerance_minutes' => 0,
                'sort_order' => 40,
            ],
        ];
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }
}
