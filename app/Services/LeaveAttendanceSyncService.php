<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Leave;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class LeaveAttendanceSyncService
{
    public function syncForLeave(Leave $leave): void
    {
        if ($leave->status !== 'approved') {
            $this->clearForLeave($leave);

            return;
        }

        $this->applyApprovedLeaveAttendances($leave);
    }

    public function clearForLeave(Leave $leave): void
    {
        Attendance::query()
            ->where('leave_id', $leave->id)
            ->where('status', 'leave')
            ->whereNull('check_in')
            ->whereNull('check_out')
            ->delete();
    }

    protected function applyApprovedLeaveAttendances(Leave $leave): void
    {
        $startDate = Carbon::parse($leave->start_date)->startOfDay();
        $endDate = Carbon::parse($leave->end_date)->startOfDay();

        if ($endDate->lessThan($startDate)) {
            return;
        }

        $period = CarbonPeriod::create($startDate, $endDate);

        foreach ($period as $date) {
            /** @var Carbon $date */
            $attendance = Attendance::query()->firstOrNew([
                'employee_id' => $leave->employee_id,
                'date' => $date->toDateString(),
            ]);

            if (! $this->canSynchronizeAttendance($attendance, $leave)) {
                continue;
            }

            $attendance->tenant_id = $leave->tenant_id;
            $attendance->leave_id = $leave->id;
            $attendance->status = 'leave';
            $attendance->check_in = null;
            $attendance->check_out = null;
            $attendance->save();
        }
    }

    protected function canSynchronizeAttendance(Attendance $attendance, Leave $leave): bool
    {
        if (! $attendance->exists) {
            return true;
        }

        if ((int) ($attendance->leave_id ?? 0) === (int) $leave->id) {
            return true;
        }

        if ($attendance->leave_id !== null) {
            return false;
        }

        if (in_array($attendance->status, ['leave', 'absent'], true)
            && $attendance->check_in === null
            && $attendance->check_out === null) {
            return true;
        }

        return false;
    }
}
