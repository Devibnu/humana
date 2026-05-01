@extends('layouts.user_type.auth')

@section('content')

<div class="row">
    <div class="col-12 mb-4">
        <div class="card mx-4 shadow-xs">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                    <div>
                        <p class="text-sm mb-1 text-uppercase font-weight-bold">Analitik Kehadiran</p>
                        <h4 class="mb-1">Dashboard Analitik Absensi Bulanan & Tahunan</h4>
                        <p class="text-sm text-secondary mb-0">Pantau pola kehadiran, distribusi status, dan total jam kerja tanpa perlu pivot manual.</p>
                        <div class="d-flex flex-wrap gap-2 align-items-center mt-2">
                            <span class="badge {{ $isTenantScoped ? 'bg-gradient-warning text-dark' : 'bg-gradient-dark' }}" data-testid="attendance-analytics-tenant-scope-badge">
                                Tenant: {{ $tenantScopeLabel }}
                            </span>
                            <span class="text-xs text-secondary" data-testid="attendance-analytics-tenant-scope-description">{{ $tenantScopeDescription }}</span>
                        </div>
                    </div>
                    @if ($currentUser && ($currentUser->isAdminHr() || $currentUser->isManager()))
                    <div class="btn-group" role="group" aria-label="Ekspor laporan analitik" data-testid="attendance-analytics-export-group">
                        <a href="{{ route('attendances.analytics.export.pdf', ['year' => $selectedYear, 'month' => $selectedMonth, 'tenant_id' => $tenant?->id]) }}" class="btn btn-outline-danger btn-sm mb-0" data-bs-toggle="tooltip" title="Unduh laporan analitik absensi bulanan/tahunan" data-testid="attendance-analytics-export-pdf">
                            <i class="fas fa-file-pdf me-1"></i> PDF
                        </a>
                        <a href="{{ route('attendances.analytics.export.xlsx', ['year' => $selectedYear, 'month' => $selectedMonth, 'tenant_id' => $tenant?->id]) }}" class="btn btn-outline-success btn-sm mb-0" data-bs-toggle="tooltip" title="Unduh laporan analitik absensi bulanan/tahunan" data-testid="attendance-analytics-export-xlsx">
                            <i class="fas fa-file-excel me-1"></i> XLSX
                        </a>
                    </div>
                    @endif
                </div>

                <form action="{{ route('attendances.analytics') }}" method="GET" class="row g-3 align-items-end mt-3" data-testid="attendance-analytics-filter-section">
                    @if ($canSwitchTenant)
                    <div class="col-md-6 col-lg-3">
                        <label for="tenant_id" class="form-label text-xs text-uppercase font-weight-bold">Tenant</label>
                        <select name="tenant_id" id="tenant_id" class="form-select" data-testid="attendance-analytics-tenant-filter">
                            @foreach ($tenants as $tenantOption)
                                <option value="{{ $tenantOption->id }}" @selected((int) $tenantOption->id === (int) ($tenant?->id))>
                                    {{ $tenantOption->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @else
                    <input type="hidden" name="tenant_id" value="{{ $tenant?->id }}">
                    @endif
                    <div class="col-md-6 col-lg-3">
                        <label for="year" class="form-label text-xs text-uppercase font-weight-bold">Tahun Analisis</label>
                        <select name="year" id="year" class="form-select" data-testid="attendance-analytics-filter-year">
                            @foreach ($availableYears as $yearOption)
                                <option value="{{ $yearOption }}" @selected((int) $yearOption === (int) $selectedYear)>{{ $yearOption }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <label for="month" class="form-label text-xs text-uppercase font-weight-bold">Bulan Detail</label>
                        <select name="month" id="month" class="form-select" data-testid="attendance-analytics-filter-month">
                            @foreach ($monthNames as $monthNumber => $monthLabel)
                                <option value="{{ $monthNumber }}" @selected((int) $monthNumber === (int) $selectedMonth)>{{ $monthLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="d-flex flex-wrap gap-2">
                            <button type="submit" class="btn btn-dark mb-0" data-testid="attendance-analytics-filter-btn">
                                <i class="fas fa-filter me-1"></i> Terapkan Filter
                            </button>
                            <a href="{{ route('attendances.analytics', ['tenant_id' => $tenant?->id]) }}" class="btn btn-light mb-0" data-testid="attendance-analytics-reset-btn">
                                <i class="fas fa-undo me-1"></i> Reset
                            </a>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <span class="badge bg-gradient-dark">Periode Detail: {{ $selectedPeriodLabel }}</span>
                            <span class="badge bg-gradient-secondary">Jam Kerja Bulan: {{ $monthSummary['total_work_hours_label'] }}</span>
                            <span class="badge bg-gradient-secondary">Jam Kerja Tahun: {{ $yearSummary['total_work_hours_label'] }}</span>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-3 col-sm-6 mb-4">
        <div class="card mx-4 h-100 shadow-xs border-top border-3 border-success" data-testid="attendance-analytics-card-month-total">
            <div class="card-body p-3">
                <p class="text-sm mb-1 text-uppercase font-weight-bold text-success">Total Kehadiran Bulan Ini</p>
                <h2 class="mb-1">{{ $monthSummary['total_attendances'] }}</h2>
                <p class="text-xs text-secondary mb-2">Periode {{ $selectedPeriodLabel }} &bull; {{ $monthSummary['total_work_hours_label'] }}</p>
                @if ($monthSummary['total_attendances'] > 0)
                <span class="badge bg-gradient-success" data-testid="attendance-analytics-card-month-pct-present">
                    Hadir {{ number_format($monthSummary['percentages']['present'], 1, ',', '.') }}%
                </span>
                @endif
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6 mb-4">
        <div class="card mx-4 h-100 shadow-xs border-top border-3 border-info" data-testid="attendance-analytics-card-year-total">
            <div class="card-body p-3">
                <p class="text-sm mb-1 text-uppercase font-weight-bold text-info">Total Kehadiran Tahun Ini</p>
                <h2 class="mb-1">{{ $yearSummary['total_attendances'] }}</h2>
                <p class="text-xs text-secondary mb-2">Tahun {{ $selectedYear }} &bull; {{ $yearSummary['total_work_hours_label'] }}</p>
                @if ($yearSummary['total_attendances'] > 0)
                <span class="badge bg-gradient-info" data-testid="attendance-analytics-card-year-pct-present">
                    Hadir {{ number_format($yearSummary['percentages']['present'], 1, ',', '.') }}%
                </span>
                @endif
            </div>
        </div>
    </div>
    <div class="col-xl-6 mb-4">
        <div class="card mx-4 h-100 shadow-xs" data-testid="attendance-analytics-card-status-summary">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                    <div>
                        <p class="text-sm mb-1">Distribusi Status</p>
                        <h6 class="mb-1">Komposisi status untuk {{ $selectedPeriodLabel }}</h6>
                        <p class="text-xs text-secondary mb-0">Hadir termasuk status terlambat.</p>
                    </div>
                    <div class="row g-2 flex-grow-1">
                        @foreach ($statusMeta as $statusKey => $meta)
                            <div class="col-6 col-lg-3">
                                <div class="border border-radius-lg p-2 h-100">
                                    <p class="text-xs mb-1" style="color: {{ $meta['color'] }};">{{ $meta['label'] }}</p>
                                    <h5 class="mb-0">{{ $monthSummary['status_counts'][$statusKey] }}</h5>
                                    <span class="text-xs text-secondary">{{ number_format($monthSummary['percentages'][$statusKey], 1, ',', '.') }}%</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-8 mb-4">
        <div class="card mx-4 h-100 shadow-xs">
            <div class="card-header pb-0 d-flex justify-content-between align-items-start flex-wrap gap-2">
                <div>
                    <h6 class="mb-0">Tren Absensi per Status 12 Bulan Terakhir</h6>
                    <p class="text-sm mb-0">Pergerakan status Hadir, Izin, Sakit, dan Alpha sampai periode {{ $selectedPeriodLabel }}.</p>
                </div>
                <span class="badge bg-gradient-success">Total tahun {{ $selectedYear }}: {{ $yearSummary['total_attendances'] }}</span>
            </div>
            <div class="card-body">
                @if (array_sum($monthlyTrendChart['totals']) > 0)
                <div class="chart" style="height: 360px;" data-testid="attendance-analytics-monthly-trend-chart-container">
                    <canvas id="attendance-monthly-trend-chart" class="chart-canvas"></canvas>
                </div>
                @else
                <div class="d-flex align-items-center justify-content-center" style="height: 360px;" data-testid="attendance-analytics-monthly-trend-empty-state">
                    <div class="text-center">
                        <i class="fas fa-chart-line fa-3x text-secondary opacity-5 mb-3"></i>
                        <p class="text-sm text-secondary mb-0">Belum ada data absensi untuk periode ini.</p>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-xl-4 mb-4">
        <div class="card mx-4 h-100 shadow-xs">
            <div class="card-header pb-0">
                <h6 class="mb-0">Proporsi Status Bulan Berjalan</h6>
                <p class="text-sm mb-0">Persentase status absensi untuk {{ $selectedPeriodLabel }}.</p>
            </div>
            <div class="card-body">
                @if (array_sum($statusDistributionChart['counts']) > 0)
                    <div class="chart" style="height: 360px;" data-testid="attendance-analytics-pie-chart-container">
                        <canvas id="attendance-status-pie-chart" class="chart-canvas"></canvas>
                    </div>
                @else
                    <p class="text-sm text-secondary mb-0">Belum ada data distribusi status untuk periode ini.</p>
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
                    <h6 class="mb-0">Distribusi Status 5 Tahun Terakhir</h6>
                    <p class="text-sm mb-0">Bandingkan pola status kehadiran tahunan untuk mendukung evaluasi operasional HR.</p>
                </div>
                <span class="badge bg-gradient-dark">Rentang {{ $yearlyDistributionChart['labels'][0] }} - {{ last($yearlyDistributionChart['labels']) }}</span>
            </div>
            <div class="card-body">
                @if (array_sum($yearlyDistributionChart['totals']) > 0)
                <div class="chart" style="height: 360px;" data-testid="attendance-analytics-yearly-distribution-chart-container">
                    <canvas id="attendance-yearly-distribution-chart" class="chart-canvas"></canvas>
                </div>
                @else
                <div class="d-flex align-items-center justify-content-center" style="height: 360px;" data-testid="attendance-analytics-yearly-distribution-empty-state">
                    <div class="text-center">
                        <i class="fas fa-chart-bar fa-3x text-secondary opacity-5 mb-3"></i>
                        <p class="text-sm text-secondary mb-0">Belum ada data absensi untuk periode ini.</p>
                    </div>
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
        function formatTooltipWithPercentage(label, value, total) {
            var numericValue = Number(value || 0);
            var numericTotal = Number(total || 0);
            var percentage = numericTotal > 0 ? ((numericValue / numericTotal) * 100).toFixed(1) : '0.0';

            return label + ': ' + numericValue + ' (' + percentage + '%)';
        }

        var monthlyTrendChartElement = document.getElementById('attendance-monthly-trend-chart');

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
                                    var monthlyTotals = @json($monthlyTrendChart['totals']);
                                    var total = monthlyTotals[context.dataIndex] || 0;

                                    return formatTooltipWithPercentage(context.dataset.label, context.raw, total);
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            ticks: {
                                color: '#67748e'
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
                                color: '#67748e'
                            },
                            grid: {
                                color: 'rgba(103, 116, 142, 0.12)',
                                drawBorder: false,
                            }
                        }
                    }
                }
            });
        }

        var attendanceStatusPieChartElement = document.getElementById('attendance-status-pie-chart');

        if (attendanceStatusPieChartElement) {
            new Chart(attendanceStatusPieChartElement.getContext('2d'), {
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

                                    return formatTooltipWithPercentage(context.label, context.raw, total);
                                }
                            }
                        }
                    }
                }
            });
        }

        var attendanceYearlyDistributionChartElement = document.getElementById('attendance-yearly-distribution-chart');

        if (attendanceYearlyDistributionChartElement) {
            new Chart(attendanceYearlyDistributionChartElement.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: @json($yearlyDistributionChart['labels']),
                    datasets: @json($yearlyDistributionChart['datasets'])
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
                                    var yearlyTotals = @json($yearlyDistributionChart['totals']);
                                    var total = yearlyTotals[context.dataIndex] || 0;

                                    return formatTooltipWithPercentage(context.dataset.label, context.raw, total);
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            stacked: false,
                            ticks: {
                                color: '#67748e'
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
                                color: '#67748e'
                            },
                            grid: {
                                color: 'rgba(103, 116, 142, 0.12)',
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