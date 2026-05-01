@extends('layouts.user_type.auth')

@section('content')

<div class="row">
    <div class="col-12 mb-4">
        <div class="card mx-4 shadow-xs">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                    <div>
                        <p class="text-sm mb-1 text-uppercase font-weight-bold">Audit Log Resolusi Anomali Cuti</p>
                        <h4 class="mb-1">Dashboard Audit Log Resolusi</h4>
                        <p class="text-sm text-secondary mb-0">Tinjau jejak siapa yang menyelesaikan anomali cuti, kapan dilakukan, dan tindakan apa yang dipilih untuk kebutuhan audit HR.</p>
                        <div class="d-flex flex-wrap gap-2 align-items-center mt-2">
                            <span class="badge {{ $isTenantScoped ? 'bg-gradient-warning text-dark' : 'bg-gradient-dark' }}" data-testid="leave-anomaly-resolution-audit-tenant-scope-badge">
                                Tenant: {{ $tenantScopeLabel }}
                            </span>
                            <span class="text-xs text-secondary">{{ $tenantScopeDescription }}</span>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap gap-2 align-items-end justify-content-end">
                        <a href="{{ route('leaves.anomalies.resolutions', array_filter(['tenant_id' => $tenant?->id, 'month' => $selectedMonth, 'year' => $selectedYear])) }}" class="btn btn-sm btn-outline-dark mb-0">Kembali ke Dashboard Resolusi</a>
                        <a href="{{ route('leaves.anomalies.resolutions.log.export.pdf', array_filter(['tenant_id' => $tenant?->id, 'month' => $selectedMonth, 'year' => $selectedYear, 'search' => $search])) }}"
                            class="btn btn-sm btn-outline-success mb-0"
                            data-testid="leave-anomaly-resolution-audit-export-pdf">
                            Export Audit Log PDF
                        </a>
                        <a href="{{ route('leaves.anomalies.resolutions.log.export.xlsx', array_filter(['tenant_id' => $tenant?->id, 'month' => $selectedMonth, 'year' => $selectedYear, 'search' => $search])) }}"
                            class="btn btn-sm bg-gradient-success mb-0"
                            data-testid="leave-anomaly-resolution-audit-export-xlsx">
                            Export Audit Log XLSX
                        </a>
                        <form method="GET" action="{{ route('leaves.anomalies.resolutions.log') }}" class="row g-2 align-items-end justify-content-end" data-testid="leave-anomaly-resolution-audit-filter-form">
                            @if ($canSwitchTenant)
                                <div class="col-12 col-md-auto">
                                    <label for="tenant_id" class="form-label text-xs text-uppercase font-weight-bold mb-1">Tenant</label>
                                    <select name="tenant_id" id="tenant_id" onchange="this.form.submit()" class="form-select form-select-sm" data-testid="leave-anomaly-resolution-audit-tenant-filter">
                                        @foreach ($tenants as $tenantOption)
                                            <option value="{{ $tenantOption->id }}" @selected((int) $tenantOption->id === (int) ($tenant?->id))>{{ $tenantOption->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                            <div class="col-6 col-md-auto">
                                <label for="month" class="form-label text-xs text-uppercase font-weight-bold mb-1">Bulan</label>
                                <select name="month" id="month" onchange="this.form.submit()" class="form-select form-select-sm" data-testid="leave-anomaly-resolution-audit-month-filter">
                                    @foreach ($monthOptions as $monthValue => $monthLabel)
                                        <option value="{{ $monthValue }}" @selected((int) $monthValue === (int) $selectedMonth)>{{ $monthLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-6 col-md-auto">
                                <label for="year" class="form-label text-xs text-uppercase font-weight-bold mb-1">Tahun</label>
                                <select name="year" id="year" onchange="this.form.submit()" class="form-select form-select-sm" data-testid="leave-anomaly-resolution-audit-year-filter">
                                    @foreach ($yearOptions as $yearOption)
                                        <option value="{{ $yearOption }}" @selected((int) $yearOption === (int) $selectedYear)>{{ $yearOption }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-md-auto">
                                <label for="search" class="form-label text-xs text-uppercase font-weight-bold mb-1">Cari Karyawan</label>
                                <input type="text" name="search" id="search" value="{{ $search }}" class="form-control form-control-sm" placeholder="Nama karyawan" data-testid="leave-anomaly-resolution-audit-search">
                            </div>
                            <div class="col-12 col-md-auto">
                                <button type="submit" class="btn btn-sm bg-gradient-dark mb-0">Terapkan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12 mb-4">
        <div class="card mx-4 shadow-xs">
            <div class="card-header pb-0">
                <h6 class="mb-0">Log Resolusi</h6>
                <p class="text-sm mb-0">Menampilkan {{ $summary['total'] ?? 0 }} log untuk {{ $monthOptions[$selectedMonth] ?? '-' }} {{ $selectedYear }}.</p>
            </div>
            <div class="card-body">
                <div class="table-responsive" data-testid="leave-anomaly-resolution-audit-table-container">
                    <table class="table align-items-center mb-0">
                        <thead>
                            <tr>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Employee</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Jenis Anomali</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Deskripsi</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Periode</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Manager</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Tindakan</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Catatan</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Timestamp</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($logs as $log)
                                <tr title="{{ $log['resolution_tooltip'] }}" data-bs-toggle="tooltip">
                                    <td class="text-sm font-weight-bold">{{ $log['employee'] }}</td>
                                    <td>
                                        <div class="d-flex flex-column gap-2">
                                            <span class="badge {{ $log['type_key'] === 'lonjakan' ? 'bg-gradient-danger' : ($log['type_key'] === 'pola_berulang' ? 'bg-gradient-warning text-dark' : 'bg-gradient-info') }} align-self-start">
                                                {{ $log['jenis_anomali'] }}
                                            </span>
                                            <span class="badge bg-gradient-success align-self-start">Resolved</span>
                                        </div>
                                    </td>
                                    <td class="text-sm text-secondary">{{ $log['deskripsi'] }}</td>
                                    <td class="text-sm">{{ $log['periode'] }}</td>
                                    <td class="text-sm">{{ $log['manager'] }}</td>
                                    <td class="text-sm">{{ $log['tindakan'] }}</td>
                                    <td class="text-sm text-secondary"><span title="{{ $log['catatan'] }}">{{ \Illuminate\Support\Str::limit($log['catatan'], 80) }}</span></td>
                                    <td class="text-sm">{{ $log['timestamp'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-5 text-secondary">Belum ada audit log resolusi untuk filter yang dipilih.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection