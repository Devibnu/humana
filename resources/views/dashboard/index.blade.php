@extends('layouts.user_type.auth')

@section('content')

<div class="row">
    <div class="col-12 mb-4">
        <div class="card mx-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                    <div>
                        <p class="text-sm mb-1 text-uppercase font-weight-bold">Ringkasan Dashboard HR</p>
                        <h4 class="mb-1">Ringkasan Operasional</h4>
                        <p class="text-sm text-secondary mb-0">Ringkasan cepat untuk karyawan, absensi, persetujuan cuti, dan data master.</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2 justify-content-end">
                        <span class="badge bg-gradient-warning">Cuti Menunggu: {{ $leaveStatusSummary['pending'] ?? 0 }}</span>
                        <span class="badge bg-gradient-success">Cuti Disetujui: {{ $leaveStatusSummary['approved'] ?? 0 }}</span>
                        <span class="badge bg-gradient-danger">Cuti Ditolak: {{ $leaveStatusSummary['rejected'] ?? 0 }}</span>
                        <span class="badge bg-gradient-info">Absensi Hadir: {{ $attendanceStatusSummary['present'] ?? 0 }}</span>
                        <span class="badge bg-gradient-secondary">Absensi Tidak Hadir: {{ $attendanceStatusSummary['absent'] ?? 0 }}</span>
                        <span class="badge bg-gradient-dark">Absensi Terlambat: {{ $attendanceStatusSummary['late'] ?? 0 }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-4 col-sm-6 mb-4">
        <div class="card mx-4 h-100">
            <div class="card-body p-3">
                <p class="text-sm mb-1">Total Karyawan</p>
                <h4 class="mb-1">{{ $employeesTotal }}</h4>
                <p class="text-xs text-secondary mb-0">Data karyawan dalam cakupan akses aktif</p>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-sm-6 mb-4">
        <div class="card mx-4 h-100">
            <div class="card-body p-3">
                <p class="text-sm mb-1">Absensi Hari Ini</p>
                <h4 class="mb-1">{{ $attendancesTodayTotal }}</h4>
                <p class="text-xs text-secondary mb-0">Entri absensi untuk {{ now()->format('d M Y') }}</p>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-sm-6 mb-4">
        <div class="card mx-4 h-100">
            <div class="card-body p-3">
                <p class="text-sm mb-1">Cuti Menunggu Persetujuan</p>
                <h4 class="mb-1">{{ $leavesPendingApprovalTotal }}</h4>
                <p class="text-xs text-secondary mb-0">Pengajuan cuti yang menunggu persetujuan dalam cakupan saat ini</p>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-sm-6 mb-4">
        <div class="card mx-4 h-100">
            <div class="card-body p-3">
                <p class="text-sm mb-1">Total Lokasi Kerja</p>
                <h4 class="mb-1">{{ $workLocationsTotal }}</h4>
                <p class="text-xs text-secondary mb-0">Lokasi kerja terdaftar</p>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-sm-6 mb-4">
        <div class="card mx-4 h-100">
            <div class="card-body p-3">
                <p class="text-sm mb-1">Total Posisi</p>
                <h4 class="mb-1">{{ $positionsTotal }}</h4>
                <p class="text-xs text-secondary mb-0">Data master posisi</p>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-sm-6 mb-4">
        <div class="card mx-4 h-100">
            <div class="card-body p-3">
                <p class="text-sm mb-1">Total Departemen</p>
                <h4 class="mb-1">{{ $departmentsTotal }}</h4>
                <p class="text-xs text-secondary mb-0">Data master departemen</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-7 mb-4">
        <div class="card mx-4 h-100">
            <div class="card-header pb-0">
                <h6 class="mb-0">Absensi per Lokasi Kerja</h6>
                <p class="text-sm mb-0">Log absensi valid yang dikelompokkan berdasarkan lokasi kerja untuk hari ini.</p>
            </div>
            <div class="card-body">
                @if (count($attendancePerWorkLocationChart['labels']) > 0)
                    <div class="chart" style="height: 320px;">
                        <canvas id="attendance-work-location-chart" class="chart-canvas"></canvas>
                    </div>
                @else
                    <p class="text-sm text-secondary mb-0">Belum ada data log absensi untuk hari ini.</p>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-5 mb-4">
        <div class="card mx-4 h-100">
            <div class="card-header pb-0">
                <h6 class="mb-0">Distribusi Status Cuti</h6>
                <p class="text-sm mb-0">Pengajuan cuti dalam cakupan saat ini berdasarkan status persetujuan.</p>
            </div>
            <div class="card-body">
                @if (array_sum($leaveStatusChart['counts']) > 0)
                    <div class="chart" style="height: 320px;">
                        <canvas id="leave-status-chart" class="chart-canvas"></canvas>
                    </div>
                @else
                    <p class="text-sm text-secondary mb-0">Belum ada data cuti pada cakupan saat ini.</p>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection

@push('dashboard')
<script>
    window.addEventListener('load', function () {
        var attendanceChartElement = document.getElementById('attendance-work-location-chart');

        if (attendanceChartElement) {
            new Chart(attendanceChartElement.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: @json($attendancePerWorkLocationChart['labels']),
                    datasets: [{
                        label: 'Attendances',
                        data: @json($attendancePerWorkLocationChart['counts']),
                        backgroundColor: '#17c1e8',
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

        var leaveStatusChartElement = document.getElementById('leave-status-chart');

        if (leaveStatusChartElement) {
            new Chart(leaveStatusChartElement.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: @json($leaveStatusChart['labels']),
                    datasets: [{
                        data: @json($leaveStatusChart['counts']),
                        backgroundColor: @json($leaveStatusChart['backgroundColor']),
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
                        }
                    },
                    cutout: '68%'
                }
            });
        }
    });
</script>
@endpush