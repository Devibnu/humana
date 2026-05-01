@extends('layouts.user_type.auth')

@section('content')

<div class="row">
    <div class="col-12 mb-4">
        <div class="card mx-4 shadow-xs">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                    <div>
                        <p class="text-sm mb-1 text-uppercase font-weight-bold">Audit Dashboard Resolusi Anomali Cuti</p>
                        <h4 class="mb-1">Dashboard Audit Resolusi Anomali</h4>
                        <p class="text-sm text-secondary mb-0">Pantau progres resolusi dan jejak auditnya dalam satu halaman, lengkap dengan grafik tren dan log detail tindak lanjut.</p>
                        <div class="d-flex flex-wrap gap-2 align-items-center mt-2">
                            <span class="badge {{ $isTenantScoped ? 'bg-gradient-warning text-dark' : 'bg-gradient-dark' }}" data-testid="leave-anomaly-resolution-audit-dashboard-tenant-scope-badge">
                                Tenant: {{ $tenantScopeLabel }}
                            </span>
                            <span class="text-xs text-secondary">{{ $tenantScopeDescription }}</span>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap gap-2 align-items-end justify-content-end">
                        <a href="{{ route('leaves.anomalies.resolutions') }}" class="btn btn-sm btn-outline-dark mb-0">Kembali ke Dashboard Resolusi</a>
                        <a href="{{ route('leaves.anomalies.resolutions.audit.export.pdf', array_filter(['tenant_id' => $tenant?->id, 'month' => $selectedMonth, 'year' => $selectedYear, 'search' => $search])) }}"
                            class="btn btn-sm btn-outline-success mb-0"
                            data-testid="leave-anomaly-resolution-audit-dashboard-export-pdf">
                            Export Audit Dashboard PDF
                        </a>
                        <a href="{{ route('leaves.anomalies.resolutions.audit.export.xlsx', array_filter(['tenant_id' => $tenant?->id, 'month' => $selectedMonth, 'year' => $selectedYear, 'search' => $search])) }}"
                            class="btn btn-sm bg-gradient-success mb-0"
                            data-testid="leave-anomaly-resolution-audit-dashboard-export-xlsx">
                            Export Audit Dashboard XLSX
                        </a>
                        <form method="GET" action="{{ route('leaves.anomalies.resolutions.audit') }}" class="row g-2 align-items-end justify-content-end" data-testid="leave-anomaly-resolution-audit-dashboard-filter-form">
                            @if ($canSwitchTenant)
                                <div class="col-12 col-md-auto">
                                    <label for="tenant_id" class="form-label text-xs text-uppercase font-weight-bold mb-1">Tenant</label>
                                    <select name="tenant_id" id="tenant_id" onchange="this.form.submit()" class="form-select form-select-sm" data-testid="leave-anomaly-resolution-audit-dashboard-tenant-filter">
                                        @foreach ($tenants as $tenantOption)
                                            <option value="{{ $tenantOption->id }}" @selected((int) $tenantOption->id === (int) ($tenant?->id))>{{ $tenantOption->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                            <div class="col-6 col-md-auto">
                                <label for="month" class="form-label text-xs text-uppercase font-weight-bold mb-1">Bulan</label>
                                <select name="month" id="month" onchange="this.form.submit()" class="form-select form-select-sm" data-testid="leave-anomaly-resolution-audit-dashboard-month-filter">
                                    @foreach ($monthOptions as $monthValue => $monthLabel)
                                        <option value="{{ $monthValue }}" @selected((int) $monthValue === (int) $selectedMonth)>{{ $monthLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-6 col-md-auto">
                                <label for="year" class="form-label text-xs text-uppercase font-weight-bold mb-1">Tahun</label>
                                <select name="year" id="year" onchange="this.form.submit()" class="form-select form-select-sm" data-testid="leave-anomaly-resolution-audit-dashboard-year-filter">
                                    @foreach ($yearOptions as $yearOption)
                                        <option value="{{ $yearOption }}" @selected((int) $yearOption === (int) $selectedYear)>{{ $yearOption }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-md-auto">
                                <label for="search" class="form-label text-xs text-uppercase font-weight-bold mb-1">Cari Karyawan</label>
                                <input type="text" name="search" id="search" value="{{ $search }}" class="form-control form-control-sm" placeholder="Nama karyawan" data-testid="leave-anomaly-resolution-audit-dashboard-search">
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
    <div class="col-xl-4 col-sm-6 mb-4">
        <div class="card mx-4 h-100 shadow-xs border-start border-4 border-success">
            <div class="card-body p-3">
                <p class="text-sm mb-1" data-testid="leave-anomaly-resolution-audit-dashboard-month-label">Jumlah Resolusi Bulan Ini</p>
                <h2 class="mb-1" data-testid="leave-anomaly-resolution-audit-dashboard-month-value">{{ $summary['resolved_this_month'] ?? 0 }}</h2>
                <p class="text-xs text-secondary mb-0">Jumlah anomali yang selesai pada {{ $selectedMonthLabel }} {{ $selectedYear }}.</p>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-sm-6 mb-4">
        <div class="card mx-4 h-100 shadow-xs border-start border-4 border-info">
            <div class="card-body p-3">
                <p class="text-sm mb-1" data-testid="leave-anomaly-resolution-audit-dashboard-year-label">Jumlah Resolusi Tahun Ini</p>
                <h2 class="mb-1" data-testid="leave-anomaly-resolution-audit-dashboard-year-value">{{ $summary['resolved_this_year'] ?? 0 }}</h2>
                <p class="text-xs text-secondary mb-0">Akumulasi resolusi sepanjang {{ $selectedYear }}.</p>
            </div>
        </div>
    </div>
    <div class="col-xl-4 mb-4">
        <div class="card mx-4 h-100 shadow-xs border-start border-4 border-secondary">
            <div class="card-body p-3">
                <p class="text-sm mb-1" data-testid="leave-anomaly-resolution-audit-dashboard-unresolved-label">Jumlah Unresolved Aktif</p>
                <h2 class="mb-1" data-testid="leave-anomaly-resolution-audit-dashboard-unresolved-value">{{ $summary['unresolved_active'] ?? 0 }}</h2>
                <p class="text-xs text-secondary mb-0">Backlog anomali yang belum ditindaklanjuti saat ini.</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-8 mb-4">
        <div class="card mx-4 h-100 shadow-xs">
            <div class="card-header pb-0">
                <h6 class="mb-0">Tren Resolusi Bulanan</h6>
                <p class="text-sm mb-0">Line chart resolved vs unresolved untuk 12 bulan terakhir.</p>
            </div>
            <div class="card-body">
                <div class="chart resolution-audit-dashboard-chart" data-testid="leave-anomaly-resolution-audit-dashboard-line-chart-container">
                    <canvas id="leave-anomaly-resolution-audit-dashboard-line-chart" class="chart-canvas"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4 mb-4">
        <div class="card mx-4 h-100 shadow-xs">
            <div class="card-header pb-0">
                <h6 class="mb-0">Proporsi Tindakan {{ $selectedMonthLabel }} {{ $selectedYear }}</h6>
                <p class="text-sm mb-0">Pie chart proporsi tindakan resolusi bulan berjalan.</p>
            </div>
            <div class="card-body">
                <div class="chart resolution-audit-dashboard-chart resolution-audit-dashboard-chart-pie" data-testid="leave-anomaly-resolution-audit-dashboard-pie-chart-container">
                    <canvas id="leave-anomaly-resolution-audit-dashboard-pie-chart" class="chart-canvas"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12 mb-4">
        <div class="card mx-4 h-100 shadow-xs">
            <div class="card-header pb-0">
                <h6 class="mb-0">Distribusi Tindakan Tahunan</h6>
                <p class="text-sm mb-0">Bar chart distribusi tindakan resolusi pada 5 tahun terakhir.</p>
            </div>
            <div class="card-body">
                <div class="chart resolution-audit-dashboard-chart" data-testid="leave-anomaly-resolution-audit-dashboard-bar-chart-container">
                    <canvas id="leave-anomaly-resolution-audit-dashboard-bar-chart" class="chart-canvas"></canvas>
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
                <p class="text-sm mb-0">Menampilkan {{ $summary['total_logs'] ?? 0 }} log untuk filter aktif.</p>
            </div>
            <div class="card-body">
                <div class="table-responsive" data-testid="leave-anomaly-resolution-audit-dashboard-table-container">
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
                                            <span class="badge {{ $log['type_key'] === 'lonjakan' ? 'bg-gradient-danger' : ($log['type_key'] === 'pola_berulang' ? 'bg-gradient-warning text-dark' : 'bg-gradient-info') }} align-self-start">{{ $log['jenis_anomali'] }}</span>
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
                                    <td colspan="8" class="text-center py-5 text-secondary">Belum ada data audit resolusi untuk filter yang dipilih.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .resolution-audit-dashboard-chart {
        position: relative;
        height: 340px;
    }

    .resolution-audit-dashboard-chart-pie {
        height: 280px;
    }

    @media (max-width: 991.98px) {
        .resolution-audit-dashboard-chart { height: 300px; }
        .resolution-audit-dashboard-chart-pie { height: 240px; }
    }

    @media (max-width: 575.98px) {
        .resolution-audit-dashboard-chart { height: 260px; }
        .resolution-audit-dashboard-chart-pie { height: 220px; }
    }
</style>

@endsection

@push('dashboard')
<script>
    window.addEventListener('load', function () {
        var palette = {
            resolved: '#16a34a',
            unresolved: '#8392ab',
            investigasi: '#1171ef',
            teguran: '#f78b1f',
            disetujuiKhusus: '#2dce89',
            abaikan: '#ea0606',
            text: '#344767',
            subtext: '#67748e',
            grid: 'rgba(103, 116, 142, 0.12)'
        };

        var charts = @json($charts);
        var lineChartElement = document.getElementById('leave-anomaly-resolution-audit-dashboard-line-chart');

        if (lineChartElement) {
            new Chart(lineChartElement.getContext('2d'), {
                type: 'line',
                data: {
                    labels: charts.monthlyTrend.labels,
                    datasets: [
                        {
                            label: 'Resolved',
                            data: charts.monthlyTrend.resolved,
                            borderColor: palette.resolved,
                            backgroundColor: 'rgba(22, 163, 74, 0.16)',
                            fill: true,
                            tension: 0.35,
                            stack: 'audit-resolution-status',
                        },
                        {
                            label: 'Unresolved',
                            data: charts.monthlyTrend.unresolved,
                            borderColor: palette.unresolved,
                            backgroundColor: 'rgba(131, 146, 171, 0.18)',
                            fill: true,
                            tension: 0.35,
                            stack: 'audit-resolution-status',
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { position: 'bottom', labels: { color: palette.text } },
                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    var total = Number(charts.monthlyTrend.totals[context.dataIndex] || 0);
                                    var value = Number(context.parsed.y || 0);
                                    var percentage = total > 0 ? ((value / total) * 100).toFixed(1) : '0.0';
                                    return context.dataset.label + ': ' + value + ' kasus (' + percentage + '%)';
                                }
                            }
                        }
                    },
                    scales: {
                        x: { ticks: { color: palette.subtext }, grid: { display: false, drawBorder: false } },
                        y: { stacked: true, beginAtZero: true, ticks: { precision: 0, color: palette.subtext }, grid: { color: palette.grid, drawBorder: false } }
                    }
                }
            });
        }

        var barChartElement = document.getElementById('leave-anomaly-resolution-audit-dashboard-bar-chart');

        if (barChartElement) {
            new Chart(barChartElement.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: charts.annualTrend.labels,
                    datasets: [
                        { label: 'Investigasi', data: charts.annualTrend.investigasi, backgroundColor: palette.investigasi, borderRadius: 8 },
                        { label: 'Teguran', data: charts.annualTrend.teguran, backgroundColor: palette.teguran, borderRadius: 8 },
                        { label: 'Disetujui Khusus', data: charts.annualTrend.disetujui_khusus, backgroundColor: palette.disetujuiKhusus, borderRadius: 8 },
                        { label: 'Abaikan', data: charts.annualTrend.abaikan, backgroundColor: palette.abaikan, borderRadius: 8 }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom', labels: { color: palette.text } },
                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    var total = Number(charts.annualTrend.totals[context.dataIndex] || 0);
                                    var value = Number(context.parsed.y || 0);
                                    var percentage = total > 0 ? ((value / total) * 100).toFixed(1) : '0.0';
                                    return context.dataset.label + ': ' + value + ' resolusi (' + percentage + '%)';
                                }
                            }
                        }
                    },
                    scales: {
                        x: { ticks: { color: palette.subtext }, grid: { display: false, drawBorder: false } },
                        y: { beginAtZero: true, ticks: { precision: 0, color: palette.subtext }, grid: { color: palette.grid, drawBorder: false } }
                    }
                }
            });
        }

        var pieChartElement = document.getElementById('leave-anomaly-resolution-audit-dashboard-pie-chart');

        if (pieChartElement) {
            new Chart(pieChartElement.getContext('2d'), {
                type: 'pie',
                data: {
                    labels: charts.actionDistribution.labels,
                    datasets: [{
                        data: charts.actionDistribution.values,
                        backgroundColor: [palette.investigasi, palette.teguran, palette.disetujuiKhusus, palette.abaikan],
                        borderWidth: 0,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom', labels: { color: palette.text } },
                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    var value = Number(context.parsed || 0);
                                    var percentage = Number(charts.actionDistribution.percentages[context.dataIndex] || 0).toFixed(1);
                                    return context.label + ': ' + value + ' resolusi (' + percentage + '%)';
                                }
                            }
                        }
                    }
                }
            });
        }

        if (window.Echo && {{ $currentUser?->id ?? 'null' }}) {
            window.Echo.private('App.Models.User.{{ $currentUser?->id }}')
                .notification(function (notification) {
                    if (! ['leave_anomaly', 'leave_anomaly_resolution'].includes(notification.category || '')) {
                        return;
                    }

                    window.location.reload();
                });
        }
    });
</script>
@endpush