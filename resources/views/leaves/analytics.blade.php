@extends('layouts.user_type.auth')

@section('content')

<div class="row">
    <div class="col-12 mb-4">
        <div class="card mx-4 shadow-xs">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                    <div>
                        <p class="text-sm mb-1 text-uppercase font-weight-bold">Analitik Cuti</p>
                        <h4 class="mb-1">Dashboard Analitik Cuti Bulanan & Tahunan</h4>
                        <p class="text-sm text-secondary mb-0">Visualisasi tren status cuti, distribusi permintaan, dan total hari cuti untuk membantu HR membaca pola cuti tanpa pivot manual.</p>
                        <div class="d-flex flex-wrap gap-2 align-items-center mt-2">
                            <span class="badge {{ $isTenantScoped ? 'bg-gradient-warning text-dark' : 'bg-gradient-dark' }}" data-testid="leave-analytics-tenant-scope-badge">
                                Tenant: {{ $tenantScopeLabel }}
                            </span>
                            <span class="text-xs text-secondary" data-testid="leave-analytics-tenant-scope-description">{{ $tenantScopeDescription }}</span>
                        </div>
                    </div>
                    <form method="GET" action="{{ route('leaves.analytics') }}" class="row g-2 align-items-end justify-content-end" data-testid="leave-analytics-filter-form">
                        @if ($canSwitchTenant)
                            <div class="col-12 col-md-auto">
                                <label for="tenant_id" class="form-label text-xs text-uppercase font-weight-bold mb-1">Tenant</label>
                                <select name="tenant_id" id="tenant_id" onchange="this.form.submit()" class="form-select form-select-sm" title="Pilih tenant untuk melihat analitik cuti" data-bs-toggle="tooltip" data-testid="leave-analytics-tenant-filter">
                                    @foreach ($tenants as $tenantOption)
                                        <option value="{{ $tenantOption->id }}" @selected((int) $tenantOption->id === (int) ($tenant?->id))>
                                            {{ $tenantOption->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div class="col-6 col-md-auto">
                            <label for="year" class="form-label text-xs text-uppercase font-weight-bold mb-1">Tahun</label>
                            <select name="year" id="year" onchange="this.form.submit()" class="form-select form-select-sm" data-testid="leave-analytics-year-filter">
                                @foreach ($yearOptions as $yearOption)
                                    <option value="{{ $yearOption }}" @selected((int) $yearOption === (int) $selectedYear)>
                                        {{ $yearOption }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 col-md-auto">
                            <label for="month" class="form-label text-xs text-uppercase font-weight-bold mb-1">Bulan</label>
                            <select name="month" id="month" onchange="this.form.submit()" class="form-select form-select-sm" data-testid="leave-analytics-month-filter">
                                @foreach ($monthOptions as $monthValue => $monthLabel)
                                    <option value="{{ $monthValue }}" @selected((int) $monthValue === (int) $selectedMonth)>
                                        {{ $monthLabel }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </form>
                </div>
                <div class="d-flex flex-wrap gap-2 align-items-center mt-3">
                    <span class="badge bg-warning text-dark me-2" title="Jumlah request / total hari cuti" data-bs-toggle="tooltip" data-testid="leave-analytics-summary-pending">Pending: {{ $summary['pending_count'] ?? 0 }} / {{ $summary['pending_days'] ?? 0 }} hari</span>
                    <span class="badge bg-success me-2" title="Jumlah request / total hari cuti" data-bs-toggle="tooltip" data-testid="leave-analytics-summary-approved">Approved: {{ $summary['approved_count'] ?? 0 }} / {{ $summary['approved_days'] ?? 0 }} hari</span>
                    <span class="badge bg-danger" title="Jumlah request / total hari cuti" data-bs-toggle="tooltip" data-testid="leave-analytics-summary-rejected">Rejected: {{ $summary['rejected_count'] ?? 0 }} / {{ $summary['rejected_days'] ?? 0 }} hari</span>
                    <span class="text-xs text-secondary">Periode aktif: {{ $selectedMonthLabel }} {{ $selectedYear }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12 mb-4">
        <div class="card mx-4 shadow-xs">
            <div class="card-header pb-0">
                <h6 class="mb-0">Breakdown Jenis Cuti Periode Aktif</h6>
                <p class="text-sm mb-0">Daftar jenis cuti berdasarkan relasi leave type untuk {{ $selectedMonthLabel }} {{ $selectedYear }}.</p>
            </div>
            <div class="card-body">
                @if (count($leaveTypeBreakdown ?? []) === 0)
                    <div class="text-center py-4" data-testid="leave-analytics-leave-type-empty-state">
                        <p class="text-secondary mb-0">Belum ada data jenis cuti pada periode ini.</p>
                    </div>
                @else
                    <div class="table-responsive" data-testid="leave-analytics-leave-type-table-container">
                        <table class="table align-items-center mb-0" data-testid="leave-analytics-leave-type-table">
                            <thead>
                                <tr>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Jenis Cuti</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Permintaan</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Total Hari</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach (($leaveTypeBreakdown ?? []) as $breakdown)
                                    <tr>
                                        <td><span class="text-sm font-weight-bold">{{ $breakdown['leave_type_name'] }}</span></td>
                                        <td><span class="text-sm">{{ $breakdown['requests'] }}</span></td>
                                        <td><span class="text-sm">{{ $breakdown['days'] }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-3 col-sm-6 mb-4">
        <div class="card mx-4 h-100 shadow-xs border-start border-4 border-warning">
            <div class="card-body p-3">
                <p class="text-sm mb-1" data-testid="leave-analytics-card-total-requests-label">Total Permintaan Bulan Ini</p>
                <h2 class="mb-1" data-testid="leave-analytics-card-total-requests-value">{{ $summary['total_requests'] ?? 0 }}</h2>
                <p class="text-xs text-secondary mb-0">Permintaan cuti pada periode {{ $selectedMonthLabel }} {{ $selectedYear }}.</p>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6 mb-4">
        <div class="card mx-4 h-100 shadow-xs border-start border-4 border-success">
            <div class="card-body p-3">
                <p class="text-sm mb-1" data-testid="leave-analytics-card-total-days-label">Total Hari Cuti Bulan Ini</p>
                <h2 class="mb-1" data-testid="leave-analytics-card-total-days-value">{{ $summary['total_days'] ?? 0 }}</h2>
                <p class="text-xs text-secondary mb-0">Akumulasi seluruh hari cuti di periode terpilih.</p>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-sm-4 mb-4">
        <div class="card mx-4 h-100 shadow-xs bg-gradient-warning">
            <div class="card-body p-3 text-dark">
                <p class="text-sm mb-1 text-dark">Distribusi Pending</p>
                <h3 class="mb-1">{{ $summary['pending_count'] ?? 0 }}</h3>
                <p class="text-xs mb-0">{{ number_format((float) ($summary['pending']['percentage'] ?? 0), 1) }}% dari total permintaan</p>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-sm-4 mb-4">
        <div class="card mx-4 h-100 shadow-xs bg-gradient-success">
            <div class="card-body p-3 text-white">
                <p class="text-sm mb-1 text-white">Distribusi Approved</p>
                <h3 class="mb-1">{{ $summary['approved_count'] ?? 0 }}</h3>
                <p class="text-xs mb-0">{{ number_format((float) ($summary['approved']['percentage'] ?? 0), 1) }}% dari total permintaan</p>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-sm-4 mb-4">
        <div class="card mx-4 h-100 shadow-xs bg-gradient-danger">
            <div class="card-body p-3 text-white">
                <p class="text-sm mb-1 text-white">Distribusi Rejected</p>
                <h3 class="mb-1">{{ $summary['rejected_count'] ?? 0 }}</h3>
                <p class="text-xs mb-0">{{ number_format((float) ($summary['rejected']['percentage'] ?? 0), 1) }}% dari total permintaan</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-8 mb-4">
        <div class="card mx-4 h-100 shadow-xs">
            <div class="card-header pb-0">
                <h6 class="mb-0">Tren Cuti per Status - 12 Bulan pada Tahun {{ $selectedYear }}</h6>
                <p class="text-sm mb-0">Line chart membandingkan request pending, approved, dan rejected setiap bulan.</p>
            </div>
            <div class="card-body">
                <div class="chart analytics-chart" data-testid="leave-analytics-monthly-trend-chart-container">
                    <canvas id="leave-analytics-monthly-trend-chart" class="chart-canvas"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4 mb-4">
        <div class="card mx-4 h-100 shadow-xs">
            <div class="card-header pb-0">
                <h6 class="mb-0">Proporsi Status Bulan Berjalan</h6>
                <p class="text-sm mb-0">Pie chart menampilkan proporsi request per status untuk {{ $selectedMonthLabel }} {{ $selectedYear }}.</p>
            </div>
            <div class="card-body">
                @if (array_sum($charts['statusPie']['requests']) > 0)
                    <div class="chart analytics-chart analytics-chart-sm" data-testid="leave-analytics-status-pie-chart-container">
                        <canvas id="leave-analytics-status-pie-chart" class="chart-canvas"></canvas>
                    </div>
                @else
                    <div class="text-center py-5" data-testid="leave-analytics-empty-state-month">
                        <i class="fas fa-chart-pie fa-3x text-secondary mb-3"></i>
                        <p class="text-secondary mb-0">Belum ada data cuti pada periode {{ $selectedMonthLabel }} {{ $selectedYear }}.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-8 mb-4">
        <div class="card mx-4 h-100 shadow-xs">
            <div class="card-header pb-0">
                <h6 class="mb-0">Distribusi Status per Tahun - 5 Tahun Terakhir</h6>
                <p class="text-sm mb-0">Bar chart memotret perubahan status cuti tahunan agar pola jangka panjang mudah dibaca.</p>
            </div>
            <div class="card-body">
                <div class="chart analytics-chart" data-testid="leave-analytics-annual-status-chart-container">
                    <canvas id="leave-analytics-annual-status-chart" class="chart-canvas"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4 mb-4">
        <div class="card mx-4 h-100 shadow-xs">
            <div class="card-header pb-0">
                <h6 class="mb-0">Ringkasan Angka Periode Terpilih</h6>
                <p class="text-sm mb-0">Rincian hari cuti dan request per status untuk periode aktif.</p>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <span class="text-sm">Pending</span>
                    <span class="text-sm font-weight-bold text-warning">{{ $summary['pending_count'] ?? 0 }} request / {{ $summary['pending_days'] ?? 0 }} hari</span>
                </div>
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <span class="text-sm">Approved</span>
                    <span class="text-sm font-weight-bold text-success">{{ $summary['approved_count'] ?? 0 }} request / {{ $summary['approved_days'] ?? 0 }} hari</span>
                </div>
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <span class="text-sm">Rejected</span>
                    <span class="text-sm font-weight-bold text-danger">{{ $summary['rejected_count'] ?? 0 }} request / {{ $summary['rejected_days'] ?? 0 }} hari</span>
                </div>
                <div class="d-flex justify-content-between align-items-center py-2">
                    <span class="text-sm">Total Hari Cuti</span>
                    <span class="text-sm font-weight-bold">{{ $summary['total_days'] ?? 0 }} hari</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12 mb-4">
        <div class="card mx-4 shadow-xs">
            <div class="card-header pb-0">
                <h6 class="mb-0">Tabel Rekap Bulanan Tahun {{ $selectedYear }}</h6>
                <p class="text-sm mb-0">Ringkasan 12 bulan untuk mempermudah audit cepat tanpa membuka spreadsheet.</p>
            </div>
            <div class="card-body px-0 pt-0 pb-2">
                <div class="table-responsive p-3">
                    <table class="table align-items-center mb-0" data-testid="leave-analytics-monthly-table">
                        <thead>
                            <tr>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Bulan</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Pending</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Approved</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Rejected</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Total Permintaan</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Total Hari</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($monthly as $row)
                                <tr>
                                    <td><span class="text-sm font-weight-bold">{{ $row['period_label'] }}</span></td>
                                    <td><span class="text-sm">{{ $row['pending']['requests'] }}</span></td>
                                    <td><span class="text-sm">{{ $row['approved']['requests'] }}</span></td>
                                    <td><span class="text-sm">{{ $row['rejected']['requests'] }}</span></td>
                                    <td><span class="text-sm">{{ $row['total_requests'] }}</span></td>
                                    <td><span class="text-sm">{{ $row['total_days'] }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .analytics-chart {
        position: relative;
        height: 360px;
    }

    .analytics-chart-sm {
        height: 320px;
    }

    @media (max-width: 991.98px) {
        .analytics-chart,
        .analytics-chart-sm {
            height: 300px;
        }
    }

    @media (max-width: 575.98px) {
        .analytics-chart,
        .analytics-chart-sm {
            height: 260px;
        }
    }
</style>

@endsection

@push('dashboard')
<script>
    window.addEventListener('load', function () {
        var chartPalette = {
            pending: '#fbcf33',
            approved: '#82d616',
            rejected: '#ea0606',
            text: '#344767',
            subtext: '#67748e',
            grid: 'rgba(103, 116, 142, 0.12)'
        };

        var formatPercentTooltip = function (context, percentages, suffix) {
            var index = context.dataIndex;
            var label = context.label || '';
            var value = context.parsed;
            var percentage = percentages[index] || 0;

            return label + ': ' + value + ' ' + suffix + ' (' + percentage.toFixed(1) + '%)';
        };

        var monthlyTrendChartElement = document.getElementById('leave-analytics-monthly-trend-chart');

        if (monthlyTrendChartElement) {
            new Chart(monthlyTrendChartElement.getContext('2d'), {
                type: 'line',
                data: {
                    labels: @json($charts['monthlyTrend']['labels']),
                    datasets: [
                        {
                            label: 'Pending',
                            data: @json($charts['monthlyTrend']['pending_requests']),
                            borderColor: chartPalette.pending,
                            backgroundColor: chartPalette.pending,
                            tension: 0.35,
                            fill: false,
                            pointRadius: 4,
                            pointHoverRadius: 5,
                        },
                        {
                            label: 'Approved',
                            data: @json($charts['monthlyTrend']['approved_requests']),
                            borderColor: chartPalette.approved,
                            backgroundColor: chartPalette.approved,
                            tension: 0.35,
                            fill: false,
                            pointRadius: 4,
                            pointHoverRadius: 5,
                        },
                        {
                            label: 'Rejected',
                            data: @json($charts['monthlyTrend']['rejected_requests']),
                            borderColor: chartPalette.rejected,
                            backgroundColor: chartPalette.rejected,
                            tension: 0.35,
                            fill: false,
                            pointRadius: 4,
                            pointHoverRadius: 5,
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
                            labels: {
                                boxWidth: 12,
                                color: chartPalette.text,
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    var total = 0;
                                    context.chart.data.datasets.forEach(function (dataset) {
                                        total += Number(dataset.data[context.dataIndex] || 0);
                                    });

                                    var value = Number(context.parsed.y || 0);
                                    var percentage = total > 0 ? ((value / total) * 100).toFixed(1) : '0.0';

                                    return context.dataset.label + ': ' + value + ' request (' + percentage + '%)';
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            ticks: {
                                color: chartPalette.subtext,
                            },
                            grid: {
                                display: false,
                                drawBorder: false,
                            }
                        },
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0,
                                color: chartPalette.subtext,
                            },
                            grid: {
                                color: chartPalette.grid,
                                drawBorder: false,
                            }
                        }
                    }
                }
            });
        }

        var annualStatusChartElement = document.getElementById('leave-analytics-annual-status-chart');

        if (annualStatusChartElement) {
            new Chart(annualStatusChartElement.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: @json($charts['annualStatus']['labels']),
                    datasets: [
                        {
                            label: 'Pending',
                            data: @json($charts['annualStatus']['pending_requests']),
                            backgroundColor: chartPalette.pending,
                            borderRadius: 8,
                        },
                        {
                            label: 'Approved',
                            data: @json($charts['annualStatus']['approved_requests']),
                            backgroundColor: chartPalette.approved,
                            borderRadius: 8,
                        },
                        {
                            label: 'Rejected',
                            data: @json($charts['annualStatus']['rejected_requests']),
                            backgroundColor: chartPalette.rejected,
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
                            labels: {
                                color: chartPalette.text,
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    var total = 0;
                                    context.chart.data.datasets.forEach(function (dataset) {
                                        total += Number(dataset.data[context.dataIndex] || 0);
                                    });

                                    var value = Number(context.parsed.y || 0);
                                    var percentage = total > 0 ? ((value / total) * 100).toFixed(1) : '0.0';

                                    return context.dataset.label + ': ' + value + ' request (' + percentage + '%)';
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            stacked: false,
                            ticks: {
                                color: chartPalette.subtext,
                            },
                            grid: {
                                display: false,
                                drawBorder: false,
                            }
                        },
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0,
                                color: chartPalette.subtext,
                            },
                            grid: {
                                color: chartPalette.grid,
                                drawBorder: false,
                            }
                        }
                    }
                }
            });
        }

        var statusPieChartElement = document.getElementById('leave-analytics-status-pie-chart');

        if (statusPieChartElement) {
            new Chart(statusPieChartElement.getContext('2d'), {
                type: 'pie',
                data: {
                    labels: @json($charts['statusPie']['labels']),
                    datasets: [{
                        data: @json($charts['statusPie']['requests']),
                        backgroundColor: @json($charts['statusPie']['backgroundColor']),
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
                                color: chartPalette.text,
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    return formatPercentTooltip(context, @json($charts['statusPie']['percentages']), 'request');
                                },
                                afterLabel: function (context) {
                                    var days = @json($charts['statusPie']['days']);
                                    return 'Total hari: ' + (days[context.dataIndex] || 0);
                                }
                            }
                        }
                    }
                }
            });
        }
    });
</script>
@endpush