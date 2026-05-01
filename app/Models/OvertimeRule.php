<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OvertimeRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'salary_type',
        'standard_hours_per_day',
        'rate_first_hour',
        'rate_next_hours',
    ];

    protected $casts = [
        'standard_hours_per_day' => 'integer',
        'rate_first_hour' => 'decimal:2',
        'rate_next_hours' => 'decimal:2',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
