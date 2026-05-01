@extends('layouts.user_type.auth')

@section('content')

@php($activePayrollFilters = collect([
    $search !== '' ? 'Pencarian: '.$search : null,
    $selectedTenantName ? 'Tenant: '.$selectedTenantName : null,
    ($selectedSortBy !== 'latest_period' || $selectedSortDirection !== 'desc') && isset($sortOptions[$selectedSortBy])
        ? 'Urutan: '.$sortOptions[$selectedSortBy].' ('.strtoupper($selectedSortDirection).')'
        : null,
])->filter()->values())
@php($hasActivePayrollFilters = $search !== '' || $selectedTenantId !== null)

<div class="row">
    <div class="col-12">
        <x-flash-messages />
        <div class="card mb-4 mx-4 shadow-xs">
            <div class="card-header pb-0">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                    <div>
                        <h5 class="mb-1">Daftar Payroll</h5>
                        <p class="text-sm text-secondary mb-0">Kelola payroll dengan pola kerja yang lebih cepat: ringkasan nominal, filter tenant, pencarian karyawan, dan akses laporan dalam satu halaman.</p>
                    </div>
                    <div class="d-flex gap-2 flex-wrap align-items-center">
                        @if ($activePayrollFilters->isNotEmpty())
                            <span class="badge bg-gradient-info">Filter aktif</span>
                            @foreach ($activePayrollFilters as $activePayrollFilter)
                                <span class="badge bg-gradient-light text-dark">{{ $activePayrollFilter }}</span>
                            @endforeach
                        @endif
                        <a href="{{ route('payroll.reports') }}" class="btn btn-outline-dark btn-sm mb-0" data-testid="btn-open-payroll-reports">
                            <i class="fas fa-chart-line me-1"></i> Laporan Payroll
                        </a>
                        <a href="{{ route('payroll.create') }}" class="btn bg-gradient-primary btn-sm mb-0" data-testid="btn-open-payroll-create">
                            <i class="fas fa-plus me-1"></i> Input Payroll
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body px-0 pt-0 pb-2">
                <div class="px-4 pt-4">
                    <div class="row g-3 mb-4">
                        <div class="col-xl-3 col-md-6">
                            <div class="card border shadow-xs h-100" data-testid="payroll-summary-total">
                                <div class="card-body py-3">
                                    <p class="text-xs text-uppercase text-secondary font-weight-bold mb-1">Total Payroll</p>
                                    <h5 class="mb-0">{{ $summary['total'] }}</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card border shadow-xs h-100" data-testid="payroll-summary-monthly-salary">
                                <div class="card-body py-3">
                                    <p class="text-xs text-uppercase text-secondary font-weight-bold mb-1">Akumulasi Gaji Bulanan</p>
                                    <h5 class="mb-0 text-dark">Rp {{ number_format((float) $summary['monthly_salary_total'], 0, ',', '.') }}</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card border shadow-xs h-100" data-testid="payroll-summary-overtime">
                                <div class="card-body py-3">
                                    <p class="text-xs text-uppercase text-secondary font-weight-bold mb-1">Total Lembur Payroll</p>
                                    <h5 class="mb-0 text-success">Rp {{ number_format((float) $summary['overtime_total'], 0, ',', '.') }}</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card border shadow-xs h-100" data-testid="payroll-summary-deductions">
                                <div class="card-body py-3">
                                    <p class="text-xs text-uppercase text-secondary font-weight-bold mb-1">Total Potongan</p>
                                    <h5 class="mb-0 text-danger">Rp {{ number_format((float) $summary['deduction_total'], 0, ',', '.') }}</h5>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="border border-radius-xl p-3 mb-4 bg-gray-100">
                        <form action="{{ route('payroll.index') }}" method="GET" class="row g-3 align-items-end" data-testid="payroll-filter-form">
                            <div class="col-lg-4 col-md-6">
                                <label for="search" class="form-label">Cari Payroll</label>
                                <div class="input-group">
                                    <span class="input-group-text text-body"><i class="fas fa-search" aria-hidden="true"></i></span>
                                    <input
                                        type="text"
                                        id="search"
                                        name="search"
                                        class="form-control"
                                        value="{{ $search }}"
                                        placeholder="Cari nama karyawan, kode, atau tenant"
                                        data-testid="payroll-search-input"
                                    >
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <label for="tenant_id" class="form-label">Tenant</label>
                                <select name="tenant_id" id="tenant_id" class="form-control" data-testid="payroll-tenant-filter">
                                    <option value="">Semua Tenant</option>
                                    @foreach ($tenants as $tenant)
                                        <option value="{{ $tenant->id }}" @selected($selectedTenantId === $tenant->id)>{{ $tenant->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <label for="sort_by" class="form-label">Urutkan</label>
                                <select name="sort_by" id="sort_by" class="form-control" data-testid="payroll-sort-filter">
                                    @foreach ($sortOptions as $sortValue => $sortLabel)
                                        <option value="{{ $sortValue }}" @selected($selectedSortBy === $sortValue)>{{ $sortLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-2 col-md-6">
                                <label for="sort_direction" class="form-label">Arah</label>
                                <select name="sort_direction" id="sort_direction" class="form-control" data-testid="payroll-sort-direction-filter">
                                    <option value="desc" @selected($selectedSortDirection === 'desc')>DESC</option>
                                    <option value="asc" @selected($selectedSortDirection === 'asc')>ASC</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <div class="d-flex gap-2 justify-content-lg-end">
                                    <button type="submit" class="btn bg-gradient-dark mb-0" data-testid="btn-apply-payroll-filter">
                                        <i class="fas fa-filter me-1"></i> Terapkan
                                    </button>
                                    <a href="{{ route('payroll.index') }}" class="btn btn-light mb-0" data-testid="btn-reset-payroll-filter">
                                        Reset
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                @if ($payrolls->isEmpty())
                    <div class="text-center py-5" data-testid="{{ $hasActivePayrollFilters ? 'payroll-filter-empty-state' : 'payroll-empty-state' }}">
                        <i class="fas fa-money-check-alt fa-3x text-secondary mb-3"></i>
                        @if ($hasActivePayrollFilters)
                            <p class="text-secondary mb-1">Tidak ada payroll yang cocok dengan filter saat ini.</p>
                            <p class="text-sm text-secondary mb-3">Ubah tenant, kata kunci, atau urutan untuk melihat payroll lain.</p>
                            <a href="{{ route('payroll.index') }}" class="btn btn-light btn-sm mb-0">Reset Filter</a>
                        @else
                            <p class="text-secondary mb-0">Belum ada data payroll. Mulai dari input payroll pertama untuk karyawan aktif.</p>
                        @endif
                    </div>
                @else
                    <div class="table-responsive p-0">
                        <table class="table align-items-center mb-0" data-testid="payroll-table">
                            <thead>
                                <tr>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-4 text-start">Karyawan</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-start">Periode</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-start">Kompensasi</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-start">Potongan</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-start">Catatan</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-start">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($payrolls as $payroll)
                                    <tr>
                                        <td class="ps-4 text-start">
                                            <div class="py-2">
                                                <h6 class="mb-0 text-sm">{{ $payroll->employee?->name ?? '-' }}</h6>
                                                <p class="text-xs text-secondary mb-0">{{ $payroll->employee?->employee_code ?? 'Tanpa kode karyawan' }}</p>
                                                <p class="text-xs text-secondary mb-0">{{ $payroll->employee?->tenant?->name ?? 'Tenant belum terhubung' }}</p>
                                            </div>
                                        </td>
                                        <td class="text-start">
                                            <div class="py-2">
                                                <p class="text-sm font-weight-bold mb-1">{{ $payroll->period_start && $payroll->period_end ? $payroll->period_start->format('d M Y').' - '.$payroll->period_end->format('d M Y') : 'Periode belum diatur' }}</p>
                                                <p class="text-xs text-secondary mb-0">Diurutkan untuk review dan audit payroll.</p>
                                            </div>
                                        </td>
                                        <td class="text-start">
                                            <div class="py-2">
                                                <p class="text-sm font-weight-bold mb-1">Bulanan: {{ $payroll->monthly_salary !== null ? 'Rp '.number_format((float) $payroll->monthly_salary, 0, ',', '.') : '-' }}</p>
                                                <p class="text-xs text-secondary mb-1">Harian: {{ $payroll->daily_wage !== null ? 'Rp '.number_format((float) $payroll->daily_wage, 0, ',', '.') : '-' }}</p>
                                                <p class="text-xs text-secondary mb-1">Transport/Makan/Kesehatan: {{ 'Rp '.number_format((float) (($payroll->allowance_transport ?? 0) + ($payroll->allowance_meal ?? 0) + ($payroll->allowance_health ?? 0)), 0, ',', '.') }}</p>
                                                <span class="badge bg-gradient-success">Lembur {{ $payroll->overtime_pay !== null ? 'Rp '.number_format((float) $payroll->overtime_pay, 0, ',', '.') : '-' }}</span>
                                            </div>
                                        </td>
                                        <td class="text-start">
                                            <div class="py-2">
                                                <p class="text-sm font-weight-bold mb-1">Pajak: {{ $payroll->deduction_tax !== null ? 'Rp '.number_format((float) $payroll->deduction_tax, 0, ',', '.') : '-' }}</p>
                                                <p class="text-xs text-secondary mb-1">BPJS: {{ $payroll->deduction_bpjs !== null ? 'Rp '.number_format((float) $payroll->deduction_bpjs, 0, ',', '.') : '-' }}</p>
                                                <p class="text-xs text-secondary mb-1">Pinjaman: {{ $payroll->deduction_loan !== null ? 'Rp '.number_format((float) $payroll->deduction_loan, 0, ',', '.') : '-' }}</p>
                                                <span class="badge bg-gradient-danger">Absensi {{ $payroll->deduction_attendance !== null ? 'Rp '.number_format((float) $payroll->deduction_attendance, 0, ',', '.') : '-' }}</span>
                                            </div>
                                        </td>
                                        <td class="text-start">
                                            <div class="py-2" style="max-width: 260px;">
                                                <p class="text-sm mb-1">{{ $payroll->overtime_note ? \Illuminate\Support\Str::limit($payroll->overtime_note, 80) : 'Tidak ada catatan lembur.' }}</p>
                                                <p class="text-xs text-secondary mb-0">{{ $payroll->deduction_attendance_note ? \Illuminate\Support\Str::limit($payroll->deduction_attendance_note, 80) : 'Tidak ada catatan potongan absensi.' }}</p>
                                            </div>
                                        </td>
                                        <td class="text-start">
                                            <div class="d-flex align-items-center gap-3 py-2">
                                                <a href="{{ route('payroll.show', $payroll) }}" class="mx-1" data-bs-toggle="tooltip" title="Lihat detail">
                                                    <i class="fas fa-eye text-info"></i>
                                                </a>
                                                <a href="{{ route('payroll.edit', $payroll) }}" class="mx-1" data-bs-toggle="tooltip" title="Edit payroll">
                                                    <i class="fas fa-edit text-secondary"></i>
                                                </a>
                                                <form action="{{ route('payroll.destroy', $payroll) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus data payroll ini?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-link text-danger p-0 m-0" title="Hapus payroll">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="px-4 pt-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <p class="text-sm text-secondary mb-0">Menampilkan {{ $payrolls->count() }} dari total {{ $payrolls->total() }} payroll.</p>
                        {{ $payrolls->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection
