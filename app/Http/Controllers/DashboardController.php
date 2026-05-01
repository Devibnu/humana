<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendanceLog;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\Position;
use App\Models\User;
use App\Models\WorkLocation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $currentUser = $request->user();
        $employee = $currentUser?->employee;
        $today = now()->toDateString();

        $employeesTotal = $this->scopeEmployees(Employee::query(), $currentUser, $employee?->id)->count();
        $attendancesTodayTotal = $this->scopeAttendances(Attendance::query(), $currentUser, $employee?->id)
            ->whereDate('date', $today)
            ->count();
        $leavesPendingApprovalTotal = $this->scopeLeaves(Leave::query(), $currentUser, $employee?->id)
            ->where('status', 'pending')
            ->count();
        $workLocationsTotal = $this->scopeWorkLocations(WorkLocation::query(), $currentUser, $employee)->count();
        $positionsTotal = $this->scopePositions(Position::query(), $currentUser, $employee)->count();
        $departmentsTotal = $this->scopeDepartments(Department::query(), $currentUser, $employee)->count();

        $leaveStatusSummary = $this->buildStatusSummary(
            ['pending', 'approved', 'rejected'],
            fn (string $status) => $this->scopeLeaves(Leave::query(), $currentUser, $employee?->id)->where('status', $status)->count(),
        );

        $attendanceStatusSummary = $this->buildStatusSummary(
            ['present', 'absent', 'late'],
            fn (string $status) => $this->scopeAttendances(Attendance::query(), $currentUser, $employee?->id)
                ->whereDate('date', $today)
                ->where('status', $status)
                ->count(),
        );

        $attendancePerWorkLocationChart = $this->buildAttendancePerWorkLocationChart($currentUser, $employee?->id, $today);
        $leaveStatusChart = [
            'labels' => ['Menunggu', 'Disetujui', 'Ditolak'],
            'counts' => [
                $leaveStatusSummary['pending'] ?? 0,
                $leaveStatusSummary['approved'] ?? 0,
                $leaveStatusSummary['rejected'] ?? 0,
            ],
            'backgroundColor' => ['#fbcf33', '#82d616', '#ea0606'],
        ];

        return view('dashboard.index', compact(
            'currentUser',
            'employeesTotal',
            'attendancesTodayTotal',
            'leavesPendingApprovalTotal',
            'workLocationsTotal',
            'positionsTotal',
            'departmentsTotal',
            'attendancePerWorkLocationChart',
            'leaveStatusChart',
            'leaveStatusSummary',
            'attendanceStatusSummary'
        ));
    }

    protected function buildStatusSummary(array $statuses, callable $resolver): array
    {
        $summary = [];

        foreach ($statuses as $status) {
            $summary[$status] = (int) $resolver($status);
        }

        return $summary;
    }

    protected function buildAttendancePerWorkLocationChart(?User $currentUser, ?int $employeeId, string $today): array
    {
        $rows = AttendanceLog::query()
            ->join('work_locations', 'work_locations.id', '=', 'attendance_logs.work_location_id')
            ->join('attendances', 'attendances.id', '=', 'attendance_logs.attendance_id')
            ->when($currentUser?->isManager(), fn (Builder $query) => $query->where('attendance_logs.tenant_id', $currentUser->tenant_id))
            ->when($currentUser?->isEmployee(), fn (Builder $query) => $employeeId ? $query->where('attendance_logs.employee_id', $employeeId) : $query->whereRaw('1 = 0'))
            ->whereDate('attendances.date', $today)
            ->groupBy('work_locations.id', 'work_locations.name')
            ->orderBy('work_locations.name')
            ->get([
                'work_locations.name as label',
                \DB::raw('COUNT(attendance_logs.id) as total'),
            ]);

        return [
            'labels' => $rows->pluck('label')->values()->all(),
            'counts' => $rows->pluck('total')->map(fn ($total) => (int) $total)->values()->all(),
        ];
    }

    protected function scopeEmployees(Builder $query, ?User $currentUser, ?int $employeeId): Builder
    {
        if (! $currentUser || $currentUser->isAdminHr()) {
            return $query;
        }

        if ($currentUser->isManager()) {
            return $query->where('tenant_id', $currentUser->tenant_id);
        }

        return $employeeId
            ? $query->whereKey($employeeId)
            : $query->whereRaw('1 = 0');
    }

    protected function scopeAttendances(Builder $query, ?User $currentUser, ?int $employeeId): Builder
    {
        if (! $currentUser || $currentUser->isAdminHr()) {
            return $query;
        }

        if ($currentUser->isManager()) {
            return $query->where('tenant_id', $currentUser->tenant_id);
        }

        return $employeeId
            ? $query->where('employee_id', $employeeId)
            : $query->whereRaw('1 = 0');
    }

    protected function scopeLeaves(Builder $query, ?User $currentUser, ?int $employeeId): Builder
    {
        if (! $currentUser || $currentUser->isAdminHr()) {
            return $query;
        }

        if ($currentUser->isManager()) {
            return $query->where('tenant_id', $currentUser->tenant_id);
        }

        return $employeeId
            ? $query->where('employee_id', $employeeId)
            : $query->whereRaw('1 = 0');
    }

    protected function scopeWorkLocations(Builder $query, ?User $currentUser, ?Employee $employee): Builder
    {
        if (! $currentUser || $currentUser->isAdminHr()) {
            return $query;
        }

        if ($currentUser->isManager()) {
            return $query->where('tenant_id', $currentUser->tenant_id);
        }

        return $employee?->work_location_id
            ? $query->whereKey($employee->work_location_id)
            : $query->whereRaw('1 = 0');
    }

    protected function scopePositions(Builder $query, ?User $currentUser, ?Employee $employee): Builder
    {
        if (! $currentUser || $currentUser->isAdminHr()) {
            return $query;
        }

        if ($currentUser->isManager()) {
            return $query->where('tenant_id', $currentUser->tenant_id);
        }

        return $employee?->position_id
            ? $query->whereKey($employee->position_id)
            : $query->whereRaw('1 = 0');
    }

    protected function scopeDepartments(Builder $query, ?User $currentUser, ?Employee $employee): Builder
    {
        if (! $currentUser || $currentUser->isAdminHr()) {
            return $query;
        }

        if ($currentUser->isManager()) {
            return $query->where('tenant_id', $currentUser->tenant_id);
        }

        return $employee?->department_id
            ? $query->whereKey($employee->department_id)
            : $query->whereRaw('1 = 0');
    }
}