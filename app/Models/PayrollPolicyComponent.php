<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollPolicyComponent extends Model
{
    use HasFactory;

    protected $fillable = [
        'payroll_policy_id',
        'component_code',
        'component_name',
        'component_type',
        'amount',
        'calculation_method',
        'leave_paid_behavior',
        'leave_unpaid_behavior',
        'attendance_based',
        'taxable',
        'sort_order',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'attendance_based' => 'boolean',
        'taxable' => 'boolean',
    ];

    public function policy()
    {
        return $this->belongsTo(PayrollPolicy::class, 'payroll_policy_id');
    }
}
