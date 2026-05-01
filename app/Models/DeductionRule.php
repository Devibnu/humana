<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeductionRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'salary_type',
        'working_hours_per_day',
        'working_days_per_month',
        'tolerance_minutes',
        'rate_type',
        'alpha_full_day',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
