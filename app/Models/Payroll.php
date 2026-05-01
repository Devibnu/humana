<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payroll extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'deduction_rule_id',
        'monthly_salary',
        'daily_wage',
        'allowance_transport',
        'allowance_meal',
        'allowance_health',
        'overtime_pay',
        'overtime_note',
        'deduction_tax',
        'deduction_bpjs',
        'deduction_loan',
        'deduction_attendance',
        'deduction_attendance_note',
        'period_start',
        'period_end',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function deductionRule()
    {
        return $this->belongsTo(DeductionRule::class);
    }

    protected $casts = [
        'monthly_salary' => 'decimal:2',
        'daily_wage' => 'decimal:2',
        'allowance_transport' => 'decimal:2',
        'allowance_meal' => 'decimal:2',
        'allowance_health' => 'decimal:2',
        'overtime_pay' => 'decimal:2',
        'deduction_tax' => 'decimal:2',
        'deduction_bpjs' => 'decimal:2',
        'deduction_loan' => 'decimal:2',
        'deduction_attendance' => 'decimal:2',
        'period_start' => 'date',
        'period_end' => 'date',
    ];
}
