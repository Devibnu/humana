@extends('layouts.user_type.auth')

@section('content')

<style>
    .payroll-setting-card {
        border-radius: 12px;
        transition: box-shadow 0.2s ease;
    }
    .payroll-setting-column {
        width: 100%;
    }
    .payroll-setting-card .card-body {
        padding: 1.5rem;
    }
    @media (min-width: 1200px) {
        .payroll-setting-card .card-body {
            padding: 1.75rem 2rem;
        }
    }
    .payroll-setting-card:hover {
        box-shadow: 0 8px 24px rgba(0,0,0,0.10) !important;
    }
    .company-avatar {
        width: 44px;
        height: 44px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        font-weight: 700;
        color: #fff;
        flex-shrink: 0;
        background: linear-gradient(135deg, #627594 0%, #3a416f 100%);
    }
    .period-preview-box {
        background: linear-gradient(135deg, #f0f4ff 0%, #e8f0fe 100%);
        border: 1px solid #d0dcff;
        border-radius: 10px;
        padding: 12px 16px;
    }
    .period-step {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 0.78rem;
        font-weight: 600;
        color: #3a416f;
    }
    .period-step .dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        flex-shrink: 0;
    }
    .period-divider {
        flex: 1;
        height: 2px;
        background: repeating-linear-gradient(90deg, #aab6d0 0, #aab6d0 4px, transparent 4px, transparent 8px);
        margin: 0 6px;
    }
    .input-day-wrapper {
        position: relative;
    }
    .input-day-wrapper .day-suffix {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 0.72rem;
        color: #8392ab;
        pointer-events: none;
    }
    .input-day-wrapper input {
        padding-right: 36px;
    }
    .section-label {
        font-size: 0.68rem;
        font-weight: 700;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: #8392ab;
        margin-bottom: 10px;
    }
    .form-label {
        font-size: 0.78rem;
        font-weight: 600;
        color: #344767;
        margin-bottom: 5px;
    }
    .form-control, .form-select {
        font-size: 0.85rem;
        border-radius: 8px;
    }
    .badge-status {
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.7rem;
        font-weight: 700;
        letter-spacing: 0.04em;
    }
    .card-section-divider {
        border: none;
        border-top: 1px dashed #e9ecef;
        margin: 16px 0;
    }
</style>

<div class="row">
    <div class="col-12">
        <div class="card mx-4 mb-4 shadow-xs" data-testid="payroll-settings-card">
            {{-- Page Header --}}
            <div class="card-header pb-0">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div style="width:42px;height:42px;border-radius:10px;background:linear-gradient(135deg,#627594,#3a416f);display:flex;align-items:center;justify-content:center;">
                            <i class="fas fa-sliders-h text-white" style="font-size:1rem;"></i>
                        </div>
                        <div>
                            <h5 class="mb-0 font-weight-bold">Pengaturan Payroll</h5>
                            <p class="text-sm text-secondary mb-0">Atur jadwal gajian &amp; periode cut-off per perusahaan</p>
                        </div>
                    </div>
                    <a href="{{ route('payroll.index') }}" class="btn btn-outline-secondary btn-sm mb-0 d-flex align-items-center gap-1">
                        <i class="fas fa-arrow-left" style="font-size:0.75rem;"></i>
                        <span>Kembali</span>
                    </a>
                </div>
                <hr class="horizontal dark mt-3 mb-0">
            </div>

            <div class="card-body pt-4">
                <x-flash-messages />

                <div class="row g-4">
                    @foreach ($tenants as $tenant)
                        @php
                            $setting = $tenant->payrollSetting;
                            $payrollDay    = old('payroll_day',        $setting?->payroll_day        ?? 25);
                            $periodStart   = old('period_start_day',   $setting?->period_start_day   ?? 1);
                            $periodEnd     = old('period_end_day',     $setting?->period_end_day     ?? 31);
                            $monthOffset   = old('period_month_offset',$setting?->period_month_offset ?? 'current');
                            $status        = old('status',             $setting?->status             ?? 'active');
                            $initials      = strtoupper(substr($tenant->name, 0, 2));
                        @endphp
                        <div class="col-12 payroll-setting-column">
                            <div class="card border shadow-xs h-100 payroll-setting-card">
                                {{-- Company Header --}}
                                <div class="card-body pb-2">
                                    <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="company-avatar">{{ $initials }}</div>
                                            <div>
                                                <p class="section-label mb-0">Perusahaan</p>
                                                <h6 class="mb-0 font-weight-bold">{{ $tenant->name }}</h6>
                                                <p class="text-xs text-secondary mb-0">
                                                    <i class="fas fa-link me-1" style="font-size:0.65rem;"></i>
                                                    {{ $tenant->domain ?? $tenant->slug }}
                                                </p>
                                            </div>
                                        </div>
                                        <span class="badge-status {{ $status === 'active' ? 'bg-gradient-success text-white' : 'badge bg-gradient-secondary text-white' }}">
                                            <i class="fas {{ $status === 'active' ? 'fa-check-circle' : 'fa-times-circle' }} me-1"></i>
                                            {{ $status === 'active' ? 'Aktif' : 'Nonaktif' }}
                                        </span>
                                    </div>

                                    {{-- Period Visual Preview --}}
                                    <div class="period-preview-box mb-3">
                                        <p class="section-label mb-2">
                                            <i class="fas fa-calendar-alt me-1"></i> Gambaran Periode Gajian
                                        </p>
                                        <div class="d-flex align-items-center">
                                            <div class="period-step">
                                                <span class="dot" style="background:#5e72e4;"></span>
                                                <span>Tgl {{ $periodStart }}<br><span style="font-weight:400;color:#6c757d;">Awal Periode</span></span>
                                            </div>
                                            <div class="period-divider"></div>
                                            <div class="period-step">
                                                <span class="dot" style="background:#f5365c;"></span>
                                                <span>Tgl {{ $periodEnd }}<br><span style="font-weight:400;color:#6c757d;">Cut-off</span></span>
                                            </div>
                                            <div class="period-divider"></div>
                                            <div class="period-step">
                                                <span class="dot" style="background:#2dce89;"></span>
                                                <span>Tgl {{ $payrollDay }}<br><span style="font-weight:400;color:#6c757d;">Gajian</span></span>
                                            </div>
                                        </div>
                                    </div>

                                    <hr class="card-section-divider">

                                    <form action="{{ route('payroll.settings.update') }}" method="POST" data-testid="payroll-setting-form-{{ $tenant->id }}">
                                        @csrf
                                        <input type="hidden" name="tenant_id" value="{{ $tenant->id }}">

                                        {{-- Tanggal Section --}}
                                        <p class="section-label">
                                            <i class="fas fa-calendar-day me-1"></i> Tanggal Payroll
                                        </p>
                                        <div class="row g-3 mb-3">
                                            <div class="col-md-4">
                                                <label class="form-label">
                                                    Tanggal Gajian
                                                    <i class="fas fa-info-circle text-secondary ms-1" data-bs-toggle="tooltip" title="Tanggal karyawan menerima gaji"></i>
                                                </label>
                                                <div class="input-day-wrapper">
                                                    <input type="number" min="1" max="31" name="payroll_day"
                                                        class="form-control"
                                                        value="{{ $payrollDay }}" required>
                                                    <span class="day-suffix">hari</span>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">
                                                    Awal Periode
                                                    <i class="fas fa-info-circle text-secondary ms-1" data-bs-toggle="tooltip" title="Hari mulai penghitungan absensi/cut-off"></i>
                                                </label>
                                                <div class="input-day-wrapper">
                                                    <input type="number" min="1" max="31" name="period_start_day"
                                                        class="form-control"
                                                        value="{{ $periodStart }}" required>
                                                    <span class="day-suffix">hari</span>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">
                                                    Akhir Cut-off
                                                    <i class="fas fa-info-circle text-secondary ms-1" data-bs-toggle="tooltip" title="Hari terakhir absensi dihitung dalam periode ini"></i>
                                                </label>
                                                <div class="input-day-wrapper">
                                                    <input type="number" min="1" max="31" name="period_end_day"
                                                        class="form-control"
                                                        value="{{ $periodEnd }}" required>
                                                    <span class="day-suffix">hari</span>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Config Section --}}
                                        <p class="section-label">
                                            <i class="fas fa-cog me-1"></i> Konfigurasi
                                        </p>
                                        <div class="row g-3 mb-3">
                                            <div class="col-md-6">
                                                <label class="form-label">Bulan Periode</label>
                                                <select name="period_month_offset" class="form-control" required>
                                                    <option value="current"  @selected($monthOffset === 'current')>Bulan gajian berjalan</option>
                                                    <option value="previous" @selected($monthOffset === 'previous')>Bulan sebelum tanggal gajian</option>
                                                </select>
                                                <small class="text-muted d-block mt-1">
                                                    <i class="fas fa-lightbulb text-warning me-1"></i>
                                                    Pilih <em>previous</em> jika payroll tgl&nbsp;1 mencakup bulan sebelumnya.
                                                </small>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Status</label>
                                                <select name="status" class="form-control" required>
                                                    <option value="active"   @selected($status === 'active')>Aktif</option>
                                                    <option value="inactive" @selected($status === 'inactive')>Tidak Aktif</option>
                                                </select>
                                                <small class="text-muted d-block mt-1">
                                                    <i class="fas fa-info-circle me-1"></i>
                                                    Nonaktifkan untuk menangguhkan sementara.
                                                </small>
                                            </div>
                                        </div>

                                        {{-- Toggle --}}
                                        <div class="p-3 rounded-3 mb-3" style="background:#f8f9fa;border:1px solid #e9ecef;">
                                            <div class="form-check form-switch mb-0 d-flex align-items-start gap-2">
                                                <input class="form-check-input mt-1" type="checkbox"
                                                    name="publish_slips_on_approval" value="1"
                                                    id="publish-{{ $tenant->id }}"
                                                    @checked(old('publish_slips_on_approval', $setting?->publish_slips_on_approval ?? false))>
                                                <div>
                                                    <label class="form-check-label fw-semibold text-sm" for="publish-{{ $tenant->id }}">
                                                        Auto-publish slip gaji
                                                    </label>
                                                    <p class="text-xs text-secondary mb-0">
                                                        Slip gaji otomatis dipublikasikan ke karyawan setelah payroll disetujui.
                                                    </p>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="d-flex justify-content-end">
                                            <button type="submit" class="btn bg-gradient-primary btn-sm mb-0 px-4">
                                                <i class="fas fa-save me-1"></i> Simpan Pengaturan
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                @if($tenants->isEmpty())
                    <div class="text-center py-5">
                        <i class="fas fa-building text-secondary" style="font-size:3rem;opacity:0.3;"></i>
                        <p class="text-secondary mt-3 mb-0">Belum ada perusahaan yang terdaftar.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@push('js')
<script>
    // Init Bootstrap tooltips
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
        new bootstrap.Tooltip(el, { placement: 'top', trigger: 'hover' });
    });
</script>
@endpush

@endsection
