<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'payroll_id',
        'payroll_policy_component_id',
        'component_code',
        'component_name',
        'component_type',
        'amount',
        'quantity',
        'rate',
        'note',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'quantity' => 'decimal:2',
        'rate' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function payroll()
    {
        return $this->belongsTo(Payroll::class);
    }

    public function policyComponent()
    {
        return $this->belongsTo(PayrollPolicyComponent::class, 'payroll_policy_component_id');
    }
}
