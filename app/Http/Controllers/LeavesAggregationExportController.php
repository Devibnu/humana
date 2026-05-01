<?php

namespace App\Http\Controllers;

use App\Exports\LeavesEmployeeAggregationExport;
use App\Models\Employee;
use App\Models\Leave;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;

class LeavesAggregationExportController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:leaves.manage');
    }

    public function employeeXlsx(Request $request, Employee $employee)
    {
        $employee = $this->resolveAccessibleEmployee($request, $employee);
        $leaves = $this->getEmployeeLeaves($employee);

        return Excel::download(
            new LeavesEmployeeAggregationExport(
                $employee,
                $leaves,
                $this->buildMonthlyAggregation($leaves),
                $this->buildAnnualAggregation($leaves)
            ),
            $this->buildFilename($employee),
            ExcelFormat::XLSX
        );
    }

    protected function getEmployeeLeaves(Employee $employee): Collection
    {
        return Leave::query()
            ->where('employee_id', $employee->id)
            ->orderBy('start_date')
            ->orderBy('id')
            ->get();
    }

    protected function buildMonthlyAggregation(Collection $leaves): array
    {
        return $leaves
            ->groupBy(function ($leave) {
                $startDate = $leave->start_date instanceof Carbon
                    ? $leave->start_date
                    : Carbon::parse($leave->start_date);

                return $startDate->format('Y-m');
            })
            ->map(function (Collection $group, string $periodKey) {
                [$year, $month] = array_map('intval', explode('-', $periodKey));

                return [
                    'year' => $year,
                    'month_number' => $month,
                    'month' => Carbon::createFromDate($year, $month, 1)->format('F'),
                    'pending' => $group->where('status', 'pending')->count(),
                    'approved' => $group->where('status', 'approved')->count(),
                    'rejected' => $group->where('status', 'rejected')->count(),
                    'total_days' => (int) $group->sum('duration'),
                ];
            })
            ->sortBy(fn (array $row) => sprintf('%04d-%02d', $row['year'], $row['month_number']))
            ->map(function (array $row) {
                unset($row['month_number']);

                return $row;
            })
            ->values()
            ->all();
    }

    protected function buildAnnualAggregation(Collection $leaves): array
    {
        return $leaves
            ->groupBy(function ($leave) {
                $startDate = $leave->start_date instanceof Carbon
                    ? $leave->start_date
                    : Carbon::parse($leave->start_date);

                return $startDate->format('Y');
            })
            ->map(function (Collection $group, string $year) {
                return [
                    'year' => (int) $year,
                    'pending' => $group->where('status', 'pending')->count(),
                    'approved' => $group->where('status', 'approved')->count(),
                    'rejected' => $group->where('status', 'rejected')->count(),
                    'total_days' => (int) $group->sum('duration'),
                ];
            })
            ->sortBy('year')
            ->values()
            ->all();
    }

    protected function buildFilename(Employee $employee): string
    {
        return 'leaves_'.Str::slug($employee->name, '_').'_'.now()->format('Ymd').'_aggregation.xlsx';
    }

    protected function resolveAccessibleEmployee(Request $request, Employee $employee): Employee
    {
        $currentUser = $request->user() ?? auth()->user();

        if ($currentUser?->isManager()) {
            abort_unless((int) $employee->tenant_id === (int) $currentUser->tenant_id, 404);
        }

        return $employee;
    }
}