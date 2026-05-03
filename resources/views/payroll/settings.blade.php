@extends('layouts.user_type.auth')

@section('content')

<div class="row">
    <div class="col-12">
        <div class="card mx-4 mb-4 shadow-xs" data-testid="payroll-settings-card">
            <div class="card-header pb-0 d-flex justify-content-between align-items-start flex-wrap gap-2">
                <div>
                    <h5 class="mb-1">Pengaturan Payroll Perusahaan</h5>
                    <p class="text-sm text-secondary mb-0">Atur tanggal gajian dan periode cut-off payroll sesuai kebijakan masing-masing perusahaan.</p>
                </div>
                <a href="{{ route('payroll.index') }}" class="btn btn-light btn-sm mb-0">
                    <i class="fas fa-arrow-left me-1"></i> Kembali
                </a>
            </div>
            <div class="card-body">
                <x-flash-messages />

                <div class="row g-4">
                    @foreach ($tenants as $tenant)
                        @php($setting = $tenant->payrollSetting)
                        <div class="col-xl-6">
                            <div class="card border shadow-xs h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                                        <div>
                                            <p class="text-xs text-uppercase text-secondary font-weight-bold mb-1">Perusahaan</p>
                                            <h6 class="mb-1">{{ $tenant->name }}</h6>
                                            <p class="text-sm text-secondary mb-0">{{ $tenant->domain ?? $tenant->slug }}</p>
                                        </div>
                                        <span class="badge {{ ($setting?->status ?? 'active') === 'active' ? 'bg-gradient-success' : 'bg-gradient-secondary' }}">
                                            {{ strtoupper($setting?->status ?? 'active') }}
                                        </span>
                                    </div>

                                    <form action="{{ route('payroll.settings.update') }}" method="POST" data-testid="payroll-setting-form-{{ $tenant->id }}">
                                        @csrf
                                        <input type="hidden" name="tenant_id" value="{{ $tenant->id }}">

                                        <div class="row g-3">
                                            <div class="col-md-4">
                                                <label class="form-label">Tanggal Gajian</label>
                                                <input type="number" min="1" max="31" name="payroll_day" class="form-control" value="{{ old('payroll_day', $setting?->payroll_day ?? 25) }}" required>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Awal Periode</label>
                                                <input type="number" min="1" max="31" name="period_start_day" class="form-control" value="{{ old('period_start_day', $setting?->period_start_day ?? 1) }}" required>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Akhir Cut-off</label>
                                                <input type="number" min="1" max="31" name="period_end_day" class="form-control" value="{{ old('period_end_day', $setting?->period_end_day ?? 31) }}" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Bulan Periode</label>
                                                <select name="period_month_offset" class="form-control" required>
                                                    <option value="current" @selected(old('period_month_offset', $setting?->period_month_offset ?? 'current') === 'current')>Bulan gajian berjalan</option>
                                                    <option value="previous" @selected(old('period_month_offset', $setting?->period_month_offset ?? 'current') === 'previous')>Bulan sebelum tanggal gajian</option>
                                                </select>
                                                <small class="text-muted">Pilih previous untuk payroll tanggal 1/5 dengan periode bulan sebelumnya.</small>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Status</label>
                                                <select name="status" class="form-control" required>
                                                    <option value="active" @selected(old('status', $setting?->status ?? 'active') === 'active')>Aktif</option>
                                                    <option value="inactive" @selected(old('status', $setting?->status ?? 'active') === 'inactive')>Tidak Aktif</option>
                                                </select>
                                            </div>
                                            <div class="col-12">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" name="publish_slips_on_approval" value="1" id="publish-{{ $tenant->id }}" @checked(old('publish_slips_on_approval', $setting?->publish_slips_on_approval ?? false))>
                                                    <label class="form-check-label" for="publish-{{ $tenant->id }}">Publish slip otomatis setelah payroll disetujui</label>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="d-flex justify-content-end mt-4">
                                            <button type="submit" class="btn bg-gradient-primary mb-0">
                                                <i class="fas fa-save me-1"></i> Simpan Pengaturan
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
