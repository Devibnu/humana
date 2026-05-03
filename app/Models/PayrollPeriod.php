<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollPeriod extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'payroll_setting_id',
        'name',
        'payroll_month',
        'period_start',
        'period_end',
        'payroll_date',
        'status',
        'generated_at',
        'approved_at',
        'published_at',
        'created_by',
    ];

    protected $casts = [
        'payroll_month' => 'date',
        'period_start' => 'date',
        'period_end' => 'date',
        'payroll_date' => 'date',
        'generated_at' => 'datetime',
        'approved_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function setting()
    {
        return $this->belongsTo(PayrollSetting::class, 'payroll_setting_id');
    }

    public function payrolls()
    {
        return $this->hasMany(Payroll::class);
    }
}
