@extends('layouts.user_type.auth')

@section('content')

<div class="row">
    <div class="col-12">
        <div class="card mx-4 mb-4 shadow-xs">
            <div class="card-header pb-0 d-flex justify-content-between align-items-center">
                <h5 class="mb-1">Tambah Aturan Absensi</h5>
                <a href="{{ route('absence_rules.index') }}" class="btn btn-light btn-sm">Batal</a>
            </div>
            <div class="card-body">
                <form action="{{ route('absence_rules.store') }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label">Tenant</label>
                        <select name="tenant_id" class="form-control">
                            @foreach($tenants as $tenant)
                                <option value="{{ $tenant->id }}">{{ $tenant->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3"><label>Jam Kerja per Hari</label>
                        <input type="number" name="working_hours_per_day" class="form-control" value="8">
                    </div>
                    <div class="mb-3"><label>Hari Kerja per Bulan</label>
                        <input type="number" name="working_days_per_month" class="form-control" value="22">
                    </div>
                    <div class="mb-3"><label>Toleransi Telat (menit)</label>
                        <input type="number" name="tolerance_minutes" class="form-control" value="15">
                    </div>
                    <div class="mb-3"><label>Tipe Rate Potongan</label>
                        <select name="rate_type" class="form-select">
                          <option value="proportional">Proporsional (gaji ÷ jam kerja)</option>
                          <option value="flat">Flat (nominal per menit)</option>
                        </select>
                    </div>
                    <div class="form-check mb-3">
                        <input type="checkbox" name="alpha_full_day" class="form-check-input" checked>
                        <label class="form-check-label">Alpha potong penuh 1 hari</label>
                    </div>
                    <button type="submit" class="btn bg-gradient-primary">Simpan Aturan</button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection
