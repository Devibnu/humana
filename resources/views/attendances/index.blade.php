@extends('layouts.user_type.auth')

@section('content')

@php($currentUser = $currentUser ?? auth()->user())
@php($selfAttendanceContext = $selfAttendanceContext ?? ['employee' => null, 'workLocation' => null, 'todayAttendance' => null, 'nextAction' => null])
@php($selfWorkLocation = $selfAttendanceContext['workLocation'] ?? null)
@php($selfTodayAttendance = $selfAttendanceContext['todayAttendance'] ?? null)
@php($selfNextAction = $selfAttendanceContext['nextAction'] ?? null)
@php($statusLabels = ['present' => 'Hadir', 'late' => 'Terlambat', 'leave' => 'Izin', 'sick' => 'Sakit', 'absent' => 'Alpha'])
@php($statusClasses = ['present' => 'bg-gradient-success', 'late' => 'bg-gradient-warning', 'leave' => 'bg-gradient-warning text-dark', 'sick' => 'bg-info', 'absent' => 'bg-danger'])

<div class="row humana-mobile-shell">
    <div class="col-12">
        <x-flash-messages />

        @if ($currentUser && $currentUser->isEmployee())
            <div class="card humana-mobile-card humana-attendance-hero humana-mobile-only mb-3">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                        <div>
                            <p class="text-xs text-white-50 mb-1">{{ now()->translatedFormat('l, d M Y') }}</p>
                            <h5 class="text-white mb-0">Absensi Hari Ini</h5>
                        </div>
                        <span class="badge bg-white text-dark">{{ $selfNextAction === 'check_out' ? 'Sudah Masuk' : ($selfNextAction === 'complete' ? 'Lengkap' : 'Siap Absen') }}</span>
                    </div>

                    <form action="{{ route('attendances.self-service') }}" method="POST" id="self-attendance-form-mobile" data-testid="self-attendance-form-mobile">
                        @csrf
                        <input type="hidden" name="latitude" id="self-attendance-latitude-mobile">
                        <input type="hidden" name="longitude" id="self-attendance-longitude-mobile">
                        @if (! $selfWorkLocation)
                            <button type="button" class="btn btn-light humana-mobile-action mb-3" disabled data-testid="btn-self-attendance-disabled-mobile">
                                <i class="fas fa-map-marker-alt me-2"></i> Lokasi kerja belum diatur
                            </button>
                        @elseif ($selfNextAction === 'complete')
                            <button type="button" class="btn btn-light humana-mobile-action mb-3" disabled data-testid="btn-self-attendance-complete-mobile">
                                <i class="fas fa-check-circle me-2"></i> Absensi Hari Ini Lengkap
                            </button>
                        @else
                            <button type="submit" class="btn bg-white text-dark humana-mobile-action mb-3" id="btn-self-attendance-mobile" data-testid="btn-self-attendance-mobile">
                                <i class="fas fa-location-arrow me-2 text-primary"></i> {{ $selfNextAction === 'check_out' ? 'Absen Pulang' : 'Absen Masuk' }}
                            </button>
                        @endif
                    </form>

                    <div class="humana-mobile-meta">
                        <div class="meta-item">
                            <p class="text-xxs text-white-50 mb-1">Lokasi</p>
                            <p class="text-sm text-white font-weight-bold mb-0">{{ $selfWorkLocation?->name ?? 'Belum diatur' }}</p>
                            @if ($selfWorkLocation)
                                <p class="text-xxs text-white-50 mb-0">Radius {{ $selfWorkLocation->radius }} meter</p>
                            @endif
                        </div>
                        <div class="meta-item">
                            <p class="text-xxs text-white-50 mb-1">Jam</p>
                            <p class="text-sm text-white font-weight-bold mb-0">Masuk {{ $selfTodayAttendance?->check_in ?? '—' }}</p>
                            <p class="text-xxs text-white-50 mb-0">Pulang {{ $selfTodayAttendance?->check_out ?? '—' }}</p>
                        </div>
                    </div>
                    <p class="text-xs text-white-50 mb-0 mt-3" id="self-attendance-status-mobile" data-testid="self-attendance-status-mobile"></p>
                </div>
            </div>
        @endif

        <div class="card mb-4 mx-4 shadow-xs humana-mobile-card">
            <div class="card-header pb-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-1">Daftar Kehadiran</h5>
                    <p class="text-sm text-secondary mb-0">Pantau absensi harian karyawan dengan ringkasan status, filter tanggal, dan data lokasi perangkat.</p>
                    @if ($currentUser && $currentUser->isManager())
                        <p class="text-xs text-secondary mb-0 mt-2">Tenant terkunci: Anda hanya melihat data kehadiran dari {{ $currentUser->tenant?->name ?? 'tenant Anda' }}.</p>
                    @endif
                    @if ($currentUser && $currentUser->isEmployee())
                        <p class="text-xs text-secondary mb-0 mt-2">Anda hanya melihat data kehadiran milik Anda sendiri.</p>
                    @endif
                </div>
                @if ($currentUser && ($currentUser->isAdminHr() || $currentUser->isManager()))
                    <div class="d-flex flex-wrap gap-2 justify-content-end">
                        <a href="{{ route('attendances.analytics') }}" class="btn btn-outline-dark btn-sm mb-0" data-bs-toggle="tooltip" title="Buka analitik absensi bulanan/tahunan" data-testid="btn-attendance-analytics-shortcut">
                            <i class="fas fa-chart-line me-1"></i> Analytics
                        </a>
                        <a href="{{ route('attendances.export.csv', request()->only(['start_date', 'end_date'])) }}" class="btn btn-outline-secondary btn-sm mb-0" data-bs-toggle="tooltip" title="Unduh data absensi harian untuk audit operasional" data-testid="btn-export-attendance-csv">
                            <i class="fas fa-file-csv me-1"></i> Export CSV
                        </a>
                        <a href="{{ route('attendances.export.xlsx', request()->only(['start_date', 'end_date'])) }}" class="btn btn-outline-success btn-sm mb-0" data-bs-toggle="tooltip" title="Unduh data absensi harian untuk audit operasional" data-testid="btn-export-attendance-xlsx">
                            <i class="fas fa-file-excel me-1"></i> Export XLSX
                        </a>
                        <button type="button" class="btn bg-gradient-primary btn-sm mb-0" data-bs-toggle="modal" data-bs-target="#addAttendanceIndexModal" data-testid="btn-open-add-attendance-modal">
                            <i class="fas fa-plus me-1"></i> Tambah Kehadiran
                        </button>
                    </div>
                @endif
                @if ($currentUser && $currentUser->isEmployee())
                    <div class="d-flex flex-column align-items-lg-end gap-2 d-none d-md-flex">
                        <form action="{{ route('attendances.self-service') }}" method="POST" id="self-attendance-form" class="d-flex flex-wrap gap-2 justify-content-end align-items-center" data-testid="self-attendance-form">
                            @csrf
                            <input type="hidden" name="latitude" id="self-attendance-latitude">
                            <input type="hidden" name="longitude" id="self-attendance-longitude">
                            @if (! $selfWorkLocation)
                                <button type="button" class="btn btn-light btn-sm mb-0" disabled data-testid="btn-self-attendance-disabled">
                                    <i class="fas fa-map-marker-alt me-1"></i> Lokasi kerja belum diatur
                                </button>
                            @elseif ($selfNextAction === 'complete')
                                <button type="button" class="btn btn-light btn-sm mb-0" disabled data-testid="btn-self-attendance-complete">
                                    <i class="fas fa-check-circle me-1"></i> Absensi Hari Ini Lengkap
                                </button>
                            @else
                                <button type="submit" class="btn bg-gradient-primary btn-sm mb-0" id="btn-self-attendance" data-testid="btn-self-attendance">
                                    <i class="fas fa-location-arrow me-1"></i> {{ $selfNextAction === 'check_out' ? 'Absen Pulang' : 'Absen Masuk' }}
                                </button>
                            @endif
                        </form>
                        @if ($selfWorkLocation)
                            <p class="text-xs text-secondary mb-0 text-lg-end" data-testid="self-attendance-work-location">
                                Lokasi: {{ $selfWorkLocation->name }} | Radius {{ $selfWorkLocation->radius }} meter
                                @if ($selfTodayAttendance?->check_in)
                                    | Masuk {{ $selfTodayAttendance->check_in }}
                                @endif
                                @if ($selfTodayAttendance?->check_out)
                                    | Pulang {{ $selfTodayAttendance->check_out }}
                                @endif
                            </p>
                        @endif
                        <p class="text-xs text-secondary mb-0 text-lg-end" id="self-attendance-status" data-testid="self-attendance-status"></p>
                    </div>
                @endif
            </div>
            <div class="card-body px-0 pt-0 pb-2">
                <div class="px-4 py-3 border-bottom humana-mobile-filter">
                    <form action="{{ route('attendances.index') }}" method="GET" class="row g-3 align-items-end">
                        <div class="col-6 col-md-4">
                            <label class="form-label">Tanggal Mulai</label>
                            <input type="date" name="start_date" class="form-control" value="{{ $startDate }}" data-testid="attendance-start-date-filter">
                        </div>
                        <div class="col-6 col-md-4">
                            <label class="form-label">Tanggal Selesai</label>
                            <input type="date" name="end_date" class="form-control" value="{{ $endDate }}" data-testid="attendance-end-date-filter">
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="d-flex gap-2 flex-wrap">
                                <button type="submit" class="btn bg-gradient-dark mb-0 flex-fill flex-md-grow-0" data-testid="btn-apply-attendance-filter">
                                    <i class="fas fa-filter me-1"></i> Terapkan Filter
                                </button>
                                <a href="{{ route('attendances.index') }}" class="btn btn-light mb-0 flex-fill flex-md-grow-0">Reset</a>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="px-4 py-3 border-bottom">
                    <div class="d-flex flex-wrap gap-2 humana-summary-scroll">
                        <span class="badge bg-success" data-testid="attendances-summary-present">Hadir: {{ $summary['present'] }}</span>
                        <span class="badge bg-warning text-dark" data-testid="attendances-summary-leave">Izin: {{ $summary['leave'] }}</span>
                        <span class="badge bg-info" data-testid="attendances-summary-sick">Sakit: {{ $summary['sick'] }}</span>
                        <span class="badge bg-danger" data-testid="attendances-summary-absent">Alpha: {{ $summary['absent'] }}</span>
                    </div>
                </div>

                @if ($attendances->isEmpty())
                    <div class="text-center py-5" data-testid="attendances-empty-state">
                        <i class="fas fa-calendar-times fa-3x text-secondary mb-3"></i>
                        <p class="text-secondary mb-0">Belum ada data kehadiran untuk periode ini</p>
                    </div>
                @else
                    <div class="humana-mobile-list px-3 py-3">
                        <div class="d-grid gap-3 humana-bottom-safe">
                            @foreach ($attendances as $attendance)
                                @php($attendanceLog = $attendance->attendanceLog)
                                @php($workLocationName = $attendanceLog?->workLocation?->name ?? $attendance->employee?->workLocation?->name ?? '—')
                                @php($coordinates = $attendanceLog ? number_format((float) $attendanceLog->latitude, 7, '.', '').', '.number_format((float) $attendanceLog->longitude, 7, '.', '') : '—')
                                <div class="humana-attendance-item" data-testid="attendance-mobile-card-{{ $attendance->id }}">
                                    <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                        <div>
                                            <p class="text-xs text-secondary mb-1">{{ \Illuminate\Support\Carbon::parse($attendance->date)->format('d M Y') }}</p>
                                            <h6 class="text-sm mb-0">{{ $attendance->employee?->name ?? '—' }}</h6>
                                        </div>
                                        <span class="badge {{ $statusClasses[$attendance->status] ?? 'bg-secondary' }}">
                                            {{ $statusLabels[$attendance->status] ?? ucfirst($attendance->status) }}
                                        </span>
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <p class="text-xxs text-secondary mb-1">Masuk</p>
                                            <p class="text-sm font-weight-bold mb-0">{{ $attendance->check_in ?? '—' }}</p>
                                        </div>
                                        <div class="col-6">
                                            <p class="text-xxs text-secondary mb-1">Pulang</p>
                                            <p class="text-sm font-weight-bold mb-0">{{ $attendance->check_out ?? '—' }}</p>
                                        </div>
                                        <div class="col-12">
                                            <p class="text-xxs text-secondary mb-1">Lokasi</p>
                                            <p class="text-sm mb-0">{{ $workLocationName }}</p>
                                        </div>
                                        @if ($attendanceLog)
                                            <div class="col-12">
                                                <p class="text-xxs text-secondary mb-1">Koordinat perangkat</p>
                                                <p class="text-xs mb-0">{{ $coordinates }}</p>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="table-responsive p-0 humana-desktop-table">
                        <table class="table align-items-center mb-0" data-testid="attendances-table">
                            <thead>
                                <tr>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-4">Tanggal</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Karyawan</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Lokasi Kerja</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">Status</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">Jam Masuk</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">Jam Keluar</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">Device Koordinat</th>
                                    @if ($currentUser && ($currentUser->isAdminHr() || $currentUser->isManager()))
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">Aksi</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($attendances as $attendance)
                                    @php($attendanceLog = $attendance->attendanceLog)
                                    @php($workLocationName = $attendanceLog?->workLocation?->name ?? $attendance->employee?->workLocation?->name ?? '—')
                                    @php($coordinates = $attendanceLog ? number_format((float) $attendanceLog->latitude, 7, '.', '').', '.number_format((float) $attendanceLog->longitude, 7, '.', '') : '—')
                                    <tr>
                                        <td class="ps-4">
                                            <p class="text-xs font-weight-bold mb-0">{{ \Illuminate\Support\Carbon::parse($attendance->date)->format('d M Y') }}</p>
                                        </td>
                                        <td>
                                            <h6 class="mb-0 text-sm">{{ $attendance->employee?->name ?? '—' }}</h6>
                                            <p class="text-xs text-secondary mb-0">{{ $attendance->tenant?->name ?? '—' }}</p>
                                        </td>
                                        <td>
                                            <span class="text-sm font-weight-bold">{{ $workLocationName }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge {{ $statusClasses[$attendance->status] ?? 'bg-secondary' }}" data-testid="attendance-status-{{ $attendance->id }}">
                                                {{ $statusLabels[$attendance->status] ?? ucfirst($attendance->status) }}
                                            </span>
                                        </td>
                                        <td class="text-center"><span class="text-secondary text-sm font-weight-bold">{{ $attendance->check_in ?? '—' }}</span></td>
                                        <td class="text-center"><span class="text-secondary text-sm font-weight-bold">{{ $attendance->check_out ?? '—' }}</span></td>
                                        <td class="text-center"><span class="text-secondary text-xs font-weight-bold">{{ $coordinates }}</span></td>
                                        @if ($currentUser && ($currentUser->isAdminHr() || $currentUser->isManager()))
                                            <td class="text-center">
                                                <div class="d-flex justify-content-center align-items-center gap-3">
                                                    <a href="{{ route('attendances.edit', $attendance) }}?view=1" class="mx-1" data-bs-toggle="tooltip" title="Lihat" data-testid="btn-view-attendance-{{ $attendance->id }}">
                                                        <i class="fas fa-eye text-info"></i>
                                                    </a>
                                                    <a href="{{ route('attendances.edit', $attendance) }}" class="mx-1" data-bs-toggle="tooltip" title="Edit" data-testid="btn-edit-attendance-{{ $attendance->id }}">
                                                        <i class="fas fa-edit text-secondary"></i>
                                                    </a>
                                                    @if ($currentUser->isAdminHr())
                                                        <form action="{{ route('attendances.destroy', $attendance) }}" method="POST" class="d-inline">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="border-0 bg-transparent p-0 mx-1" data-bs-toggle="tooltip" title="Hapus" data-testid="btn-delete-attendance-{{ $attendance->id }}" onclick="return confirm('Hapus data kehadiran ini?')">
                                                                <i class="fas fa-trash text-danger"></i>
                                                            </button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="px-4 pt-3">{{ $attendances->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</div>

@if ($currentUser && ($currentUser->isAdminHr() || $currentUser->isManager()))
    <div class="modal fade" id="addAttendanceIndexModal" tabindex="-1" aria-labelledby="addAttendanceIndexModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addAttendanceIndexModalLabel">Tambah Kehadiran</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    @if ($employees->isEmpty())
                        <div class="border border-secondary border-radius-md p-4 text-center bg-gray-100" data-testid="attendances-modal-empty-state">
                            <i class="fas fa-user-clock text-secondary fa-2x mb-3"></i>
                            <h6 class="mb-2">Belum ada karyawan, silakan buat karyawan terlebih dahulu</h6>
                            <p class="text-sm text-secondary mb-0">Kehadiran hanya dapat dicatat jika data karyawan sudah tersedia di tenant terkait.</p>
                        </div>
                    @else
                        <form action="{{ route('attendances.store') }}" method="POST" data-testid="attendances-index-create-form">
                            @csrf
                            @include('attendances._form')
                            <div class="d-flex justify-content-end gap-2 mt-4 flex-wrap">
                                <button type="button" class="btn btn-info mb-0" id="detect-attendance-location"><i class="fas fa-location-arrow me-1"></i> Gunakan Lokasi Perangkat</button>
                                <button type="button" class="btn btn-light mb-0" data-bs-dismiss="modal"><i class="fas fa-times me-1"></i> Batal</button>
                                <button type="submit" class="btn btn-primary mb-0"><i class="fas fa-save me-1"></i> Simpan Kehadiran</button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endif

@if ($currentUser && $currentUser->isEmployee())
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                function bindSelfAttendance(formId, buttonId, latitudeId, longitudeId, statusId) {
                    var form = document.getElementById(formId);
                    var button = document.getElementById(buttonId);
                    var latitudeInput = document.getElementById(latitudeId);
                    var longitudeInput = document.getElementById(longitudeId);
                    var statusElement = document.getElementById(statusId);

                    if (!form || !button || !latitudeInput || !longitudeInput || !statusElement) {
                        return;
                    }

                    form.addEventListener('submit', function (event) {
                        event.preventDefault();

                        if (!window.isSecureContext) {
                            statusElement.textContent = 'Browser memblokir lokasi karena halaman belum HTTPS. Buka lewat HTTPS atau izinkan lokasi untuk situs ini.';
                            return;
                        }

                        if (!navigator.geolocation) {
                            statusElement.textContent = 'Browser tidak dapat membaca lokasi perangkat.';
                            return;
                        }

                        button.disabled = true;
                        statusElement.textContent = 'Sedang mengambil lokasi perangkat...';

                        navigator.geolocation.getCurrentPosition(
                            function (position) {
                                latitudeInput.value = position.coords.latitude.toFixed(7);
                                longitudeInput.value = position.coords.longitude.toFixed(7);
                                statusElement.textContent = 'Lokasi terbaca, menyimpan absensi...';
                                form.submit();
                            },
                            function (error) {
                                button.disabled = false;
                                if (error && error.code === error.PERMISSION_DENIED) {
                                    statusElement.textContent = 'Akses lokasi ditolak. Izinkan Location untuk situs ini di pengaturan browser lalu coba lagi.';
                                    return;
                                }

                                if (error && error.code === error.POSITION_UNAVAILABLE) {
                                    statusElement.textContent = 'Lokasi perangkat belum tersedia. Pastikan Location Services aktif dan coba lagi.';
                                    return;
                                }

                                if (error && error.code === error.TIMEOUT) {
                                    statusElement.textContent = 'Pengambilan lokasi terlalu lama. Coba lagi di area dengan sinyal lokasi lebih baik.';
                                    return;
                                }

                                statusElement.textContent = 'Tidak dapat mengambil lokasi perangkat. Periksa izin lokasi browser lalu coba lagi.';
                            },
                            {
                                enableHighAccuracy: true,
                                timeout: 10000,
                            }
                        );
                    });
                }

                bindSelfAttendance('self-attendance-form', 'btn-self-attendance', 'self-attendance-latitude', 'self-attendance-longitude', 'self-attendance-status');
                bindSelfAttendance('self-attendance-form-mobile', 'btn-self-attendance-mobile', 'self-attendance-latitude-mobile', 'self-attendance-longitude-mobile', 'self-attendance-status-mobile');
            });
        </script>
    @endpush
@endif

@if ($errors->has('tenant_id') || $errors->has('employee_id') || $errors->has('work_location_id') || $errors->has('date') || $errors->has('check_in') || $errors->has('check_out') || $errors->has('status') || $errors->has('latitude') || $errors->has('longitude'))
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var modalElement = document.getElementById('addAttendanceIndexModal');

                if (modalElement && typeof bootstrap !== 'undefined') {
                    bootstrap.Modal.getOrCreateInstance(modalElement).show();
                }
            });
        </script>
    @endpush
@endif

@endsection
