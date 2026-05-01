@extends('layouts.user_type.auth')

@section('content')

<div class="row">
    <div class="col-12 mb-4">
        <div class="card mx-4 shadow-xs">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                    <div>
                        <p class="text-sm mb-1 text-uppercase font-weight-bold">Tren Resolusi Anomali Cuti</p>
                        <h4 class="mb-1">Dashboard Tren Resolusi Anomali Cuti</h4>
                        <p class="text-sm text-secondary mb-0">Pantau konsistensi tindak lanjut anomali cuti, backlog unresolved, dan distribusi tindakan resolusi lintas periode.</p>
                        <div class="d-flex flex-wrap gap-2 align-items-center mt-2">
                            <span class="badge {{ $isTenantScoped ? 'bg-gradient-warning text-dark' : 'bg-gradient-dark' }}" data-testid="leave-anomaly-resolution-trend-tenant-scope-badge">
                                Tenant: {{ $tenantScopeLabel }}
                            </span>
                            <span class="text-xs text-secondary">{{ $tenantScopeDescription }}</span>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap gap-2 align-items-end justify-content-end">
                        <a href="{{ route('leaves.anomalies.resolutions', array_filter(['tenant_id' => $tenant?->id, 'year' => $selectedYear, 'month' => $selectedMonth])) }}" class="btn btn-sm btn-outline-dark mb-0">Kembali ke Dashboard Resolusi</a>
                        <form method="GET" action="{{ route('leaves.anomalies.resolutions.trends') }}" class="row g-2 align-items-end justify-content-end" data-testid="leave-anomaly-resolution-trend-filter-form">
                            @if ($canSwitchTenant)
                                <div class="col-12 col-md-auto">
                                    <label for="tenant_id" class="form-label text-xs text-uppercase font-weight-bold mb-1">Tenant</label>
                                    <select name="tenant_id" id="tenant_id" onchange="this.form.submit()" class="form-select form-select-sm" data-testid="leave-anomaly-resolution-trend-tenant-filter">
                                        @foreach ($tenants as $tenantOption)
                                            <option value="{{ $tenantOption->id }}" @selected((int) $tenantOption->id === (int) ($tenant?->id))>
                                                {{ $tenantOption->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                            <div class="col-12 col-md-auto">
                                <label for="year" class="form-label text-xs text-uppercase font-weight-bold mb-1">Tahun</label>
                                <select name="year" id="year" onchange="this.form.submit()" class="form-select form-select-sm" data-testid="leave-anomaly-resolution-trend-year-filter">
                                    @foreach ($yearOptions as $yearOption)
                                        <option value="{{ $yearOption }}" @selected((int) $yearOption === (int) $selectedYear)>
                                            {{ $yearOption }}
                                        </option>
                                    @endforeach
                                </select>
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
                <p class="text-sm mb-1" data-testid="leave-anomaly-resolution-trend-month-label">Total Resolusi Bulan Ini</p>
                <h2 class="mb-1" data-testid="leave-anomaly-resolution-trend-month-value">{{ $summary['resolved_this_month'] ?? 0 }}</h2>
                <p class="text-xs text-secondary mb-0">Jumlah anomali yang sudah resolved pada {{ $selectedMonthLabel }} {{ $selectedYear }}.</p>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-sm-6 mb-4">
        <div class="card mx-4 h-100 shadow-xs border-start border-4 border-info">
            <div class="card-body p-3">
                <p class="text-sm mb-1" data-testid="leave-anomaly-resolution-trend-year-label">Total Resolusi Tahun Ini</p>
                <h2 class="mb-1" data-testid="leave-anomaly-resolution-trend-year-value">{{ $summary['resolved_this_year'] ?? 0 }}</h2>
                <p class="text-xs text-secondary mb-0">Akumulasi resolusi sepanjang tahun analisis {{ $selectedYear }}.</p>
            </div>
        </div>
    </div>
    <div class="col-xl-4 mb-4">
        <div class="card mx-4 h-100 shadow-xs border-start border-4 border-secondary">
            <div class="card-body p-3">
                <p class="text-sm mb-1" data-testid="leave-anomaly-resolution-trend-unresolved-label">Jumlah Unresolved Aktif</p>
                <h2 class="mb-1" data-testid="leave-anomaly-resolution-trend-unresolved-value">{{ $summary['unresolved_active'] ?? 0 }}</h2>
                <p class="text-xs text-secondary mb-0">Backlog anomali yang masih menunggu tindak lanjut saat ini.</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-8 mb-4">
        <div class="card mx-4 h-100 shadow-xs">
            <div class="card-header pb-0">
                <h6 class="mb-0">Tren Resolusi 12 Bulan Terakhir</h6>
                <p class="text-sm mb-0">Line chart menampilkan pergerakan resolved vs unresolved berdasarkan periode anomali.</p>
            </div>
            <div class="card-body">
                <div class="chart resolution-trend-chart" data-testid="leave-anomaly-resolution-trend-line-chart-container">
                    <canvas id="leave-anomaly-resolution-trend-line-chart" class="chart-canvas"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4 mb-4">
        <div class="card mx-4 h-100 shadow-xs">
            <div class="card-header pb-0">
                <h6 class="mb-0">Proporsi Tindakan {{ $selectedMonthLabel }} {{ $selectedYear }}</h6>
                <p class="text-sm mb-0">Pie chart membantu melihat strategi tindak lanjut yang paling sering dipakai di bulan berjalan.</p>
            </div>
            <div class="card-body">
                <div class="chart resolution-trend-chart resolution-trend-chart-pie" data-testid="leave-anomaly-resolution-trend-pie-chart-container">
                    <canvas id="leave-anomaly-resolution-trend-pie-chart" class="chart-canvas"></canvas>
                </div>
                <div class="d-flex flex-column gap-2 mt-3">
                    @foreach ($actions as $action)
                        <div class="d-flex justify-content-between align-items-center text-sm">
                            <span>{{ $action['label'] }}</span>
                            <span>{{ $action['total'] }} ({{ number_format($action['percentage'], 1, ',', '.') }}%)</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12 mb-4">
        <div class="card mx-4 h-100 shadow-xs">
            <div class="card-header pb-0">
                <h6 class="mb-0">Distribusi Resolusi per Tahun</h6>
                <p class="text-sm mb-0">Bar chart grouped memperlihatkan distribusi tindakan resolusi pada 5 tahun terakhir.</p>
            </div>
            <div class="card-body">
                <div class="chart resolution-trend-chart" data-testid="leave-anomaly-resolution-trend-bar-chart-container">
                    <canvas id="leave-anomaly-resolution-trend-bar-chart" class="chart-canvas"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .resolution-trend-chart {
        position: relative;
        height: 360px;
    }

    .resolution-trend-chart-pie {
        height: 300px;
    }

    @media (max-width: 991.98px) {
        .resolution-trend-chart {
            height: 300px;
        }

        .resolution-trend-chart-pie {
            height: 260px;
        }
    }

    @media (max-width: 575.98px) {
        .resolution-trend-chart {
            height: 260px;
        }

        .resolution-trend-chart-pie {
            height: 240px;
        }
    }
</style>

@endsection

@push('dashboard')
<script>
    window.addEventListener('load', function () {
        var palette = {
            resolved: '#17c1e8',
            unresolved: '#8392ab',
            investigasi: '#1171ef',
            teguran: '#f78b1f',
            disetujuiKhusus: '#2dce89',
            abaikan: '#ea0606',
            text: '#344767',
            subtext: '#67748e',
            grid: 'rgba(103, 116, 142, 0.12)'
        };

        var monthlyLabels = @json(collect($monthly)->pluck('label')->all());
        var monthlyResolved = @json(collect($monthly)->pluck('resolved')->all());
        var monthlyUnresolved = @json(collect($monthly)->pluck('unresolved')->all());
        var monthlyTotals = @json(collect($monthly)->pluck('total')->all());
        var annualLabels = @json(collect($annual)->pluck('year')->map(fn ($year) => (string) $year)->all());
        var annualInvestigasi = @json(collect($annual)->pluck('investigasi')->all());
        var annualTeguran = @json(collect($annual)->pluck('teguran')->all());
        var annualDisetujuiKhusus = @json(collect($annual)->pluck('disetujui_khusus')->all());
        var annualAbaikan = @json(collect($annual)->pluck('abaikan')->all());
        var annualTotals = @json(collect($annual)->pluck('total_resolved')->all());
        var pieLabels = @json(collect($actions)->pluck('label')->all());
        var pieValues = @json(collect($actions)->pluck('total')->all());
        var piePercentages = @json(collect($actions)->pluck('percentage')->all());

        var lineChartElement = document.getElementById('leave-anomaly-resolution-trend-line-chart');

        if (lineChartElement) {
            new Chart(lineChartElement.getContext('2d'), {
                type: 'line',
                data: {
                    labels: monthlyLabels,
                    datasets: [
                        {
                            label: 'Resolved',
                            data: monthlyResolved,
                            borderColor: palette.resolved,
                            backgroundColor: 'rgba(23, 193, 232, 0.18)',
                            fill: true,
                            tension: 0.35,
                            stack: 'resolution-status',
                        },
                        {
                            label: 'Unresolved',
                            data: monthlyUnresolved,
                            borderColor: palette.unresolved,
                            backgroundColor: 'rgba(131, 146, 171, 0.20)',
                            fill: true,
                            tension: 0.35,
                            stack: 'resolution-status',
                        }
                    ]
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
                            labels: { color: palette.text }
                        },
                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    var total = Number(monthlyTotals[context.dataIndex] || 0);
                                    var value = Number(context.parsed.y || 0);
                                    var percentage = total > 0 ? ((value / total) * 100).toFixed(1) : '0.0';

                                    return context.dataset.label + ': ' + value + ' kasus (' + percentage + '%)';
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            ticks: { color: palette.subtext },
                            grid: { display: false, drawBorder: false }
                        },
                        y: {
                            stacked: true,
                            beginAtZero: true,
                            ticks: { precision: 0, color: palette.subtext },
                            grid: { color: palette.grid, drawBorder: false }
                        }
                    }
                }
            });
        }

        var barChartElement = document.getElementById('leave-anomaly-resolution-trend-bar-chart');

        if (barChartElement) {
            new Chart(barChartElement.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: annualLabels,
                    datasets: [
                        {
                            label: 'Investigasi',
                            data: annualInvestigasi,
                            backgroundColor: palette.investigasi,
                            borderRadius: 8,
                        },
                        {
                            label: 'Teguran',
                            data: annualTeguran,
                            backgroundColor: palette.teguran,
                            borderRadius: 8,
                        },
                        {
                            label: 'Disetujui Khusus',
                            data: annualDisetujuiKhusus,
                            backgroundColor: palette.disetujuiKhusus,
                            borderRadius: 8,
                        },
                        {
                            label: 'Abaikan',
                            data: annualAbaikan,
                            backgroundColor: palette.abaikan,
                            borderRadius: 8,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { color: palette.text }
                        },
                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    var total = Number(annualTotals[context.dataIndex] || 0);
                                    var value = Number(context.parsed.y || 0);
                                    var percentage = total > 0 ? ((value / total) * 100).toFixed(1) : '0.0';

                                    return context.dataset.label + ': ' + value + ' resolusi (' + percentage + '%)';
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            ticks: { color: palette.subtext },
                            grid: { display: false, drawBorder: false }
                        },
                        y: {
                            beginAtZero: true,
                            ticks: { precision: 0, color: palette.subtext },
                            grid: { color: palette.grid, drawBorder: false }
                        }
                    }
                }
            });
        }

        var pieChartElement = document.getElementById('leave-anomaly-resolution-trend-pie-chart');

        if (pieChartElement) {
            new Chart(pieChartElement.getContext('2d'), {
                type: 'pie',
                data: {
                    labels: pieLabels,
                    datasets: [{
                        data: pieValues,
                        backgroundColor: [palette.investigasi, palette.teguran, palette.disetujuiKhusus, palette.abaikan],
                        borderWidth: 0,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { color: palette.text }
                        },
                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    var value = Number(context.parsed || 0);
                                    var percentage = Number(piePercentages[context.dataIndex] || 0).toFixed(1);

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