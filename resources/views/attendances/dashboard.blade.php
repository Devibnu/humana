@extends('layouts.user_type.auth')

@section('content')

<div class="row">
    <div class="col-12 mb-4">
        <div class="card mx-4 shadow-xs">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                    <div>
                        <p class="text-sm mb-1 text-uppercase font-weight-bold">Dashboard Kehadiran</p>
                        <h4 class="mb-1">Ringkasan Kehadiran Operasional</h4>
                        <p class="text-sm text-secondary mb-0">Lihat performa absensi harian dan tren 7 hari terakhir secara ringkas untuk kebutuhan audit HR.</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2 justify-content-end">
                        <a href="{{ route('attendances.analytics') }}" class="btn btn-outline-dark btn-sm mb-0" data-bs-toggle="tooltip" title="Buka analitik absensi bulanan/tahunan" data-testid="attendance-dashboard-shortcut-analytics">
                            <i class="fas fa-chart-line me-1"></i> Export Analytics
                        </a>
                        <span class="badge bg-success" data-testid="attendance-dashboard-badge-present">Hadir: {{ $summaryBadges['Hadir'] }}</span>
                        <span class="badge bg-warning text-dark" data-testid="attendance-dashboard-badge-leave">Izin: {{ $summaryBadges['Izin'] }}</span>
                        <span class="badge bg-info" data-testid="attendance-dashboard-badge-sick">Sakit: {{ $summaryBadges['Sakit'] }}</span>
                        <span class="badge bg-danger" data-testid="attendance-dashboard-badge-absent">Alpha: {{ $summaryBadges['Alpha'] }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-4 col-sm-6 mb-4">
        <div class="card mx-4 h-100 shadow-xs" data-testid="attendance-dashboard-card-total">
            <div class="card-body p-3">
                <p class="text-sm mb-1">Total Kehadiran Hari Ini</p>
                <h2 class="mb-1">{{ $totalKehadiranHariIni }}</h2>
                <p class="text-xs text-secondary mb-0">Total seluruh catatan absensi hari ini</p>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-sm-6 mb-4">
        <div class="card mx-4 h-100 shadow-xs" data-testid="attendance-dashboard-card-present">
            <div class="card-body p-3">
                <p class="text-sm mb-1 text-success">Hadir</p>
                <h2 class="mb-1 text-success">{{ $totalHadir }}</h2>
                <p class="text-xs text-secondary mb-0">Karyawan hadir hari ini</p>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-sm-6 mb-4">
        <div class="card mx-4 h-100 shadow-xs" data-testid="attendance-dashboard-card-leave">
            <div class="card-body p-3">
                <p class="text-sm mb-1 text-warning">Izin</p>
                <h2 class="mb-1 text-warning">{{ $totalIzin }}</h2>
                <p class="text-xs text-secondary mb-0">Status izin hari ini</p>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-sm-6 mb-4">
        <div class="card mx-4 h-100 shadow-xs" data-testid="attendance-dashboard-card-sick">
            <div class="card-body p-3">
                <p class="text-sm mb-1 text-info">Sakit</p>
                <h2 class="mb-1 text-info">{{ $totalSakit }}</h2>
                <p class="text-xs text-secondary mb-0">Status sakit hari ini</p>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-sm-6 mb-4">
        <div class="card mx-4 h-100 shadow-xs" data-testid="attendance-dashboard-card-absent">
            <div class="card-body p-3">
                <p class="text-sm mb-1 text-danger">Alpha</p>
                <h2 class="mb-1 text-danger">{{ $totalAlpha }}</h2>
                <p class="text-xs text-secondary mb-0">Status alpha hari ini</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-5 mb-4">
        <div class="card mx-4 h-100 shadow-xs">
            <div class="card-header pb-0">
                <h6 class="mb-0">Distribusi Status Kehadiran</h6>
                <p class="text-sm mb-0">Proporsi status kehadiran hari ini.</p>
            </div>
            <div class="card-body">
                @if (array_sum($statusDistributionChart['counts']) > 0)
                    <div class="chart" style="height: 320px;" data-testid="attendance-dashboard-status-chart-container">
                        <canvas id="attendance-status-distribution-chart" class="chart-canvas"></canvas>
                    </div>
                @else
                    <p class="text-sm text-secondary mb-0">Belum ada data distribusi kehadiran untuk hari ini.</p>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-7 mb-4">
        <div class="card mx-4 h-100 shadow-xs">
            <div class="card-header pb-0 d-flex justify-content-between align-items-start flex-wrap gap-2">
                <div>
                    <h6 class="mb-0">Tren Kehadiran 7 Hari Terakhir</h6>
                    <p class="text-sm mb-0">Jumlah catatan kehadiran harian untuk 7 hari terakhir.</p>
                </div>
                <div class="text-end">
                    <span class="badge bg-gradient-success">Hadir vs Tidak Hadir: {{ $totalHadir }} / {{ $totalTidakHadir }}</span>
                    <p class="text-xs text-secondary mb-0 mt-1">Total karyawan dalam scope: {{ $totalKaryawanScope }}</p>
                </div>
            </div>
            <div class="card-body">
                <div class="chart" style="height: 320px;" data-testid="attendance-dashboard-trend-chart-container">
                    <canvas id="attendance-weekly-trend-chart" class="chart-canvas"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('dashboard')
<script>
    window.addEventListener('load', function () {
        function tooltipLabel(context) {
            var rawValue = Number(context.raw || 0);
            var data = context.dataset.data || [];
            var total = data.reduce(function (carry, value) {
                return carry + Number(value || 0);
            }, 0);
            var percentage = total > 0 ? ((rawValue / total) * 100).toFixed(1) : '0.0';

            return context.label + ': ' + rawValue + ' (' + percentage + '%)';
        }

        var statusChartElement = document.getElementById('attendance-status-distribution-chart');

        if (statusChartElement) {
            new Chart(statusChartElement.getContext('2d'), {
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
                                label: tooltipLabel,
                            }
                        }
                    }
                }
            });
        }

        var weeklyTrendChartElement = document.getElementById('attendance-weekly-trend-chart');

        if (weeklyTrendChartElement) {
            new Chart(weeklyTrendChartElement.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: @json($attendanceTrendChart['labels']),
                    datasets: [{
                        label: 'Jumlah Kehadiran',
                        data: @json($attendanceTrendChart['counts']),
                        backgroundColor: @json($attendanceTrendChart['backgroundColor']),
                        borderColor: @json($attendanceTrendChart['borderColor']),
                        borderRadius: 8,
                        maxBarThickness: 26,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false,
                        },
                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    var value = Number(context.raw || 0);
                                    var total = @json(array_sum($attendanceTrendChart['counts']));
                                    var percentage = total > 0 ? ((value / total) * 100).toFixed(1) : '0.0';

                                    return 'Jumlah Kehadiran: ' + value + ' (' + percentage + '%)';
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
    });
</script>
@endpush