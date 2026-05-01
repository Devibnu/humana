<?php

namespace App\Exports;

use App\Models\Payroll;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class PayrollReportsExport implements FromCollection, WithHeadings, WithTitle
{
    public function __construct(
        protected Collection $reports,
        protected array $filters = []
    ) {
    }

    public function collection(): Collection
    {
        return $this->reports->map(function (Payroll $report) {
            $baseSalary = (float) ($report->monthly_salary ?? $report->daily_wage ?? 0);
            $allowance = (float) ($report->allowance_transport ?? 0)
                + (float) ($report->allowance_meal ?? 0)
                + (float) ($report->allowance_health ?? 0)
                + (float) ($report->overtime_pay ?? 0);
            $deduction = (float) ($report->deduction_tax ?? 0)
                + (float) ($report->deduction_bpjs ?? 0)
                + (float) ($report->deduction_loan ?? 0)
                + (float) ($report->deduction_attendance ?? 0);

            return [
                'employee_name' => $report->employee?->name,
                'period' => $report->period_start && $report->period_end
                    ? $report->period_start->format('Y-m-d').' s/d '.$report->period_end->format('Y-m-d')
                    : 'Belum diatur',
                'tenant' => $report->employee?->tenant?->name,
                'base_salary' => $baseSalary,
                'allowance' => $allowance,
                'deduction' => $deduction,
                'net_salary' => $baseSalary + $allowance - $deduction,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Karyawan',
            'Periode',
            'Tenant',
            'Gaji Pokok',
            'Tunjangan',
            'Potongan',
            'Total Dibayar',
        ];
    }

    public function title(): string
    {
        return 'Laporan Payroll';
    }
}