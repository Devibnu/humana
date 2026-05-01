@extends('layouts.user_type.auth')

@php
    $formatCurrency = static fn ($amount) => 'Rp '.number_format((float) $amount, 0, ',', '.');
    $nextSortOrder = static fn ($column) => ($filters['sort_by'] ?? 'periode') === $column && ($filters['sort_order'] ?? 'desc') === 'asc' ? 'desc' : 'asc';
    $sortIndicator = static function ($column) use ($filters) {
        if (($filters['sort_by'] ?? 'periode') !== $column) {
            return '';
        }

        return ($filters['sort_order'] ?? 'desc') === 'asc' ? '▲' : '▼';
    };
    $activePayrollFilters = collect([
        ! empty($filters['start_date']) ? 'Mulai: '.$filters['start_date'] : null,
        ! empty($filters['end_date']) ? 'Selesai: '.$filters['end_date'] : null,
        ! empty($filters['tenant_name']) ? 'Tenant: '.$filters['tenant_name'] : null,
        ! empty($filters['employee_name']) ? 'Karyawan: '.$filters['employee_name'] : null,
        ! empty($filters['per_page']) ? 'Per halaman: '.$filters['per_page'] : null,
    ])->filter()->values();
    $hasActivePayrollFilters = $activePayrollFilters->isNotEmpty();
@endphp

@section('content')
<div class="row">
    <div class="col-12">
        <x-flash-messages />
        <div class="card mb-4 mx-4 shadow-xs">
            <div class="card-header pb-0">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                    <div>
                        <h5 class="mb-1">Laporan Payroll</h5>
                        <p class="text-sm text-secondary mb-0">Pantau data pembayaran payroll dengan filter periode, tenant, karyawan, sorting aktif, dan export sesuai tampilan.</p>
                    </div>
                    <div class="d-flex gap-2 flex-wrap align-items-center">
                        @if ($hasActivePayrollFilters)
                            <span class="badge bg-gradient-info">Filter aktif</span>
                            @foreach ($activePayrollFilters as $activePayrollFilter)
                                <span class="badge bg-gradient-light text-dark">{{ $activePayrollFilter }}</span>
                            @endforeach
                        @endif
                        <a href="{{ route('payroll.reports.export', ['format' => 'xlsx'] + request()->all()) }}" class="btn btn-outline-success btn-sm mb-0">
                            <i class="fas fa-file-excel me-1"></i> Export Excel
                        </a>
                        <a href="{{ route('payroll.reports.export', ['format' => 'pdf'] + request()->all()) }}" class="btn btn-outline-danger btn-sm mb-0">
                            <i class="fas fa-file-pdf me-1"></i> Export PDF
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body px-0 pt-0 pb-2">
                <div class="px-4 pt-4">
                    <div class="row g-3 mb-4">
                        <div class="col-xl-3 col-md-6">
                            <div class="card border shadow-xs h-100">
                                <div class="card-body py-3">
                                    <p class="text-xs text-uppercase text-secondary font-weight-bold mb-1">Total Data</p>
                                    <h5 class="mb-0" data-testid="payroll-reports-kpi-total-data">{{ $totals['records'] }}</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card border shadow-xs h-100">
                                <div class="card-body py-3">
                                    <p class="text-xs text-uppercase text-secondary font-weight-bold mb-1">Total Dibayar</p>
                                    <h5 class="mb-0 text-success" data-testid="payroll-reports-kpi-total-paid">{{ $formatCurrency($totals['total_net_salary']) }}</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card border shadow-xs h-100">
                                <div class="card-body py-3">
                                    <p class="text-xs text-uppercase text-secondary font-weight-bold mb-1">Rata-rata Gaji Dibayar</p>
                                    <h5 class="mb-0 text-info" data-testid="payroll-reports-kpi-average-paid">{{ $formatCurrency($totals['average_net_salary']) }}</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card border shadow-xs h-100">
                                <div class="card-body py-3">
                                    <p class="text-xs text-uppercase text-secondary font-weight-bold mb-1">Total Potongan</p>
                                    <h5 class="mb-0 text-danger" data-testid="payroll-reports-kpi-total-deduction">{{ $formatCurrency($totals['total_deduction']) }}</h5>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="border border-radius-xl p-3 mb-4 bg-gray-100">
                        <form method="GET" action="{{ route('payroll.reports') }}" class="row g-3 align-items-end" data-testid="payroll-reports-filter-form">
                            <div class="col-lg-3 col-md-6">
                                <label for="start_date" class="form-label">Tanggal Mulai</label>
                                <input type="date" name="start_date" id="start_date"
                                       value="{{ $filters['start_date'] ?? request('start_date') }}" class="form-control">
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <label for="end_date" class="form-label">Tanggal Akhir</label>
                                <input type="date" name="end_date" id="end_date"
                                       value="{{ $filters['end_date'] ?? request('end_date') }}" class="form-control">
                            </div>
                            <div class="col-lg-2 col-md-6">
                                <label for="tenant_id" class="form-label">Tenant</label>
                                <select name="tenant_id" id="tenant_id" class="form-control">
                                    <option value="">Semua Tenant</option>
                                    @foreach($tenants as $tenant)
                                        <option value="{{ $tenant->id }}"
                                            {{ (string) request('tenant_id', $filters['tenant_id'] ?? '') === (string) $tenant->id ? 'selected' : '' }}>
                                            {{ $tenant->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-2 col-md-6">
                                <label for="employee_name" class="form-label">Nama Karyawan</label>
                                <input type="text" name="employee_name" id="employee_name"
                                       value="{{ $filters['employee_name'] ?? request('employee_name') }}" class="form-control"
                                       placeholder="Cari nama...">
                            </div>
                            <div class="col-lg-2 col-md-6">
                                <label for="per_page" class="form-label">Data per Halaman</label>
                                <select name="per_page" id="per_page" class="form-control" data-testid="payroll-reports-per-page-select">
                                    @foreach([10, 25, 50, 100] as $size)
                                        <option value="{{ $size }}" {{ (int) ($filters['per_page'] ?? 10) === $size ? 'selected' : '' }}>
                                            {{ $size }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <div class="d-flex gap-2 justify-content-lg-end">
                                    <button type="submit" class="btn bg-gradient-dark mb-0">Terapkan</button>
                                    <a href="{{ route('payroll.reports') }}" class="btn btn-light mb-0">Reset</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                @if ($reports->isEmpty())
                    <div class="text-center py-5">
                        <i class="fas fa-file-invoice-dollar fa-3x text-secondary mb-3"></i>
                        @if ($hasActivePayrollFilters)
                            <p class="text-secondary mb-1">Tidak ada data payroll yang cocok dengan filter saat ini.</p>
                            <p class="text-sm text-secondary mb-3">Coba ubah periode, tenant, nama karyawan, atau jumlah data per halaman.</p>
                            <a href="{{ route('payroll.reports') }}" class="btn btn-light btn-sm mb-0">Reset Filter</a>
                        @else
                            <p class="text-secondary mb-0">Belum ada data payroll untuk ditampilkan.</p>
                        @endif
                    </div>
                @else
                    <div class="table-responsive p-0">
                        <table class="table align-items-center mb-0" data-testid="payroll-reports-table">
                            <thead>
                                <tr>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-4 text-start {{ ($filters['sort_by'] ?? 'periode') === 'employee_name' ? 'bg-primary text-white' : '' }}" data-testid="payroll-reports-header-employee-name">
                                        <a href="{{ route('payroll.reports', array_merge(request()->all(), ['sort_by' => 'employee_name', 'sort_order' => $nextSortOrder('employee_name')])) }}" class="text-decoration-none {{ ($filters['sort_by'] ?? 'periode') === 'employee_name' ? 'text-white' : 'text-secondary' }}">
                                            Karyawan
                                            @if($sortIndicator('employee_name') !== '')
                                                <span data-testid="payroll-reports-sort-employee-name">{{ $sortIndicator('employee_name') }}</span>
                                            @endif
                                        </a>
                                    </th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-start {{ ($filters['sort_by'] ?? 'periode') === 'periode' ? 'bg-primary text-white' : '' }}" data-testid="payroll-reports-header-periode">
                                        <a href="{{ route('payroll.reports', array_merge(request()->all(), ['sort_by' => 'periode', 'sort_order' => $nextSortOrder('periode')])) }}" class="text-decoration-none {{ ($filters['sort_by'] ?? 'periode') === 'periode' ? 'text-white' : 'text-secondary' }}">
                                            Periode
                                            @if($sortIndicator('periode') !== '')
                                                <span data-testid="payroll-reports-sort-periode">{{ $sortIndicator('periode') }}</span>
                                            @endif
                                        </a>
                                    </th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-start">Tenant</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-end pe-4 {{ ($filters['sort_by'] ?? 'periode') === 'net_salary' ? 'bg-primary text-white' : '' }}" data-testid="payroll-reports-header-net-salary">
                                        <a href="{{ route('payroll.reports', array_merge(request()->all(), ['sort_by' => 'net_salary', 'sort_order' => $nextSortOrder('net_salary')])) }}" class="text-decoration-none {{ ($filters['sort_by'] ?? 'periode') === 'net_salary' ? 'text-white' : 'text-secondary' }}">
                                            Total Dibayar
                                            @if($sortIndicator('net_salary') !== '')
                                                <span data-testid="payroll-reports-sort-net-salary">{{ $sortIndicator('net_salary') }}</span>
                                            @endif
                                        </a>
                                    </th>
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
                                        $netSalary = $baseSalary + $allowance - $deduction;
                                    @endphp
                                    <tr>
                                        <td class="ps-4 text-start"><h6 class="mb-0 text-sm">{{ $report->employee?->name ?? '-' }}</h6></td>
                                        <td class="text-start"><span class="text-secondary text-sm">{{ $report->period_start && $report->period_end ? $report->period_start->format('Y-m-d').' s/d '.$report->period_end->format('Y-m-d') : 'Belum diatur' }}</span></td>
                                        <td class="text-start"><span class="text-secondary text-sm">{{ $report->employee?->tenant?->name ?? '-' }}</span></td>
                                        <td class="text-end pe-4"><span class="text-sm font-weight-bold">{{ number_format($netSalary, 0, ',', '.') }}</span></td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">Tidak ada data payroll untuk filter ini</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="px-4 pt-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <p class="text-sm text-secondary mb-0" data-testid="payroll-reports-pagination-summary">Menampilkan {{ $reports->firstItem() ?? 0 }}-{{ $reports->lastItem() ?? 0 }} dari {{ $reports->total() }} data</p>
                        <div>
                            {{ $reports->appends(request()->all())->links() }}
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection