@extends('layouts.user_type.auth')

@section('content')

<div class="row">
    <div class="col-12 mb-4">
        <div class="card mx-4 shadow-xs">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                    <div>
                        <p class="text-sm mb-1 text-uppercase font-weight-bold">Tren Anomali Cuti</p>
                        <h4 class="mb-1">Dashboard Tren Anomali Cuti Bulanan & Tahunan</h4>
                        <p class="text-sm text-secondary mb-0">Visualisasi pola jangka panjang lonjakan, pola berulang, dan carry-over untuk membantu HR membaca tren lintas periode.</p>
                        <div class="d-flex flex-wrap gap-2 align-items-center mt-2">
                            <span class="badge {{ $isTenantScoped ? 'bg-gradient-warning text-dark' : 'bg-gradient-dark' }}" data-testid="leave-anomaly-trend-tenant-scope-badge">
                                Tenant: {{ $tenantScopeLabel }}
                            </span>
                            <span class="text-xs text-secondary">{{ $tenantScopeDescription }}</span>
                        </div>
                    </div>
                    <form method="GET" action="{{ route('leaves.anomalies.trends') }}" class="row g-2 align-items-end justify-content-end" data-testid="leave-anomaly-trend-filter-form">
                        @if ($canSwitchTenant)
                            <div class="col-12 col-md-auto">
                                <label for="tenant_id" class="form-label text-xs text-uppercase font-weight-bold mb-1">Tenant</label>
                                <select name="tenant_id" id="tenant_id" onchange="this.form.submit()" class="form-select form-select-sm" data-testid="leave-anomaly-trend-tenant-filter">
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
                            <select name="year" id="year" onchange="this.form.submit()" class="form-select form-select-sm" data-testid="leave-anomaly-trend-year-filter">
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

<div class="row">
    <div class="col-xl-6 col-sm-6 mb-4">
        <div class="card mx-4 h-100 shadow-xs border-start border-4 border-danger">
            <div class="card-body p-3">
                <p class="text-sm mb-1" data-testid="leave-anomaly-trend-month-label">Total Anomali Bulan Ini</p>
                <h2 class="mb-1" data-testid="leave-anomaly-trend-month-value">{{ $summary['total_this_month'] ?? 0 }}</h2>
                <p class="text-xs text-secondary mb-0">Akumulasi anomali pada bulan aktif di tahun {{ $selectedYear }}.</p>
            </div>
        </div>
    </div>
    <div class="col-xl-6 col-sm-6 mb-4">
        <div class="card mx-4 h-100 shadow-xs border-start border-4 border-info">
            <div class="card-body p-3">
                <p class="text-sm mb-1" data-testid="leave-anomaly-trend-year-label">Total Anomali Tahun Ini</p>
                <h2 class="mb-1" data-testid="leave-anomaly-trend-year-value">{{ $summary['total_this_year'] ?? 0 }}</h2>
                <p class="text-xs text-secondary mb-0">Akumulasi anomali sepanjang tahun {{ $selectedYear }}.</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-8 mb-4">
        <div class="card mx-4 h-100 shadow-xs">
            <div class="card-header pb-0">
                <h6 class="mb-0">Jumlah Anomali per Bulan</h6>
                <p class="text-sm mb-0">Line chart stacked memperlihatkan kontribusi lonjakan, pola berulang, dan carry-over untuk tiap bulan.</p>
            </div>
            <div class="card-body">
                <div class="chart anomaly-trend-chart" data-testid="leave-anomaly-trend-line-chart-container">
                    <canvas id="leave-anomaly-trend-line-chart" class="chart-canvas"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4 mb-4">
        <div class="card mx-4 h-100 shadow-xs">
            <div class="card-header pb-0">
                <h6 class="mb-0">Ringkasan Bulanan {{ $selectedYear }}</h6>
                <p class="text-sm mb-0">Tabel cepat untuk membaca bulan mana yang mulai menunjukkan lonjakan tren.</p>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-items-center mb-0" data-testid="leave-anomaly-trend-monthly-table">
                        <thead>
                            <tr>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Bulan</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($monthly as $row)
                                <tr>
                                    <td><span class="text-sm font-weight-bold">{{ $row['label'] }}</span></td>
                                    <td><span class="text-sm">{{ $row['total'] }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12 mb-4">
        <div class="card mx-4 h-100 shadow-xs">
            <div class="card-header pb-0">
                <h6 class="mb-0">Jumlah Anomali per Tahun</h6>
                <p class="text-sm mb-0">Bar chart grouped menampilkan tren anomali per jenis untuk 5 tahun terakhir.</p>
            </div>
            <div class="card-body">
                <div class="chart anomaly-trend-chart" data-testid="leave-anomaly-trend-bar-chart-container">
                    <canvas id="leave-anomaly-trend-bar-chart" class="chart-canvas"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .anomaly-trend-chart {
        position: relative;
        height: 360px;
    }

    @media (max-width: 991.98px) {
        .anomaly-trend-chart {
            height: 300px;
        }
    }

    @media (max-width: 575.98px) {
        .anomaly-trend-chart {
            height: 260px;
        }
    }
</style>

@endsection

@push('dashboard')
<script>
    window.addEventListener('load', function () {
        var palette = {
            spike: '#ea0606',
            recurring: '#f78b1f',
            carry: '#1171ef',
            text: '#344767',
            subtext: '#67748e',
            grid: 'rgba(103, 116, 142, 0.12)'
        };

        var lineChartElement = document.getElementById('leave-anomaly-trend-line-chart');

        if (lineChartElement) {
            new Chart(lineChartElement.getContext('2d'), {
                type: 'line',
                data: {
                    labels: @json($charts['monthlyTrend']['labels']),
                    datasets: [
                        {
                            label: 'Lonjakan',
                            data: @json($charts['monthlyTrend']['spike']),
                            borderColor: palette.spike,
                            backgroundColor: 'rgba(234, 6, 6, 0.18)',
                            fill: true,
                            tension: 0.35,
                            stack: 'monthly-anomalies',
                        },
                        {
                            label: 'Pola Berulang',
                            data: @json($charts['monthlyTrend']['recurring']),
                            borderColor: palette.recurring,
                            backgroundColor: 'rgba(247, 139, 31, 0.18)',
                            fill: true,
                            tension: 0.35,
                            stack: 'monthly-anomalies',
                        },
                        {
                            label: 'Carry-Over',
                            data: @json($charts['monthlyTrend']['carry_over']),
                            borderColor: palette.carry,
                            backgroundColor: 'rgba(17, 113, 239, 0.18)',
                            fill: true,
                            tension: 0.35,
                            stack: 'monthly-anomalies',
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
                                    var total = Number(@json($charts['monthlyTrend']['totals'])[context.dataIndex] || 0);
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

        var barChartElement = document.getElementById('leave-anomaly-trend-bar-chart');

        if (barChartElement) {
            new Chart(barChartElement.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: @json($charts['annualTrend']['labels']),
                    datasets: [
                        {
                            label: 'Lonjakan',
                            data: @json($charts['annualTrend']['spike']),
                            backgroundColor: palette.spike,
                            borderRadius: 8,
                        },
                        {
                            label: 'Pola Berulang',
                            data: @json($charts['annualTrend']['recurring']),
                            backgroundColor: palette.recurring,
                            borderRadius: 8,
                        },
                        {
                            label: 'Carry-Over',
                            data: @json($charts['annualTrend']['carry_over']),
                            backgroundColor: palette.carry,
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
                                    var total = Number(@json($charts['annualTrend']['totals'])[context.dataIndex] || 0);
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
                            beginAtZero: true,
                            ticks: { precision: 0, color: palette.subtext },
                            grid: { color: palette.grid, drawBorder: false }
                        }
                    }
                }
            });
        }
    });
</script>
@endpush