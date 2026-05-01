<?php

namespace App\Http\Controllers;

use App\Exports\AttendanceEmployeeCsvExport;
use App\Exports\AttendanceEmployeeExport;
use App\Exports\AttendancesCsvExport;
use App\Exports\AttendancesExport;
use App\Models\Attendance;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;

class AttendancesExportController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:attendances.manage');
    }

    public function csv(Request $request)
    {
        $attendances = $this->getAttendancesForExport($request);
        $summary = $this->getSummary($attendances);

        return Excel::download(
            new AttendancesCsvExport($attendances, [
                ...$summary,
                'start_date' => $request->input('start_date'),
                'end_date' => $request->input('end_date'),
            ]),
            $this->buildFilename($request, 'csv'),
            ExcelFormat::CSV
        );
    }

    public function xlsx(Request $request)
    {
        $attendances = $this->getAttendancesForExport($request);
        $summary = $this->getSummary($attendances);

        return Excel::download(
            new AttendancesExport($attendances, [
                ...$summary,
                'start_date' => $request->input('start_date'),
                'end_date' => $request->input('end_date'),
            ]),
            $this->buildFilename($request, 'xlsx'),
            ExcelFormat::XLSX
        );
    }

    public function employeeCsv(Request $request, Employee $employee)
    {
        $employee = $this->resolveAccessibleEmployee($request, $employee);
        $attendances = $this->getEmployeeAttendancesForExport($employee);
        $summary = $this->getSummary($attendances);
        $totalMinutes = $this->getTotalWorkMinutes($attendances);

        return Excel::download(
            new AttendanceEmployeeCsvExport($employee, $attendances, [
                ...$summary,
                'total_work_minutes' => $totalMinutes,
                'total_work_hours_label' => $this->formatMinutesAsHours($totalMinutes),
            ]),
            $this->buildEmployeeFilename($employee, 'csv'),
            ExcelFormat::CSV
        );
    }

    public function employeeXlsx(Request $request, Employee $employee)
    {
        $employee = $this->resolveAccessibleEmployee($request, $employee);
        $attendances = $this->getEmployeeAttendancesForExport($employee);
        $summary = $this->getSummary($attendances);
        $totalMinutes = $this->getTotalWorkMinutes($attendances);

        return Excel::download(
            new AttendanceEmployeeExport($employee, $attendances, [
                ...$summary,
                'total_work_minutes' => $totalMinutes,
                'total_work_hours_label' => $this->formatMinutesAsHours($totalMinutes),
            ]),
            $this->buildEmployeeFilename($employee, 'xlsx'),
            ExcelFormat::XLSX
        );
    }

    protected function getAttendancesForExport(Request $request): Collection
    {
        $currentUser = $request->user() ?? auth()->user();
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        return Attendance::query()
            ->with(['employee', 'employee.workLocation', 'attendanceLog.workLocation'])
            ->when($currentUser?->isManager(), fn ($query) => $query->where('tenant_id', $currentUser->tenant_id))
            ->when($startDate, fn ($query) => $query->whereDate('date', '>=', $startDate))
            ->when($endDate, fn ($query) => $query->whereDate('date', '<=', $endDate))
            ->orderBy('date')
            ->orderBy('id')
            ->get();
    }

    protected function getSummary(Collection $attendances): array
    {
        return [
            'present_count' => $attendances->filter(fn ($attendance) => in_array($attendance->status, ['present', 'late'], true))->count(),
            'leave_count' => $attendances->where('status', 'leave')->count(),
            'sick_count' => $attendances->where('status', 'sick')->count(),
            'absent_count' => $attendances->where('status', 'absent')->count(),
        ];
    }

    protected function getEmployeeAttendancesForExport(Employee $employee): Collection
    {
        return Attendance::query()
            ->with(['employee', 'employee.workLocation', 'attendanceLog.workLocation'])
            ->where('employee_id', $employee->id)
            ->orderBy('date')
            ->orderBy('id')
            ->get();
    }

    protected function getTotalWorkMinutes(Collection $attendances): int
    {
        return $attendances->sum(function ($attendance) {
            if (! $attendance->check_in || ! $attendance->check_out) {
                return 0;
            }

            [$checkInHour, $checkInMinute] = array_map('intval', explode(':', $attendance->check_in));
            [$checkOutHour, $checkOutMinute] = array_map('intval', explode(':', $attendance->check_out));

            return max(0, (($checkOutHour * 60) + $checkOutMinute) - (($checkInHour * 60) + $checkInMinute));
        });
    }

    protected function buildFilename(Request $request, string $format): string
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        if ($startDate && $endDate) {
            if ($startDate === $endDate) {
                return 'attendances_'.str_replace('-', '', $startDate).'.'.$format;
            }

            return 'attendances_'.str_replace('-', '', $startDate).'_'.str_replace('-', '', $endDate).'.'.$format;
        }

        if ($startDate) {
            return 'attendances_'.str_replace('-', '', $startDate).'.'.$format;
        }

        return 'attendances_'.now()->format('Ymd').'.'.$format;
    }

    protected function buildEmployeeFilename(Employee $employee, string $format): string
    {
        return 'attendance_'.Str::slug($employee->name, '_').'_'.now()->format('Ymd').'.'.$format;
    }

    protected function resolveAccessibleEmployee(Request $request, Employee $employee): Employee
    {
        $currentUser = $request->user() ?? auth()->user();

        if ($currentUser?->isManager()) {
            abort_unless((int) $employee->tenant_id === (int) $currentUser->tenant_id, 404);
        }

        return $employee;
    }

    protected function formatMinutesAsHours(int $totalMinutes): string
    {
        $hours = intdiv($totalMinutes, 60);
        $minutes = $totalMinutes % 60;

        return sprintf('%d jam %02d menit', $hours, $minutes);
    }
}