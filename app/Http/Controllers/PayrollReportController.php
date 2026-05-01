<?php

namespace App\Http\Controllers;

use App\Exports\PayrollReportsExport;
use App\Models\Payroll;
use App\Models\Tenant;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;

class PayrollReportController extends Controller
{
    public function index(Request $request): View
    {
        $reportQuery = $this->applySorting($this->buildReportQuery($request), $request);
        $perPage = $this->normalizePerPage($request->input('per_page'));

        $reports = $reportQuery
            ->paginate($perPage)
            ->withQueryString();

        $filters = $this->resolveFilters($request);
        $totals = $this->buildTotals($this->buildReportQuery($request));

        return view('payroll.reports', [
            'reports' => $reports,
            'tenants' => Tenant::query()->orderBy('name')->get(['id', 'name']),
            'filters' => $filters,
            'totals' => $totals,
        ]);
    }

    public function export(Request $request, string $format)
    {
        $filters = $this->resolveFilters($request);
        $reports = $this->applySorting($this->buildReportQuery($request), $request)->get();

        if ($format === 'xlsx') {
            return Excel::download(
                new PayrollReportsExport($reports, $filters),
                $this->buildFilename($filters, 'xlsx'),
                ExcelFormat::XLSX
            );
        }

        $pdf = Pdf::loadView('payroll.exports.reports-pdf', [
            'reports' => $reports,
            'filters' => $filters,
            'totals' => $this->summarizeCollection($reports),
        ])->setPaper('a4', 'landscape');

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$this->buildFilename($filters, 'pdf').'"',
        ]);
    }

    private function buildReportQuery(Request $request): Builder
    {
        $startDate = $this->parseDate($request->input('start_date'));
        $endDate = $this->parseDate($request->input('end_date'));
        $tenantId = $request->integer('tenant_id');
        $employeeName = trim((string) $request->input('employee_name', ''));

        return Payroll::query()
            ->with('employee.tenant')
            ->when($startDate, function (Builder $query) use ($startDate) {
                $query->whereDate('period_start', '>=', $startDate->toDateString());
            })
            ->when($endDate, function (Builder $query) use ($endDate) {
                $query->whereDate('period_end', '<=', $endDate->toDateString());
            })
            ->when($tenantId, function (Builder $query) use ($tenantId) {
                $query->whereHas('employee', function (Builder $employeeQuery) use ($tenantId) {
                    $employeeQuery->where('tenant_id', $tenantId);
                });
            })
            ->when($employeeName !== '', function (Builder $query) use ($employeeName) {
                $query->whereHas('employee', function (Builder $employeeQuery) use ($employeeName) {
                    $employeeQuery->where('name', 'like', '%'.$employeeName.'%');
                });
            });
    }

    private function resolveFilters(Request $request): array
    {
        $tenant = null;
        $tenantId = $request->integer('tenant_id');

        if ($tenantId > 0) {
            $tenant = Tenant::query()->find($tenantId);
        }

        return [
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'tenant_id' => $tenantId > 0 ? $tenantId : null,
            'tenant_name' => $tenant?->name,
            'employee_name' => trim((string) $request->input('employee_name', '')),
            'sort_by' => $this->normalizeSortBy($request->input('sort_by')),
            'sort_order' => $this->normalizeSortOrder($request->input('sort_order')),
            'per_page' => $this->normalizePerPage($request->input('per_page')),
        ];
    }

    private function buildTotals(Builder $query): array
    {
        return $this->summarizeCollection($query->get());
    }

    private function summarizeCollection($reports): array
    {
        $totalRecords = $reports->count();
        $totalNetSalary = $reports->sum(fn (Payroll $payroll) => $this->calculateNetSalary($payroll));
        $totalDeduction = $reports->sum(fn (Payroll $payroll) => $this->calculateDeduction($payroll));
        $activeTenants = $reports
            ->pluck('employee.tenant_id')
            ->filter()
            ->unique()
            ->count();
        $averageNetSalary = $totalRecords > 0 ? $totalNetSalary / $totalRecords : 0;

        return [
            'records' => $totalRecords,
            'total_net_salary' => $totalNetSalary,
            'average_net_salary' => $averageNetSalary,
            'total_deduction' => $totalDeduction,
            'active_tenants' => $activeTenants,
        ];
    }

    private function applySorting(Builder $query, Request $request): Builder
    {
        $sortBy = $this->normalizeSortBy($request->input('sort_by'));
        $sortOrder = $this->normalizeSortOrder($request->input('sort_order'));

        if ($sortBy === 'employee_name') {
            return $query
                ->leftJoin('employees', 'employees.id', '=', 'payrolls.employee_id')
                ->select('payrolls.*')
                ->orderBy('employees.name', $sortOrder)
                ->orderByDesc('payrolls.id');
        }

        if ($sortBy === 'periode') {
            return $query
                ->orderBy('period_start', $sortOrder)
                ->orderBy('period_end', $sortOrder)
                ->orderByDesc('id');
        }

        if ($sortBy === 'net_salary') {
            return $query
                ->orderByRaw(
                    '(COALESCE(monthly_salary, daily_wage, 0) + COALESCE(allowance_transport, 0) + COALESCE(allowance_meal, 0) + COALESCE(allowance_health, 0) + COALESCE(overtime_pay, 0) - COALESCE(deduction_tax, 0) - COALESCE(deduction_bpjs, 0) - COALESCE(deduction_loan, 0) - COALESCE(deduction_attendance, 0)) '.$sortOrder
                )
                ->orderByDesc('id');
        }

        return $query
            ->orderByDesc('period_start')
            ->orderByDesc('id');
    }

    private function normalizeSortBy(mixed $sortBy): string
    {
        $allowed = ['employee_name', 'periode', 'net_salary'];

        return in_array($sortBy, $allowed, true) ? $sortBy : 'periode';
    }

    private function normalizeSortOrder(mixed $sortOrder): string
    {
        return $sortOrder === 'asc' ? 'asc' : 'desc';
    }

    private function normalizePerPage(mixed $perPage): int
    {
        $allowed = [10, 25, 50, 100];
        $value = is_numeric($perPage) ? (int) $perPage : 10;

        return in_array($value, $allowed, true) ? $value : 10;
    }

    private function calculateAllowance(Payroll $payroll): float
    {
        return (float) ($payroll->allowance_transport ?? 0)
            + (float) ($payroll->allowance_meal ?? 0)
            + (float) ($payroll->allowance_health ?? 0)
            + (float) ($payroll->overtime_pay ?? 0);
    }

    private function calculateDeduction(Payroll $payroll): float
    {
        return (float) ($payroll->deduction_tax ?? 0)
            + (float) ($payroll->deduction_bpjs ?? 0)
            + (float) ($payroll->deduction_loan ?? 0)
            + (float) ($payroll->deduction_attendance ?? 0);
    }

    private function calculateBaseSalary(Payroll $payroll): float
    {
        return (float) ($payroll->monthly_salary ?? $payroll->daily_wage ?? 0);
    }

    private function calculateNetSalary(Payroll $payroll): float
    {
        return $this->calculateBaseSalary($payroll)
            + $this->calculateAllowance($payroll)
            - $this->calculateDeduction($payroll);
    }

    private function parseDate(?string $date): ?Carbon
    {
        if (! $date) {
            return null;
        }

        try {
            return Carbon::parse($date);
        } catch (\Throwable) {
            return null;
        }
    }

    private function buildFilename(array $filters, string $extension): string
    {
        $parts = ['payroll_reports'];

        if (! empty($filters['tenant_name'])) {
            $parts[] = Str::slug((string) $filters['tenant_name'], '-');
        }

        if (! empty($filters['start_date'])) {
            $parts[] = $filters['start_date'];
        }

        if (! empty($filters['end_date'])) {
            $parts[] = $filters['end_date'];
        }

        $parts[] = now()->format('Ymd');

        return implode('_', $parts).'.'.$extension;
    }
}