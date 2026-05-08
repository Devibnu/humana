<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollPolicy extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'status',
        'employment_type',
        'employee_level_code',
        'position_id',
        'priority',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function position()
    {
        return $this->belongsTo(Position::class);
    }

    public function components()
    {
        return $this->hasMany(PayrollPolicyComponent::class);
    }
}
