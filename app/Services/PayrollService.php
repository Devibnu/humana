<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;

class PayrollService
{
    /**
     * Calculate attendance-based deduction for an employee within a period.
     * - Rp 1000 per minute late (status == 'Telat' and late_minutes set)
     * - Full daily wage for 'Alpha' status
     */
    public function calculateAttendanceDeduction(Employee $employee, $periodStart, $periodEnd): float
    {
        $attendances = Attendance::where('employee_id', $employee->id)
            ->whereBetween('date', [$periodStart, $periodEnd])
            ->get();

        $deduction = 0;

        foreach ($attendances as $a) {
            if (($a->status ?? '') === 'Telat') {
                $lateMinutes = (int) ($a->late_minutes ?? 0);
                $deduction += ($lateMinutes * 1000);
            }

            if (($a->status ?? '') === 'Alpha') {
                $deduction += (float) ($employee->daily_wage ?? 0);
            }
        }

        return (float) $deduction;
    }
}
