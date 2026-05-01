@extends('layouts.user_type.auth')

@section('content')

<div class="row">
    <div class="col-12 mb-4">
        <div class="card mx-4 shadow-xs">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                    <div>
                        <p class="text-sm mb-1 text-uppercase font-weight-bold">Resolusi Anomali Cuti</p>
                        <h4 class="mb-1">Dashboard Resolusi Anomali Cuti</h4>
                        <p class="text-sm text-secondary mb-0">Pantau status unresolved dan resolved, baca catatan tindak lanjut, lalu tindak lanjuti alert anomali secara real-time.</p>
                        <div class="d-flex flex-wrap gap-2 align-items-center mt-2">
                            <span class="badge {{ $isTenantScoped ? 'bg-gradient-warning text-dark' : 'bg-gradient-dark' }}" data-testid="leave-anomaly-resolution-tenant-scope-badge">
                                Tenant: {{ $tenantScopeLabel }}
                            </span>
                            <span class="text-xs text-secondary">{{ $tenantScopeDescription }}</span>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap gap-2 align-items-end justify-content-end">
                        <a href="{{ route('leaves.anomalies') }}" class="btn btn-sm btn-outline-dark mb-0">Kembali ke Dashboard Anomali</a>
                        <a href="{{ route('leaves.anomalies.resolutions.export.pdf', array_filter(['tenant_id' => $tenant?->id])) }}"
                            class="btn btn-sm btn-outline-success mb-0"
                            title="Unduh rekap resolusi anomali cuti untuk audit"
                            data-bs-toggle="tooltip">
                            Export Resolusi PDF
                        </a>
                        <a href="{{ route('leaves.anomalies.resolutions.export.xlsx', array_filter(['tenant_id' => $tenant?->id])) }}"
                            class="btn btn-sm bg-gradient-success mb-0"
                            title="Unduh rekap resolusi anomali cuti untuk audit"
                            data-bs-toggle="tooltip">
                            Export Resolusi XLSX
                        </a>
                        <form method="GET" action="{{ route('leaves.anomalies.resolutions') }}" class="row g-2 align-items-end justify-content-end" data-testid="leave-anomaly-resolution-filter-form">
                            @if ($canSwitchTenant)
                                <div class="col-12 col-md-auto">
                                    <label for="tenant_id" class="form-label text-xs text-uppercase font-weight-bold mb-1">Tenant</label>
                                    <select name="tenant_id" id="tenant_id" onchange="this.form.submit()" class="form-select form-select-sm" data-testid="leave-anomaly-resolution-tenant-filter">
                                        @foreach ($tenants as $tenantOption)
                                            <option value="{{ $tenantOption->id }}" @selected((int) $tenantOption->id === (int) ($tenant?->id))>
                                                {{ $tenantOption->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                            <div class="col-6 col-md-auto">
                                <label for="month" class="form-label text-xs text-uppercase font-weight-bold mb-1">Bulan</label>
                                <select name="month" id="month" onchange="this.form.submit()" class="form-select form-select-sm" data-testid="leave-anomaly-resolution-month-filter">
                                    @foreach ($monthOptions as $monthValue => $monthLabel)
                                        <option value="{{ $monthValue }}" @selected((int) $monthValue === (int) $selectedMonth)>{{ $monthLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-6 col-md-auto">
                                <label for="year" class="form-label text-xs text-uppercase font-weight-bold mb-1">Tahun</label>
                                <select name="year" id="year" onchange="this.form.submit()" class="form-select form-select-sm" data-testid="leave-anomaly-resolution-year-filter">
                                    @foreach ($yearOptions as $yearOption)
                                        <option value="{{ $yearOption }}" @selected((int) $yearOption === (int) $selectedYear)>{{ $yearOption }}</option>
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
        <div class="card mx-4 h-100 shadow-xs border-start border-4 border-secondary">
            <div class="card-body p-3">
                <p class="text-sm mb-1" data-testid="leave-anomaly-resolution-unresolved-label">Jumlah Anomali Unresolved</p>
                <h2 class="mb-1" data-testid="leave-anomaly-resolution-unresolved-value">{{ $summary['unresolved'] ?? 0 }}</h2>
                <p class="text-xs text-secondary mb-0">Alert yang masih menunggu tindak lanjut pada periode terpilih.</p>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-sm-6 mb-4">
        <div class="card mx-4 h-100 shadow-xs border-start border-4 border-success">
            <div class="card-body p-3">
                <p class="text-sm mb-1" data-testid="leave-anomaly-resolution-resolved-label">Jumlah Anomali Resolved</p>
                <h2 class="mb-1" data-testid="leave-anomaly-resolution-resolved-value">{{ $summary['resolved'] ?? 0 }}</h2>
                <p class="text-xs text-secondary mb-0">Alert yang sudah ditindaklanjuti dan terdokumentasi.</p>
            </div>
        </div>
    </div>
    <div class="col-xl-4 mb-4">
        <div class="card mx-4 h-100 shadow-xs">
            <div class="card-body p-3">
                <p class="text-sm mb-2" data-testid="leave-anomaly-resolution-distribution-label">Distribusi Jenis Anomali</p>
                <div class="d-flex flex-column gap-2">
                    <span class="badge bg-gradient-danger align-self-start">Lonjakan: {{ $summary['spike'] ?? 0 }}</span>
                    <span class="badge bg-gradient-warning text-dark align-self-start">Pola Berulang: {{ $summary['recurring'] ?? 0 }}</span>
                    <span class="badge bg-gradient-info align-self-start">Carry-Over: {{ $summary['carry_over'] ?? 0 }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12 mb-4">
        <div class="card mx-4 shadow-xs">
            <div class="card-header pb-0">
                <h6 class="mb-0">Daftar Resolusi Anomali</h6>
                <p class="text-sm mb-0">Tabel tindak lanjut anomali cuti untuk periode {{ $monthOptions[$selectedMonth] ?? '-' }} {{ $selectedYear }}.</p>
            </div>
            <div class="card-body">
                <div class="table-responsive" data-testid="leave-anomaly-resolution-table-container">
                    <table class="table align-items-center mb-0">
                        <thead>
                            <tr>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Employee</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Jenis Anomali</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Deskripsi</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Periode</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Status</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Manager</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Tindakan</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Catatan</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Tanggal Resolusi</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($resolutions as $resolution)
                                <tr title="{{ $resolution['resolution_tooltip'] }}" data-bs-toggle="tooltip">
                                    <td class="text-sm font-weight-bold">{{ $resolution['employee'] }}</td>
                                    <td>
                                        <span class="badge {{ $resolution['type_key'] === 'lonjakan' ? 'bg-gradient-danger' : ($resolution['type_key'] === 'pola_berulang' ? 'bg-gradient-warning text-dark' : 'bg-gradient-info') }}">
                                            {{ $resolution['jenis_anomali'] }}
                                        </span>
                                    </td>
                                    <td class="text-sm text-secondary">{{ $resolution['deskripsi'] }}</td>
                                    <td class="text-sm">{{ $resolution['periode'] }}</td>
                                    <td>
                                        <span class="badge {{ $resolution['status_key'] === 'resolved' ? 'bg-gradient-success' : 'bg-light text-dark' }}" data-testid="leave-anomaly-resolution-status-row">
                                            {{ $resolution['status_resolusi'] }}
                                        </span>
                                    </td>
                                    <td class="text-sm">{{ $resolution['manager'] }}</td>
                                    <td class="text-sm">{{ $resolution['tindakan'] }}</td>
                                    <td class="text-sm text-secondary">{{ \Illuminate\Support\Str::limit($resolution['catatan'], 80) }}</td>
                                    <td class="text-sm">{{ $resolution['tanggal_resolusi'] }}</td>
                                    <td>
                                        <div class="d-flex gap-2 flex-wrap">
                                            @if ($resolution['status_key'] === 'open')
                                                <button type="button"
                                                    class="btn btn-sm bg-gradient-success mb-0"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#resolveDashboardModal{{ $resolution['notification_id'] }}">
                                                    Resolve
                                                </button>
                                            @endif
                                            <button type="button"
                                                class="btn btn-sm btn-outline-dark mb-0"
                                                data-bs-toggle="modal"
                                                data-bs-target="#detailResolutionModal{{ $resolution['notification_id'] }}">
                                                Detail Resolusi
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                <div class="modal fade" id="detailResolutionModal{{ $resolution['notification_id'] }}" tabindex="-1" aria-labelledby="detailResolutionModalLabel{{ $resolution['notification_id'] }}" aria-hidden="true">
                                    <div class="modal-dialog modal-lg modal-dialog-centered">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="detailResolutionModalLabel{{ $resolution['notification_id'] }}">Detail Resolusi Anomali</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p class="text-sm mb-2"><strong>Employee:</strong> {{ $resolution['employee'] }}</p>
                                                <p class="text-sm mb-2"><strong>Jenis Anomali:</strong> {{ $resolution['jenis_anomali'] }}</p>
                                                <p class="text-sm mb-2"><strong>Deskripsi:</strong> {{ $resolution['deskripsi'] }}</p>
                                                <p class="text-sm mb-2"><strong>Status:</strong> {{ $resolution['status_resolusi'] }}</p>
                                                <p class="text-sm mb-2"><strong>Manager:</strong> {{ $resolution['manager'] }}</p>
                                                <p class="text-sm mb-2"><strong>Tindakan:</strong> {{ $resolution['tindakan'] }}</p>
                                                <p class="text-sm mb-0"><strong>Catatan:</strong> {{ $resolution['catatan'] }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                @if ($resolution['status_key'] === 'open')
                                    <div class="modal fade" id="resolveDashboardModal{{ $resolution['notification_id'] }}" tabindex="-1" aria-labelledby="resolveDashboardModalLabel{{ $resolution['notification_id'] }}" aria-hidden="true">
                                        <div class="modal-dialog modal-lg modal-dialog-centered">
                                            <div class="modal-content">
                                                <form method="POST" action="{{ route('leaves.anomalies.resolve', $resolution['notification_id']) }}">
                                                    @csrf
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="resolveDashboardModalLabel{{ $resolution['notification_id'] }}">Resolve Anomali Cuti</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label for="dashboard_resolution_action_{{ $resolution['notification_id'] }}" class="form-label">Tindakan</label>
                                                            <select name="resolution_action" id="dashboard_resolution_action_{{ $resolution['notification_id'] }}" class="form-select" required>
                                                                <option value="">Pilih tindakan</option>
                                                                @foreach ($resolutionActions as $resolutionAction)
                                                                    <option value="{{ $resolutionAction }}">{{ $resolutionAction }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div>
                                                            <label for="dashboard_resolution_note_{{ $resolution['notification_id'] }}" class="form-label">Catatan Resolusi</label>
                                                            <textarea name="resolution_note" id="dashboard_resolution_note_{{ $resolution['notification_id'] }}" rows="4" class="form-control" placeholder="Tuliskan hasil tindak lanjut anomali ini." required></textarea>
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
                                <tr>
                                    <td colspan="10" class="text-center py-5 text-secondary">Belum ada data resolusi anomali untuk filter yang dipilih.</td>
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

@push('dashboard')
<script>
    window.addEventListener('load', function () {
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