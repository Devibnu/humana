@extends('layouts.user_type.auth')

@section('content')

<div class="row">
    <div class="col-12 mb-4">
        <div class="card mx-4 shadow-xs">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                    <div>
                        <p class="text-sm mb-1 text-uppercase font-weight-bold">Anomali Cuti</p>
                        <h4 class="mb-1">Dashboard Deteksi Anomali Cuti</h4>
                        <p class="text-sm text-secondary mb-0">Pantau lonjakan cuti, pola berulang, dan indikasi carry-over untuk mempercepat investigasi pola cuti bermasalah.</p>
                        <div class="d-flex flex-wrap gap-2 align-items-center mt-2">
                            <span class="badge {{ $isTenantScoped ? 'bg-gradient-warning text-dark' : 'bg-gradient-dark' }}" data-testid="leave-anomaly-tenant-scope-badge">
                                Tenant: {{ $tenantScopeLabel }}
                            </span>
                            <span class="text-xs text-secondary">{{ $tenantScopeDescription }}</span>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap gap-2 align-items-end justify-content-end">
                        <a href="{{ route('leaves.anomalies.export.pdf', array_filter(['tenant_id' => $tenant?->id])) }}"
                            class="btn btn-sm bg-gradient-danger mb-0"
                            title="Unduh laporan anomali cuti untuk audit"
                            data-bs-toggle="tooltip"
                            data-testid="leave-anomaly-export-pdf">
                            Export PDF
                        </a>
                        <a href="{{ route('leaves.anomalies.export.xlsx', array_filter(['tenant_id' => $tenant?->id])) }}"
                            class="btn btn-sm bg-gradient-info mb-0"
                            title="Unduh laporan anomali cuti untuk audit"
                            data-bs-toggle="tooltip"
                            data-testid="leave-anomaly-export-xlsx">
                            Export XLSX
                        </a>
                        <a href="{{ route('leaves.anomalies.resolutions.export.pdf', array_filter(['tenant_id' => $tenant?->id])) }}"
                            class="btn btn-sm btn-outline-success mb-0"
                            title="Unduh rekap resolusi anomali cuti untuk audit"
                            data-bs-toggle="tooltip"
                            data-testid="leave-anomaly-resolution-export-pdf">
                            Export Resolusi PDF
                        </a>
                        <a href="{{ route('leaves.anomalies.resolutions.export.xlsx', array_filter(['tenant_id' => $tenant?->id])) }}"
                            class="btn btn-sm bg-gradient-success mb-0"
                            title="Unduh rekap resolusi anomali cuti untuk audit"
                            data-bs-toggle="tooltip"
                            data-testid="leave-anomaly-resolution-export-xlsx">
                            Export Resolusi XLSX
                        </a>
                        @if ($canSwitchTenant)
                            <form method="GET" action="{{ route('leaves.anomalies') }}" class="d-flex flex-wrap gap-2 align-items-end" data-testid="leave-anomaly-filter-form">
                                <div>
                                    <label for="tenant_id" class="form-label text-xs text-uppercase font-weight-bold mb-1">Tenant</label>
                                    <select name="tenant_id" id="tenant_id" onchange="this.form.submit()" class="form-select form-select-sm" title="Pilih tenant untuk melihat anomali cuti" data-bs-toggle="tooltip" data-testid="leave-anomaly-tenant-filter">
                                        @foreach ($tenants as $tenantOption)
                                            <option value="{{ $tenantOption->id }}" @selected((int) $tenantOption->id === (int) ($tenant?->id))>
                                                {{ $tenantOption->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-3 col-sm-6 mb-4">
        <div class="card mx-4 h-100 shadow-xs border-start border-4 border-danger">
            <div class="card-body p-3">
                <p class="text-sm mb-1" data-testid="leave-anomaly-total-label">Jumlah Anomali Terdeteksi Bulan Ini</p>
                <h2 class="mb-1" data-testid="leave-anomaly-total-value">{{ $summary['anomalies_this_month'] ?? 0 }}</h2>
                <p class="text-xs text-secondary mb-0">Periode aktif {{ $selectedMonthLabel }} {{ $selectedYear }}.</p>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6 mb-4">
        <div class="card mx-4 h-100 shadow-xs bg-gradient-danger">
            <div class="card-body p-3 text-white">
                <p class="text-sm mb-1 text-white">Lonjakan</p>
                <h3 class="mb-1" data-testid="leave-anomaly-spike-count">{{ $summary['spike_count'] ?? 0 }}</h3>
                <p class="text-xs mb-0">Anomali lonjakan cuti terdeteksi.</p>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6 mb-4">
        <div class="card mx-4 h-100 shadow-xs" style="background: linear-gradient(135deg, #ffb547 0%, #f78b1f 100%);">
            <div class="card-body p-3 text-white">
                <p class="text-sm mb-1 text-white">Pola Berulang</p>
                <h3 class="mb-1" data-testid="leave-anomaly-recurring-count">{{ $summary['recurring_count'] ?? 0 }}</h3>
                <p class="text-xs mb-0">Pola cuti berulang yang perlu perhatian.</p>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6 mb-4">
        <div class="card mx-4 h-100 shadow-xs" style="background: linear-gradient(135deg, #4bb7ff 0%, #1171ef 100%);">
            <div class="card-body p-3 text-white">
                <p class="text-sm mb-1 text-white">Carry-Over</p>
                <h3 class="mb-1" data-testid="leave-anomaly-carry-count">{{ $summary['carry_over_count'] ?? 0 }}</h3>
                <p class="text-xs mb-0">Indikasi carry-over cuti tahunan.</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-8 mb-4">
        <div class="card mx-4 h-100 shadow-xs">
            <div class="card-header pb-0">
                <h6 class="mb-0">Tren Hari Cuti vs Rata-Rata Bulanan</h6>
                <p class="text-sm mb-0">Titik merah menandai bulan yang melampaui ambang lonjakan.</p>
            </div>
            <div class="card-body">
                <div class="chart anomaly-chart" data-testid="leave-anomaly-spike-chart-container">
                    <canvas id="leave-anomaly-spike-chart" class="chart-canvas"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4 mb-4">
        <div class="card mx-4 h-100 shadow-xs">
            <div class="card-header pb-0">
                <h6 class="mb-0">Carry-Over Cuti per Tahun</h6>
                <p class="text-sm mb-0">Monitoring carry-over 5 tahun terakhir dengan ambang {{ $charts['carryOver']['limit'][0] ?? 0 }} hari.</p>
            </div>
            <div class="card-body">
                <div class="chart anomaly-chart anomaly-chart-sm" data-testid="leave-anomaly-carry-chart-container">
                    <canvas id="leave-anomaly-carry-chart" class="chart-canvas"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-7 mb-4">
        <div class="card mx-4 h-100 shadow-xs">
            <div class="card-header pb-0">
                <h6 class="mb-0">Heatmap Distribusi Cuti per Hari</h6>
                <p class="text-sm mb-0">Warna lebih gelap menandakan frekuensi cuti lebih tinggi pada kombinasi hari dan bulan tertentu.</p>
            </div>
            <div class="card-body">
                <div class="table-responsive" data-testid="leave-anomaly-heatmap-container">
                    <table class="table align-items-center mb-0 anomaly-heatmap-table">
                        <thead>
                            <tr>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Hari</th>
                                @foreach ($charts['heatmap']['months'] as $monthLabel)
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">{{ $monthLabel }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($charts['heatmap']['weekdays'] as $index => $weekdayLabel)
                                <tr>
                                    <td class="text-sm font-weight-bold">{{ $weekdayLabel }}</td>
                                    @foreach ($charts['heatmap']['matrix'][$index - 1] as $monthIndex => $count)
                                        @php($opacity = ($charts['heatmap']['max_count'] ?? 0) > 0 ? max(0.1, $count / $charts['heatmap']['max_count']) : 0.1)
                                        <td class="text-center">
                                            <span class="anomaly-heatmap-cell" style="background-color: rgba(247, 139, 31, {{ $opacity }});" title="{{ $weekdayLabel }} {{ $charts['heatmap']['months'][$monthIndex] }}: {{ $count }} cuti">
                                                {{ $count }}
                                            </span>
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-5 mb-4">
        <div class="card mx-4 h-100 shadow-xs">
            <div class="card-header pb-0">
                <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                    <div>
                        <h6 class="mb-0">Notifikasi Anomali</h6>
                        <p class="text-sm mb-0">Inbox notifikasi anomali untuk tindak lanjut cepat oleh admin dan manager.</p>
                    </div>
                    <span class="badge bg-gradient-dark" data-testid="leave-anomaly-notification-unread-count">
                        {{ $unreadNotificationsCount ?? 0 }} belum dibaca
                    </span>
                </div>
            </div>
            <div class="card-body">
                <div class="d-flex flex-column gap-3" data-testid="leave-anomaly-alert-list">
                    @forelse ($notifications as $notification)
                        @php($notificationData = $notification->data)
                        @php($notificationColor = ($notificationData['resolution_status'] ?? 'open') === 'resolved' ? 'success' : ($notificationData['color'] ?? 'secondary'))
                        @php($notificationIcon = $notificationData['icon'] ?? 'fas fa-bell')
                        @php($resolutionTooltip = ($notificationData['resolution_status'] ?? 'open') === 'resolved' ? ($notificationData['resolution_tooltip'] ?? '') : ($notificationData['description'] ?? ''))
                        <div class="border rounded-3 p-3 alert-{{ $notificationColor }}-soft {{ $notification->read_at ? 'opacity-8' : 'border-2' }}"
                            title="{{ $resolutionTooltip }}"
                            data-bs-toggle="tooltip"
                            data-testid="leave-anomaly-notification-item">
                            <div class="d-flex align-items-start gap-3">
                                <div class="icon icon-shape icon-sm border-radius-md text-center d-flex align-items-center justify-content-center bg-white shadow-sm">
                                    <i class="{{ $notificationIcon }} text-{{ $notificationColor }}"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between gap-2 flex-wrap align-items-start mb-1">
                                        <p class="text-sm font-weight-bold mb-0">{{ $notificationData['employee_name'] ?? 'Karyawan' }} → {{ $notificationData['title'] ?? 'Alert anomali' }}</p>
                                        <div class="d-flex gap-2 flex-wrap justify-content-end">
                                            <span class="badge {{ ($notificationData['anomaly_type'] ?? null) === 'lonjakan' ? 'bg-gradient-danger' : (($notificationData['anomaly_type'] ?? null) === 'pola_berulang' ? 'bg-gradient-warning text-dark' : 'bg-gradient-info') }}">
                                                {{ $notificationData['anomaly_type_label'] ?? 'Anomali' }}
                                            </span>
                                            <span class="badge {{ ($notificationData['resolution_status'] ?? 'open') === 'resolved' ? 'bg-gradient-success' : 'bg-light text-dark' }}" data-testid="leave-anomaly-resolution-status">
                                                {{ $notificationData['resolution_status_label'] ?? 'Belum Diselesaikan' }}
                                            </span>
                                        </div>
                                    </div>
                                    <p class="text-sm text-secondary mb-2">{{ $notificationData['description'] ?? '-' }}</p>
                                    @if (($notificationData['resolution_status'] ?? 'open') === 'resolved')
                                        <div class="resolution-summary rounded-3 p-2 mb-2">
                                            <p class="text-xs text-success font-weight-bold mb-1">Tindakan: {{ $notificationData['resolution_action'] ?? '-' }}</p>
                                            <p class="text-xs text-secondary mb-1">Catatan: {{ $notificationData['resolution_note'] ?? '-' }}</p>
                                            <p class="text-xs text-secondary mb-0">Resolved oleh {{ $notificationData['resolved_by'] ?? '-' }} pada {{ $notificationData['resolved_at_label'] ?? '-' }}</p>
                                        </div>
                                    @endif
                                    <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap">
                                        <div>
                                            <span class="text-xs text-secondary">Terdeteksi: {{ $notificationData['detected_at_label'] ?? optional($notification->created_at)->format('d M Y H:i') }}</span>
                                            @if ($notification->read_at)
                                                <span class="badge bg-light text-dark ms-2">Sudah dibaca</span>
                                            @else
                                                <span class="badge bg-gradient-dark ms-2">Baru</span>
                                            @endif
                                        </div>
                                        @if (($notificationData['resolution_status'] ?? 'open') !== 'resolved')
                                            <button type="button"
                                                class="btn btn-sm bg-gradient-success mb-0"
                                                data-bs-toggle="modal"
                                                data-bs-target="#resolveAnomalyModal{{ $notification->id }}"
                                                data-testid="leave-anomaly-resolve-button-{{ $notification->id }}">
                                                Resolve
                                            </button>
                                        @endif
                                        @if ($notification->read_at)
                                            <form method="POST" action="{{ route('leaves.anomalies.notifications.unread', $notification) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-sm btn-outline-secondary mb-0">Tandai Belum Dibaca</button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('leaves.anomalies.notifications.read', $notification) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-sm btn-outline-dark mb-0">Tandai Dibaca</button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        @if (($notificationData['resolution_status'] ?? 'open') !== 'resolved')
                            <div class="modal fade" id="resolveAnomalyModal{{ $notification->id }}" tabindex="-1" aria-labelledby="resolveAnomalyModalLabel{{ $notification->id }}" aria-hidden="true">
                                <div class="modal-dialog modal-lg modal-dialog-centered">
                                    <div class="modal-content">
                                        <form method="POST" action="{{ route('leaves.anomalies.resolve', $notification->id) }}">
                                            @csrf
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="resolveAnomalyModalLabel{{ $notification->id }}">Resolve Anomali Cuti</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p class="text-sm text-secondary">Lengkapi tindakan dan catatan resolusi untuk alert {{ $notificationData['employee_name'] ?? 'karyawan' }}.</p>
                                                <div class="mb-3">
                                                    <label for="resolution_action_{{ $notification->id }}" class="form-label">Tindakan</label>
                                                    <select name="resolution_action" id="resolution_action_{{ $notification->id }}" class="form-select" required>
                                                        <option value="">Pilih tindakan</option>
                                                        @foreach ($resolutionActions as $resolutionAction)
                                                            <option value="{{ $resolutionAction }}">{{ $resolutionAction }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div>
                                                    <label for="resolution_note_{{ $notification->id }}" class="form-label">Catatan Resolusi</label>
                                                    <textarea name="resolution_note" id="resolution_note_{{ $notification->id }}" rows="4" class="form-control" placeholder="Tuliskan hasil investigasi, alasan keputusan, atau tindak lanjut yang diambil." required></textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-light mb-0" data-bs-dismiss="modal">Batal</button>
                                                <button type="submit" class="btn bg-gradient-success mb-0">Simpan Resolusi</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @empty
                        <div class="text-center py-5">
                            <i class="fas fa-shield-alt fa-3x text-secondary mb-3"></i>
                            <p class="text-secondary mb-0">Belum ada anomali cuti yang terdeteksi.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .anomaly-chart {
        position: relative;
        height: 360px;
    }

    .anomaly-chart-sm {
        height: 320px;
    }

    .anomaly-heatmap-cell {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 42px;
        min-height: 42px;
        border-radius: 10px;
        font-size: 0.75rem;
        font-weight: 700;
        color: #1f2937;
    }

    .alert-danger-soft {
        background: rgba(234, 6, 6, 0.08);
    }

    .alert-warning-soft {
        background: rgba(247, 139, 31, 0.12);
    }

    .alert-info-soft {
        background: rgba(17, 113, 239, 0.12);
    }

    .alert-success-soft {
        background: rgba(34, 197, 94, 0.12);
    }

    .resolution-summary {
        background: rgba(34, 197, 94, 0.08);
        border: 1px solid rgba(34, 197, 94, 0.16);
    }

    @media (max-width: 991.98px) {
        .anomaly-chart,
        .anomaly-chart-sm {
            height: 300px;
        }
    }

    @media (max-width: 575.98px) {
        .anomaly-chart,
        .anomaly-chart-sm {
            height: 260px;
        }

        .anomaly-heatmap-cell {
            min-width: 34px;
            min-height: 34px;
        }
    }
</style>

@endsection

@push('dashboard')
<script>
    window.addEventListener('load', function () {
        var palette = {
            spike: '#ea0606',
            average: '#8392ab',
            threshold: '#f78b1f',
            carry: '#1171ef',
            text: '#344767',
            subtext: '#67748e',
            grid: 'rgba(103, 116, 142, 0.12)'
        };

        var spikeChartElement = document.getElementById('leave-anomaly-spike-chart');

        if (spikeChartElement) {
            new Chart(spikeChartElement.getContext('2d'), {
                type: 'line',
                data: {
                    labels: @json($charts['spikeTrend']['labels']),
                    datasets: [
                        {
                            label: 'Hari cuti',
                            data: @json($charts['spikeTrend']['days']),
                            borderColor: palette.spike,
                            backgroundColor: palette.spike,
                            tension: 0.35,
                            fill: false,
                            pointRadius: 4,
                        },
                        {
                            label: 'Rata-rata',
                            data: @json($charts['spikeTrend']['average_days']),
                            borderColor: palette.average,
                            backgroundColor: palette.average,
                            borderDash: [6, 4],
                            tension: 0,
                            fill: false,
                            pointRadius: 0,
                        },
                        {
                            label: 'Ambang lonjakan',
                            data: @json($charts['spikeTrend']['threshold_days']),
                            borderColor: palette.threshold,
                            backgroundColor: palette.threshold,
                            borderDash: [3, 3],
                            tension: 0,
                            fill: false,
                            pointRadius: 0,
                        },
                        {
                            label: 'Lonjakan',
                            data: @json($charts['spikeTrend']['anomaly_points']),
                            borderColor: palette.spike,
                            backgroundColor: palette.spike,
                            pointRadius: 6,
                            pointHoverRadius: 7,
                            showLine: false,
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
                                    var label = context.dataset.label || '';
                                    var value = Number(context.parsed.y || 0);
                                    var avg = Number(@json($charts['spikeTrend']['average_days'])[context.dataIndex] || 0);
                                    var percentage = avg > 0 ? ((value / avg) * 100).toFixed(1) : '0.0';

                                    return label + ': ' + value + ' hari (' + percentage + '% dari rata-rata)';
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

        var carryChartElement = document.getElementById('leave-anomaly-carry-chart');

        if (carryChartElement) {
            new Chart(carryChartElement.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: @json($charts['carryOver']['labels']),
                    datasets: [
                        {
                            label: 'Carry-over hari',
                            data: @json($charts['carryOver']['days']),
                            backgroundColor: palette.carry,
                            borderRadius: 8,
                        },
                        {
                            label: 'Batas carry-over',
                            data: @json($charts['carryOver']['limit']),
                            type: 'line',
                            borderColor: palette.threshold,
                            backgroundColor: palette.threshold,
                            borderDash: [4, 4],
                            pointRadius: 0,
                            fill: false,
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
                                    var value = Number(context.parsed.y || 0);
                                    var limit = Number(@json($charts['carryOver']['limit'])[context.dataIndex] || 0);
                                    var percentage = limit > 0 ? ((value / limit) * 100).toFixed(1) : '0.0';

                                    return context.dataset.label + ': ' + value + ' hari (' + percentage + '% dari batas)';
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