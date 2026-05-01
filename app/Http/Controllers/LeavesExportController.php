<?php

namespace App\Http\Controllers;

use App\Exports\LeavesEmployeeCsvExport;
use App\Exports\LeavesEmployeeExport;
use App\Models\Employee;
use App\Models\Leave;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;

class LeavesExportController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:leaves.manage');
    }

    public function employeeCsv(Request $request, Employee $employee)
    {
        $employee = $this->resolveAccessibleEmployee($request, $employee);
        $leaves = $this->getEmployeeLeavesForExport($employee);
        $summary = $this->buildSummary($leaves);

        return Excel::download(
            new LeavesEmployeeCsvExport($employee, $leaves, $summary),
            $this->buildFilename($employee, 'csv'),
            ExcelFormat::CSV
        );
    }

    public function employeeXlsx(Request $request, Employee $employee)
    {
        $employee = $this->resolveAccessibleEmployee($request, $employee);
        $leaves = $this->getEmployeeLeavesForExport($employee);
        $summary = $this->buildSummary($leaves);

        return Excel::download(
            new LeavesEmployeeExport($employee, $leaves, $summary),
            $this->buildFilename($employee, 'xlsx'),
            ExcelFormat::XLSX
        );
    }

    protected function getEmployeeLeavesForExport(Employee $employee): Collection
    {
        return Leave::query()
            ->where('employee_id', $employee->id)
            ->orderBy('start_date')
            ->orderBy('id')
            ->get();
    }

    protected function buildSummary(Collection $leaves): array
    {
        $summary = [
            'pending_count' => $leaves->where('status', 'pending')->count(),
            'pending_days' => (int) $leaves->where('status', 'pending')->sum('duration'),
            'approved_count' => $leaves->where('status', 'approved')->count(),
            'approved_days' => (int) $leaves->where('status', 'approved')->sum('duration'),
            'rejected_count' => $leaves->where('status', 'rejected')->count(),
            'rejected_days' => (int) $leaves->where('status', 'rejected')->sum('duration'),
        ];

        $summary['total_days'] = (int) ($summary['pending_days'] + $summary['approved_days'] + $summary['rejected_days']);

        return $summary;
    }

    protected function buildFilename(Employee $employee, string $extension): string
    {
        return 'leaves_'.Str::slug($employee->name, '_').'_'.now()->format('Ymd').'.'.$extension;
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