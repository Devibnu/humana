@extends('layouts.user_type.auth')

@section('content')

<div class="row">
    <div class="col-12">
        <div class="card mx-4 mb-4 shadow-xs" data-testid="payroll-generate-card">
            <div class="card-header pb-0 d-flex justify-content-between align-items-start flex-wrap gap-2">
                <div>
                    <h5 class="mb-1">Generate Payroll Bulanan</h5>
                    <p class="text-sm text-secondary mb-0">Buat batch payroll otomatis berdasarkan setting tanggal gajian dan periode perusahaan.</p>
                </div>
                <a href="{{ route('payroll.index') }}" class="btn btn-light btn-sm mb-0">
                    <i class="fas fa-arrow-left me-1"></i> Kembali
                </a>
            </div>
            <div class="card-body">
                <x-flash-messages />

                <div class="row g-4">
                    <div class="col-xl-7">
                        <div class="card border shadow-xs">
                            <div class="card-body">
                                <p class="text-xs text-uppercase text-secondary font-weight-bold mb-1">Proses Payroll</p>
                                <h6 class="mb-3">Pilih perusahaan dan bulan payroll</h6>

                                <form action="{{ route('payroll.generate.store') }}" method="POST" data-testid="payroll-generate-form">
                                    @csrf

                                    <div class="mb-3">
                                        <label class="form-label">Perusahaan</label>
                                        <select name="tenant_id" class="form-control @error('tenant_id') is-invalid @enderror" required>
                                            <option value="">Pilih perusahaan</option>
                                            @foreach ($tenants as $tenant)
                                                <option value="{{ $tenant->id }}" @selected(old('tenant_id') == $tenant->id)>
                                                    {{ $tenant->name }}
                                                    @if ($tenant->payrollSetting)
                                                        - gajian tgl {{ $tenant->payrollSetting->payroll_day }}
                                                    @endif
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('tenant_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Bulan Payroll</label>
                                        <input type="month" name="payroll_month" class="form-control @error('payroll_month') is-invalid @enderror" value="{{ old('payroll_month', $defaultPayrollMonth) }}" required>
                                        @error('payroll_month')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>

                                    <div class="alert alert-info text-white mb-3" role="alert">
                                        Sistem akan membuat payroll untuk semua karyawan aktif. Karyawan yang belum punya template payroll sebelumnya akan dilewati dulu.
                                    </div>

                                    <button type="submit" class="btn bg-gradient-primary mb-0" data-testid="btn-submit-payroll-generate">
                                        <i class="fas fa-cogs me-1"></i> Generate Payroll
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-5">
                        <div class="card border shadow-xs bg-gray-100 h-100">
                            <div class="card-body">
                                <p class="text-xs text-uppercase text-secondary font-weight-bold mb-1">Catatan</p>
                                <h6 class="mb-3">Cara kerja generate</h6>
                                <div class="d-flex gap-3 mb-3">
                                    <span class="badge bg-gradient-primary">1</span>
                                    <p class="text-sm text-secondary mb-0">Periode dihitung dari pengaturan payroll perusahaan.</p>
                                </div>
                                <div class="d-flex gap-3 mb-3">
                                    <span class="badge bg-gradient-info">2</span>
                                    <p class="text-sm text-secondary mb-0">Gaji dan tunjangan memakai payroll terakhir sebagai template.</p>
                                </div>
                                <div class="d-flex gap-3">
                                    <span class="badge bg-gradient-success">3</span>
                                    <p class="text-sm text-secondary mb-0">Potongan absensi dan lembur dihitung ulang dari data periode tersebut.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
