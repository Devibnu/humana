@extends('layouts.user_type.auth')

@php
    $nextSortOrder = static fn ($column) => ($filters['sort_by'] ?? 'waktu_mulai') === $column && ($filters['sort_order'] ?? 'desc') === 'asc' ? 'desc' : 'asc';
    $activeSortLabel = $sortLabels[$filters['sort_by'] ?? 'waktu_mulai'] ?? 'Tanggal Lembur';
    $sortIndicator = static function ($column) use ($filters) {
        if (($filters['sort_by'] ?? 'waktu_mulai') !== $column) {
            return '';
        }

        return ($filters['sort_order'] ?? 'desc') === 'asc' ? '▲' : '▼';
    };
    $activeReportFilters = collect([
        ! empty($filters['combined_preset']) && isset($quickCombinedPresets[$filters['combined_preset']]) ? [
            'label' => 'Preset Kombinasi: '.$quickCombinedPresets[$filters['combined_preset']],
            'remove' => route('lembur.reports', request()->except(['page', 'combined_preset', 'preset', 'status_preset', 'start_date', 'end_date', 'status'])),
        ] : null,
        ! empty($filters['preset']) && isset($quickFilterPresets[$filters['preset']]) ? [
            'label' => 'Preset: '.$quickFilterPresets[$filters['preset']],
            'remove' => route('lembur.reports', request()->except(['page', 'combined_preset', 'preset', 'start_date', 'end_date'])),
        ] : null,
        ! empty($filters['status_preset']) && isset($quickStatusPresets[$filters['status_preset']]) ? [
            'label' => 'Preset Status: '.$quickStatusPresets[$filters['status_preset']],
            'remove' => route('lembur.reports', request()->except(['page', 'combined_preset', 'status_preset', 'status'])),
        ] : null,
        $filters['search'] !== '' ? [
            'label' => 'Pencarian: '.$filters['search'],
            'remove' => route('lembur.reports', request()->except(['page', 'search'])),
        ] : null,
        $filters['start_date'] ? [
            'label' => 'Mulai: '.$filters['start_date'],
            'remove' => route('lembur.reports', request()->except(['page', 'combined_preset', 'preset', 'start_date'])),
        ] : null,
        $filters['end_date'] ? [
            'label' => 'Selesai: '.$filters['end_date'],
            'remove' => route('lembur.reports', request()->except(['page', 'combined_preset', 'preset', 'end_date'])),
        ] : null,
        $filters['status'] && isset($statusOptions[$filters['status']]) ? [
            'label' => 'Status: '.$statusOptions[$filters['status']],
            'remove' => route('lembur.reports', request()->except(['page', 'combined_preset', 'status_preset', 'status'])),
        ] : null,
        $filters['pengaju'] && isset($submissionRoleOptions[$filters['pengaju']]) ? [
            'label' => 'Pengaju: '.$submissionRoleOptions[$filters['pengaju']],
            'remove' => route('lembur.reports', request()->except(['page', 'pengaju'])),
        ] : null,
        ! empty($filters['sort_by']) ? [
            'label' => 'Urut: '.match ($filters['sort_by']) {
                'employee_name' => 'Karyawan',
                'pengaju' => 'Pengaju',
                'approver_name' => 'Approver',
                'durasi_jam' => 'Durasi',
                'status' => 'Status',
                'alasan' => 'Alasan',
                default => 'Tanggal Lembur',
            }.' '.strtoupper($filters['sort_order'] ?? 'desc'),
            'remove' => route('lembur.reports', request()->except(['page', 'sort_by', 'sort_order'])),
        ] : null,
        $filters['per_page'] ? [
            'label' => 'Per halaman: '.$filters['per_page'],
            'remove' => route('lembur.reports', request()->except(['page', 'per_page'])),
        ] : null,
    ])->filter()->values();
    $hasActiveReportFilters = $activeReportFilters->isNotEmpty();
@endphp

@section('content')
<div class="row">
    <div class="col-12 mb-4">
        <div class="card mx-4 shadow-xs">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                    <div>
                        <p class="text-sm mb-1 text-uppercase font-weight-bold">Analitik Lembur</p>
                        <h4 class="mb-1">Dashboard Laporan Lembur dan Approval</h4>
                        <p class="text-sm text-secondary mb-0">Pantau analitik pengajuan lembur, kualitas approval, jam lembur, dan distribusi karyawan dari satu halaman laporan.</p>
                        <div class="d-flex flex-wrap gap-2 align-items-center mt-2">
                            <span class="badge bg-gradient-dark">Scope: Tenant Aktif</span>
                            <span class="badge bg-gradient-info">Analisis berdasarkan filter laporan</span>
                            <span class="text-xs text-secondary">Export mengikuti filter yang sedang aktif.</span>
                        </div>
                        <div class="mt-3 p-3 border border-radius-lg bg-gray-100">
                            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                                <div>
                                    <p class="text-xs text-uppercase font-weight-bold text-secondary mb-1">Sinkronisasi Sorting</p>
                                    <p class="text-sm text-dark mb-1">Sorting aktif: {{ $activeSortLabel }} {{ strtoupper($filters['sort_order'] ?? 'desc') }}</p>
                                    <p class="text-xs text-secondary mb-0">Export XLSX dan PDF akan mengikuti urutan ini tanpa perlu set ulang filter.</p>
                                </div>
                                <span class="badge bg-gradient-primary">{{ $activeSortLabel }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex gap-2 flex-wrap align-items-center">
                        @if ($hasActiveReportFilters)
                            <span class="badge bg-gradient-info">Filter aktif</span>
                            @foreach ($activeReportFilters as $activeReportFilter)
                                <a href="{{ $activeReportFilter['remove'] }}" class="badge bg-gradient-light text-dark text-decoration-none">
                                    {{ $activeReportFilter['label'] }} <span class="ms-1">x</span>
                                </a>
                            @endforeach
                        @endif
                        <div class="btn-group" role="group" aria-label="Ekspor laporan lembur">
                            <a href="{{ route('lembur.export', request()->query()) }}" class="btn btn-outline-success btn-sm mb-0">
                                <i class="fas fa-file-excel me-1"></i> XLSX
                            </a>
                            <a href="{{ route('lembur.export.pdf', request()->query()) }}" class="btn btn-outline-danger btn-sm mb-0">
                                <i class="fas fa-file-pdf me-1"></i> PDF
                            </a>
                        </div>
                    </div>
                </div>
                <div class="row mt-3 g-3">
                    <div class="col-12">
                        <div class="border border-radius-xl p-3">
                            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                                <div>
                                    <p class="text-xs text-uppercase font-weight-bold text-secondary mb-1">Preset Export Cepat</p>
                                    <p class="text-sm text-secondary mb-0">Gunakan shortcut ini untuk unduh laporan HR yang paling sering dipakai tanpa set filter manual.</p>
                                </div>
                                <span class="badge bg-gradient-success">Shortcut Export</span>
                            </div>
                            <div class="row g-3">
                                @foreach ($quickExportPresets as $quickExportPreset)
                                    <div class="col-xl-3 col-lg-6">
                                        <div class="card border shadow-xs h-100">
                                            <div class="card-body p-3">
                                                <div class="d-flex justify-content-between align-items-start gap-3">
                                                    <div>
                                                        <p class="text-sm font-weight-bold mb-1">{{ $quickExportPreset['label'] }}</p>
                                                        <p class="text-xs text-secondary mb-0">{{ $quickExportPreset['helper'] }}</p>
                                                    </div>
                                                    <span class="badge bg-gradient-{{ $quickExportPreset['priority_badge']['tone'] }} {{ $quickExportPreset['priority_badge']['tone'] === 'warning' ? 'text-dark' : '' }}">{{ $quickExportPreset['priority_badge']['label'] }}</span>
                                                </div>
                                                <div class="d-flex flex-wrap gap-2 mt-3">
                                                    <span class="badge bg-gradient-light text-dark">{{ $quickExportPreset['count'] }} data</span>
                                                    <span class="badge bg-gradient-light text-dark">{{ $quickExportPreset['total_hours'] }} jam</span>
                                                    @if (! is_null($quickExportPreset['age_days']))
                                                        <span class="badge bg-gradient-light text-dark">Pending tertua {{ $quickExportPreset['age_days'] }} hari</span>
                                                    @endif
                                                </div>
                                                <div class="d-flex flex-wrap gap-2 mt-3">
                                                    <a href="{{ route('lembur.export', $quickExportPreset['query']) }}" class="btn btn-outline-success btn-sm mb-0">
                                                        <i class="fas fa-file-excel me-1"></i> XLSX
                                                    </a>
                                                    <a href="{{ route('lembur.export.pdf', $quickExportPreset['query']) }}" class="btn btn-outline-danger btn-sm mb-0">
                                                        <i class="fas fa-file-pdf me-1"></i> PDF
                                                    </a>
                                                </div>
                                                @if ($quickExportPreset['empty'])
                                                    <p class="text-xs text-secondary mb-0 mt-3">Belum ada data untuk shortcut ini saat ini, tetapi template export tetap siap dipakai.</p>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
                <form method="GET" action="{{ route('lembur.reports') }}" class="row g-3 align-items-end mt-3">
                    <div class="col-12">
                        <div class="border border-radius-xl p-3 bg-gray-100">
                            <div class="row g-3 align-items-end">
                                <div class="col-lg-7">
                                    <p class="text-xs text-uppercase font-weight-bold text-secondary mb-2">Preset Rentang Waktu</p>
                                    <div class="d-flex flex-wrap gap-2">
                                        @foreach ($quickFilterPresets as $presetKey => $presetLabel)
                                            <a
                                                href="{{ route('lembur.reports', array_merge(request()->except(['page', 'preset', 'start_date', 'end_date']), ['preset' => $presetKey])) }}"
                                                class="btn btn-sm {{ ($filters['preset'] ?? null) === $presetKey ? 'bg-gradient-dark text-white' : 'btn-outline-dark' }} mb-0"
                                            >
                                                {{ $presetLabel }}
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="col-lg-5">
                                    <p class="text-xs text-uppercase font-weight-bold text-secondary mb-2">Preset Status</p>
                                    <div class="d-flex flex-wrap gap-2">
                                        @foreach ($quickStatusPresets as $statusPresetKey => $statusPresetLabel)
                                            <a
                                                href="{{ route('lembur.reports', array_merge(request()->except(['page', 'status_preset', 'status']), ['status_preset' => $statusPresetKey])) }}"
                                                class="btn btn-sm {{ ($filters['status_preset'] ?? null) === $statusPresetKey ? 'bg-gradient-info text-white' : 'btn-outline-info' }} mb-0"
                                            >
                                                {{ $statusPresetLabel }}
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="col-12">
                                    <p class="text-xs text-uppercase font-weight-bold text-secondary mb-2">Preset Kombinasi</p>
                                    <div class="d-flex flex-wrap gap-2">
                                        @foreach ($quickCombinedPresets as $combinedPresetKey => $combinedPresetLabel)
                                            <a
                                                href="{{ route('lembur.reports', array_merge(request()->except(['page', 'combined_preset', 'preset', 'status_preset', 'start_date', 'end_date', 'status']), ['combined_preset' => $combinedPresetKey])) }}"
                                                class="btn btn-sm {{ ($filters['combined_preset'] ?? null) === $combinedPresetKey ? 'bg-gradient-primary text-white' : 'btn-outline-primary' }} mb-0"
                                            >
                                                {{ $combinedPresetLabel }}
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                                @if (! empty($filters['preset']) || ! empty($filters['status_preset']))
                                    <div class="col-12">
                                        <div class="d-flex justify-content-end">
                                            <a href="{{ route('lembur.reports', request()->except(['page', 'combined_preset', 'preset', 'status_preset', 'start_date', 'end_date', 'status'])) }}" class="btn btn-sm btn-light mb-0">Hapus Semua Preset</a>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <label for="search" class="form-label text-xs text-uppercase font-weight-bold">Cari Data</label>
                        <div class="input-group">
                            <span class="input-group-text text-body"><i class="fas fa-search" aria-hidden="true"></i></span>
                            <input type="text" name="search" id="search" class="form-control" value="{{ $filters['search'] }}" placeholder="Cari karyawan, approver, atau alasan">
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-6">
                        <label for="start_date" class="form-label text-xs text-uppercase font-weight-bold">Tanggal Mulai</label>
                        <input type="date" name="start_date" id="start_date" class="form-control" value="{{ $filters['start_date'] }}">
                    </div>
                    <div class="col-lg-2 col-md-6">
                        <label for="end_date" class="form-label text-xs text-uppercase font-weight-bold">Tanggal Akhir</label>
                        <input type="date" name="end_date" id="end_date" class="form-control" value="{{ $filters['end_date'] }}">
                    </div>
                    <div class="col-lg-2 col-md-6">
                        <label for="status" class="form-label text-xs text-uppercase font-weight-bold">Status</label>
                        <select name="status" id="status" class="form-control">
                            <option value="">Semua Status</option>
                            @foreach ($statusOptions as $statusValue => $statusLabel)
                                <option value="{{ $statusValue }}" @selected($filters['status'] === $statusValue)>{{ $statusLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-6">
                        <label for="pengaju" class="form-label text-xs text-uppercase font-weight-bold">Pengaju</label>
                        <select name="pengaju" id="pengaju" class="form-control">
                            <option value="">Semua Pengaju</option>
                            @foreach ($submissionRoleOptions as $submissionRoleValue => $submissionRoleLabel)
                                <option value="{{ $submissionRoleValue }}" @selected($filters['pengaju'] === $submissionRoleValue)>{{ $submissionRoleLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-1 col-md-6">
                        <label for="per_page" class="form-label text-xs text-uppercase font-weight-bold">Per Hal.</label>
                        <select name="per_page" id="per_page" class="form-control">
                            @foreach ([10, 25, 50, 100] as $pageSize)
                                <option value="{{ $pageSize }}" @selected($filters['per_page'] === $pageSize)>{{ $pageSize }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <div class="d-flex gap-2 justify-content-lg-end">
                            <button type="submit" class="btn btn-dark mb-0"><i class="fas fa-filter me-1"></i> Terapkan Filter</button>
                            <a href="{{ route('lembur.reports') }}" class="btn btn-light mb-0"><i class="fas fa-undo me-1"></i> Reset</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row">
    @foreach ($summary as $summaryCard)
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card mx-4 h-100 shadow-xs border-top border-3 border-{{ $summaryCard['tone'] === 'dark' ? 'dark' : $summaryCard['tone'] }}">
                <div class="card-body p-3">
                    <p class="text-sm mb-1 text-uppercase font-weight-bold text-{{ $summaryCard['tone'] === 'dark' ? 'dark' : $summaryCard['tone'] }}">{{ $summaryCard['label'] }}</p>
                    <h2 class="mb-1">{{ $summaryCard['value'] }}</h2>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="row">
    <div class="col-xl-8 mb-4">
        <div class="card mx-4 h-100 shadow-xs">
            <div class="card-header pb-0 d-flex justify-content-between align-items-start flex-wrap gap-2">
                <div>
                    <h6 class="mb-0">Tren Jam dan Pengajuan Lembur</h6>
                    <p class="text-sm mb-0">Pantau pergerakan total jam lembur dan jumlah pengajuan per bulan pada filter aktif.</p>
                </div>
                <span class="badge bg-gradient-info">Dual metrik: jam + jumlah</span>
            </div>
            <div class="card-body">
                @if (! empty($monthlyTrendChart['labels']))
                    <div class="chart" style="height: 360px;">
                        <canvas id="lembur-monthly-trend-chart" class="chart-canvas"></canvas>
                    </div>
                @else
                    <div class="d-flex align-items-center justify-content-center" style="height: 360px;">
                        <div class="text-center">
                            <i class="fas fa-chart-line fa-3x text-secondary opacity-5 mb-3"></i>
                            <p class="text-sm text-secondary mb-0">Belum ada data tren lembur untuk filter ini.</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-xl-4 mb-4">
        <div class="card mx-4 h-100 shadow-xs">
            <div class="card-header pb-0">
                <h6 class="mb-0">Proporsi Status</h6>
                <p class="text-sm mb-0">Komposisi status pengajuan lembur pada laporan aktif.</p>
            </div>
            <div class="card-body">
                @if (array_sum($statusDistributionChart['counts']) > 0)
                    <div class="chart" style="height: 360px;">
                        <canvas id="lembur-status-pie-chart" class="chart-canvas"></canvas>
                    </div>
                @else
                    <p class="text-sm text-secondary mb-0">Belum ada data distribusi status untuk filter ini.</p>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12 mb-4">
        <div class="card mx-4 shadow-xs">
            <div class="card-header pb-0 d-flex justify-content-between align-items-start flex-wrap gap-2">
                <div>
                    <h6 class="mb-0">Top Karyawan Lembur</h6>
                    <p class="text-sm mb-0">Perbandingan jam lembur dan jumlah pengajuan untuk karyawan dengan volume lembur tertinggi.</p>
                </div>
                <span class="badge bg-gradient-dark">Top 5</span>
            </div>
            <div class="card-body">
                @if (! empty($topEmployeesChart['labels']))
                    <div class="chart" style="height: 340px;">
                        <canvas id="lembur-top-employees-chart" class="chart-canvas"></canvas>
                    </div>
                @else
                    <p class="text-sm text-secondary mb-0">Belum ada data karyawan lembur untuk divisualisasikan.</p>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <x-flash-messages />
        <div class="card mb-4 mx-4 shadow-xs">
            <div class="card-body px-0 pt-0 pb-2">
                <div class="px-4 pt-4">
                    <div class="row g-3 mb-4">
                        <div class="col-xl-5">
                            <div class="card border shadow-xs h-100">
                                <div class="card-header pb-0">
                                    <h6 class="mb-1">Analitik Persetujuan</h6>
                                    <p class="text-sm text-secondary mb-0">Ringkasan kualitas approval dan backlog lembur.</p>
                                </div>
                                <div class="card-body pt-3">
                                    @foreach ($approvalInsights as $approvalInsight)
                                        <div class="d-flex justify-content-between align-items-start py-2 {{ $loop->last ? '' : 'border-bottom' }}">
                                            <div>
                                                <p class="text-sm font-weight-bold mb-1">{{ $approvalInsight['label'] }}</p>
                                                <p class="text-xs text-secondary mb-0">{{ $approvalInsight['helper'] }}</p>
                                            </div>
                                            <span class="badge bg-gradient-{{ $approvalInsight['tone'] }}">{{ $approvalInsight['value'] }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card border shadow-xs h-100">
                                <div class="card-header pb-0">
                                    <h6 class="mb-1">Breakdown Status</h6>
                                    <p class="text-sm text-secondary mb-0">Distribusi status pengajuan dalam filter aktif.</p>
                                </div>
                                <div class="card-body pt-3">
                                    @foreach ($statusBreakdown as $statusItem)
                                        <div class="d-flex justify-content-between align-items-center py-2 {{ $loop->last ? '' : 'border-bottom' }}">
                                            <span class="text-sm text-secondary">{{ $statusItem['label'] }}</span>
                                            <span class="badge {{ $statusItem['badge'] }}">{{ $statusItem['count'] }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-4 col-md-6">
                            <div class="card border shadow-xs h-100">
                                <div class="card-header pb-0">
                                    <h6 class="mb-1">Top Karyawan Lembur</h6>
                                    <p class="text-sm text-secondary mb-0">Karyawan dengan total jam lembur tertinggi.</p>
                                </div>
                                <div class="card-body pt-3">
                                    @forelse ($topEmployees as $topEmployee)
                                        <div class="d-flex justify-content-between align-items-start py-2 {{ $loop->last ? '' : 'border-bottom' }}">
                                            <div>
                                                <p class="text-sm font-weight-bold mb-1">{{ $topEmployee->employee?->name ?? 'Karyawan tidak tersedia' }}</p>
                                                <p class="text-xs text-secondary mb-0">{{ $topEmployee->employee?->employee_code ?? 'Tanpa kode' }} • {{ (int) $topEmployee->total_entries }} pengajuan</p>
                                            </div>
                                            <span class="text-sm font-weight-bold text-dark">{{ number_format((float) $topEmployee->total_hours, 2) }} jam</span>
                                        </div>
                                    @empty
                                        <p class="text-sm text-secondary mb-0">Belum ada data lembur untuk dianalisis.</p>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                @if ($reports->isEmpty())
                    <div class="text-center py-5">
                        <i class="fas fa-chart-bar fa-3x text-secondary mb-3"></i>
                        @if ($hasActiveReportFilters)
                            <p class="text-secondary mb-1">Tidak ada data lembur yang cocok dengan filter laporan saat ini.</p>
                            <p class="text-sm text-secondary mb-3">Coba ubah kata kunci, rentang tanggal, status, atau jenis pengaju.</p>
                            <a href="{{ route('lembur.reports') }}" class="btn btn-light btn-sm mb-0">Reset Filter</a>
                        @else
                            <p class="text-secondary mb-0">Belum ada data lembur untuk ditampilkan di laporan.</p>
                        @endif
                    </div>
                @else
                    <div class="table-responsive p-0">
                        <table class="table align-items-center mb-0">
                            <thead>
                                <tr>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-4 text-start {{ ($filters['sort_by'] ?? 'waktu_mulai') === 'employee_name' ? 'bg-primary text-white' : '' }}">
                                        <a href="{{ route('lembur.reports', array_merge(request()->all(), ['sort_by' => 'employee_name', 'sort_order' => $nextSortOrder('employee_name')])) }}" class="text-decoration-none {{ ($filters['sort_by'] ?? 'waktu_mulai') === 'employee_name' ? 'text-white' : 'text-secondary' }}">
                                            Karyawan
                                            @if($sortIndicator('employee_name') !== '')
                                                <span>{{ $sortIndicator('employee_name') }}</span>
                                            @endif
                                        </a>
                                    </th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-start {{ ($filters['sort_by'] ?? 'waktu_mulai') === 'pengaju' ? 'bg-primary text-white' : '' }}">
                                        <a href="{{ route('lembur.reports', array_merge(request()->all(), ['sort_by' => 'pengaju', 'sort_order' => $nextSortOrder('pengaju')])) }}" class="text-decoration-none {{ ($filters['sort_by'] ?? 'waktu_mulai') === 'pengaju' ? 'text-white' : 'text-secondary' }}">
                                            Pengaju
                                            @if($sortIndicator('pengaju') !== '')
                                                <span>{{ $sortIndicator('pengaju') }}</span>
                                            @endif
                                        </a>
                                    </th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-start {{ ($filters['sort_by'] ?? 'waktu_mulai') === 'approver_name' ? 'bg-primary text-white' : '' }}">
                                        <a href="{{ route('lembur.reports', array_merge(request()->all(), ['sort_by' => 'approver_name', 'sort_order' => $nextSortOrder('approver_name')])) }}" class="text-decoration-none {{ ($filters['sort_by'] ?? 'waktu_mulai') === 'approver_name' ? 'text-white' : 'text-secondary' }}">
                                            Approver
                                            @if($sortIndicator('approver_name') !== '')
                                                <span>{{ $sortIndicator('approver_name') }}</span>
                                            @endif
                                        </a>
                                    </th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-start {{ ($filters['sort_by'] ?? 'waktu_mulai') === 'waktu_mulai' ? 'bg-primary text-white' : '' }}">
                                        <a href="{{ route('lembur.reports', array_merge(request()->all(), ['sort_by' => 'waktu_mulai', 'sort_order' => $nextSortOrder('waktu_mulai')])) }}" class="text-decoration-none {{ ($filters['sort_by'] ?? 'waktu_mulai') === 'waktu_mulai' ? 'text-white' : 'text-secondary' }}">
                                            Tanggal Lembur
                                            @if($sortIndicator('waktu_mulai') !== '')
                                                <span>{{ $sortIndicator('waktu_mulai') }}</span>
                                            @endif
                                        </a>
                                    </th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center {{ ($filters['sort_by'] ?? 'waktu_mulai') === 'durasi_jam' ? 'bg-primary text-white' : '' }}">
                                        <a href="{{ route('lembur.reports', array_merge(request()->all(), ['sort_by' => 'durasi_jam', 'sort_order' => $nextSortOrder('durasi_jam')])) }}" class="text-decoration-none {{ ($filters['sort_by'] ?? 'waktu_mulai') === 'durasi_jam' ? 'text-white' : 'text-secondary' }}">
                                            Durasi
                                            @if($sortIndicator('durasi_jam') !== '')
                                                <span>{{ $sortIndicator('durasi_jam') }}</span>
                                            @endif
                                        </a>
                                    </th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center {{ ($filters['sort_by'] ?? 'waktu_mulai') === 'status' ? 'bg-primary text-white' : '' }}">
                                        <a href="{{ route('lembur.reports', array_merge(request()->all(), ['sort_by' => 'status', 'sort_order' => $nextSortOrder('status')])) }}" class="text-decoration-none {{ ($filters['sort_by'] ?? 'waktu_mulai') === 'status' ? 'text-white' : 'text-secondary' }}">
                                            Status
                                            @if($sortIndicator('status') !== '')
                                                <span>{{ $sortIndicator('status') }}</span>
                                            @endif
                                        </a>
                                    </th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-start {{ ($filters['sort_by'] ?? 'waktu_mulai') === 'alasan' ? 'bg-primary text-white' : '' }}">
                                        <a href="{{ route('lembur.reports', array_merge(request()->all(), ['sort_by' => 'alasan', 'sort_order' => $nextSortOrder('alasan')])) }}" class="text-decoration-none {{ ($filters['sort_by'] ?? 'waktu_mulai') === 'alasan' ? 'text-white' : 'text-secondary' }}">
                                            Alasan
                                            @if($sortIndicator('alasan') !== '')
                                                <span>{{ $sortIndicator('alasan') }}</span>
                                            @endif
                                        </a>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($reports as $report)
                                    <tr>
                                        <td class="ps-4 text-start">
                                            <div>
                                                <h6 class="mb-0 text-sm">{{ $report->employee?->name ?? '-' }}</h6>
                                                <p class="text-xs text-secondary mb-0 mt-1">{{ $report->employee?->employee_code ?? 'Tanpa kode karyawan' }}</p>
                                            </div>
                                        </td>
                                        <td class="text-start"><span class="text-secondary text-sm">{{ ucfirst($report->pengaju ?? '-') }}</span></td>
                                        <td class="text-start"><span class="text-secondary text-sm">{{ $report->approver?->name ?? 'Belum diputuskan' }}</span></td>
                                        <td class="text-start">
                                            <span class="text-secondary text-sm">{{ $report->waktu_mulai?->format('Y-m-d H:i') ?? '-' }}</span>
                                            <p class="text-xs text-secondary mb-0 mt-1">s/d {{ $report->waktu_selesai?->format('Y-m-d H:i') ?? '-' }}</p>
                                        </td>
                                        <td class="text-center"><span class="text-sm font-weight-bold">{{ $report->durasi_jam !== null ? number_format((float) $report->durasi_jam, 2).' jam' : '-' }}</span></td>
                                        <td class="text-center">
                                            <span class="badge {{ $report->status === 'disetujui' ? 'bg-gradient-success' : ($report->status === 'ditolak' ? 'bg-gradient-danger' : 'bg-gradient-warning text-dark') }}">
                                                {{ ucfirst($report->status) }}
                                            </span>
                                        </td>
                                        <td class="text-start"><span class="text-secondary text-sm">{{ \Illuminate\Support\Str::limit($report->alasan ?? '-', 80) }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="px-4 pt-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <p class="text-sm text-secondary mb-0">Menampilkan {{ $reports->firstItem() ?? 0 }}-{{ $reports->lastItem() ?? 0 }} dari {{ $reports->total() }} data lembur.</p>
                        {{ $reports->appends(request()->all())->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('dashboard')
<script>
    window.addEventListener('load', function () {
        function formatNumericValue(value) {
            return Number(value || 0).toLocaleString('id-ID', {
                minimumFractionDigits: Number(value || 0) % 1 === 0 ? 0 : 2,
                maximumFractionDigits: 2
            });
        }

        function formatTooltipWithPercentage(label, value, total, suffix) {
            var numericValue = Number(value || 0);
            var numericTotal = Number(total || 0);
            var percentage = numericTotal > 0 ? ((numericValue / numericTotal) * 100).toFixed(1) : '0.0';

            return label + ': ' + formatNumericValue(numericValue) + (suffix || '') + ' (' + percentage + '%)';
        }

        var monthlyTrendChartElement = document.getElementById('lembur-monthly-trend-chart');

        if (monthlyTrendChartElement) {
            new Chart(monthlyTrendChartElement.getContext('2d'), {
                type: 'line',
                data: {
                    labels: @json($monthlyTrendChart['labels']),
                    datasets: @json($monthlyTrendChart['datasets'])
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                boxWidth: 12,
                                color: '#344767'
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    if (context.dataset.label === 'Total Jam Lembur') {
                                        return context.dataset.label + ': ' + formatNumericValue(context.raw) + ' jam';
                                    }

                                    return context.dataset.label + ': ' + formatNumericValue(context.raw) + ' pengajuan';
                                },
                                afterBody: function (tooltipItems) {
                                    var tooltipItem = tooltipItems[0];
                                    var totalHours = @json($monthlyTrendChart['totals']);
                                    var hourValue = totalHours[tooltipItem.dataIndex] || 0;

                                    return 'Total jam lembur: ' + formatNumericValue(hourValue) + ' jam';
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            ticks: { color: '#67748e' },
                            grid: {
                                display: false,
                                drawBorder: false,
                            }
                        },
                        yHours: {
                            type: 'linear',
                            position: 'left',
                            beginAtZero: true,
                            ticks: { color: '#67748e' },
                            grid: {
                                color: 'rgba(103, 116, 142, 0.12)',
                                drawBorder: false,
                            }
                        },
                        yEntries: {
                            type: 'linear',
                            position: 'right',
                            beginAtZero: true,
                            ticks: {
                                precision: 0,
                                color: '#67748e'
                            },
                            grid: {
                                drawOnChartArea: false,
                                drawBorder: false,
                            }
                        }
                    }
                }
            });
        }

        var lemburStatusPieChartElement = document.getElementById('lembur-status-pie-chart');

        if (lemburStatusPieChartElement) {
            new Chart(lemburStatusPieChartElement.getContext('2d'), {
                type: 'pie',
                data: {
                    labels: @json($statusDistributionChart['labels']),
                    datasets: [{
                        data: @json($statusDistributionChart['counts']),
                        backgroundColor: @json($statusDistributionChart['backgroundColor']),
                        borderWidth: 0,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                boxWidth: 12,
                                color: '#344767'
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    var total = @json(array_sum($statusDistributionChart['counts']));

                                    return formatTooltipWithPercentage(context.label, context.raw, total, ' data');
                                }
                            }
                        }
                    }
                }
            });
        }

        var lemburTopEmployeesChartElement = document.getElementById('lembur-top-employees-chart');

        if (lemburTopEmployeesChartElement) {
            new Chart(lemburTopEmployeesChartElement.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: @json($topEmployeesChart['labels']),
                    datasets: [{
                        label: 'Total Jam Lembur',
                        data: @json($topEmployeesChart['hours']),
                        backgroundColor: @json($topEmployeesChart['backgroundColor']),
                        borderRadius: 8,
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    var entries = @json($topEmployeesChart['entries']);
                                    var entryCount = entries[context.dataIndex] || 0;

                                    return 'Jam lembur: ' + formatNumericValue(context.raw) + ' jam';
                                },
                                afterLabel: function (context) {
                                    var entries = @json($topEmployeesChart['entries']);
                                    var entryCount = entries[context.dataIndex] || 0;

                                    return 'Jumlah pengajuan: ' + formatNumericValue(entryCount);
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            ticks: { color: '#67748e' },
                            grid: {
                                color: 'rgba(103, 116, 142, 0.12)',
                                drawBorder: false,
                            }
                        },
                        y: {
                            ticks: { color: '#67748e' },
                            grid: {
                                display: false,
                                drawBorder: false,
                            }
                        }
                    }
                }
            });
        }
    });
</script>
@endpush