<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'payroll_day',
        'period_start_day',
        'period_end_day',
        'period_month_offset',
        'publish_slips_on_approval',
        'status',
    ];

    protected $casts = [
        'payroll_day' => 'integer',
        'period_start_day' => 'integer',
        'period_end_day' => 'integer',
        'publish_slips_on_approval' => 'boolean',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function periods()
    {
        return $this->hasMany(PayrollPeriod::class);
    }

    public function periodDatesForMonth(string $payrollMonth): array
    {
        $payrollMonthDate = Carbon::parse($payrollMonth)->startOfMonth();
        $periodBase = $payrollMonthDate->copy();

        if ($this->period_month_offset === 'previous') {
            $periodBase->subMonthNoOverflow();
        }

        if ($this->period_start_day <= $this->period_end_day) {
            $periodStart = $this->dateInMonth($periodBase, $this->period_start_day);
            $periodEnd = $this->dateInMonth($periodBase, $this->period_end_day);
        } else {
            $periodStart = $this->dateInMonth($periodBase->copy()->subMonthNoOverflow(), $this->period_start_day);
            $periodEnd = $this->dateInMonth($periodBase, $this->period_end_day);
        }

        return [
            'payroll_month' => $payrollMonthDate->toDateString(),
            'period_start' => $periodStart->toDateString(),
            'period_end' => $periodEnd->toDateString(),
            'payroll_date' => $this->dateInMonth($payrollMonthDate, $this->payroll_day)->toDateString(),
        ];
    }

    protected function dateInMonth(Carbon $month, int $day): Carbon
    {
        return $month->copy()->day(min($day, $month->daysInMonth));
    }
}
