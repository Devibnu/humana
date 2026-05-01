@php
    $formatCurrency = static fn ($amount) => 'Rp '.number_format((float) $amount, 0, ',', '.');
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Payroll</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111827; }
        h1 { font-size: 18px; margin-bottom: 6px; }
        p { margin: 0 0 6px; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { border: 1px solid #d1d5db; padding: 8px; }
        th { background: #f3f4f6; text-align: left; }
        .text-end { text-align: right; }
        .summary { margin-top: 8px; }
    </style>
</head>
<body>
    <h1>Laporan Payroll</h1>
    <p>Tenant: {{ $filters['tenant_name'] ?? 'Semua Tenant' }}</p>
    <p>Periode: {{ $filters['start_date'] ?: '-' }} sampai {{ $filters['end_date'] ?: '-' }}</p>
    <p class="summary">Total data: {{ $totals['records'] }} | Total dibayar: {{ $formatCurrency($totals['total_net_salary']) }}</p>

    <table>
        <thead>
            <tr>
                <th>Karyawan</th>
                <th>Periode</th>
                <th>Tenant</th>
                <th>Gaji Pokok</th>
                <th>Tunjangan</th>
                <th>Potongan</th>
                <th>Total Dibayar</th>
            </tr>
        </thead>
        <tbody>
            @forelse($reports as $report)
                @php
                    $baseSalary = (float) ($report->monthly_salary ?? $report->daily_wage ?? 0);
                    $allowance = (float) ($report->allowance_transport ?? 0)
                        + (float) ($report->allowance_meal ?? 0)
                        + (float) ($report->allowance_health ?? 0)
                        + (float) ($report->overtime_pay ?? 0);
                    $deduction = (float) ($report->deduction_tax ?? 0)
                        + (float) ($report->deduction_bpjs ?? 0)
                        + (float) ($report->deduction_loan ?? 0)
                        + (float) ($report->deduction_attendance ?? 0);
                @endphp
                <tr>
                    <td>{{ $report->employee?->name ?? '-' }}</td>
                    <td>{{ $report->period_start && $report->period_end ? $report->period_start->format('Y-m-d').' s/d '.$report->period_end->format('Y-m-d') : 'Belum diatur' }}</td>
                    <td>{{ $report->employee?->tenant?->name ?? '-' }}</td>
                    <td class="text-end">{{ number_format($baseSalary, 0, ',', '.') }}</td>
                    <td class="text-end">{{ number_format($allowance, 0, ',', '.') }}</td>
                    <td class="text-end">{{ number_format($deduction, 0, ',', '.') }}</td>
                    <td class="text-end">{{ number_format($baseSalary + $allowance - $deduction, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">Tidak ada data payroll untuk filter ini</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>