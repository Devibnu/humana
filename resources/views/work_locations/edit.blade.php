@extends('layouts.user_type.auth')

@section('content')

<div class="row">
    <div class="col-12">
        <x-flash-messages />

        @if ($errors->any())
            <div class="alert alert-danger text-white mx-4">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card mb-4 mx-4 shadow-xs">
            <div class="card-header pb-0">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                    <div>
                        <h5 class="mb-1">Ubah Lokasi Kerja</h5>
                        <p class="text-sm text-secondary mb-0">Perbarui titik GPS dan radius validasi absensi untuk lokasi kerja ini.</p>
                    </div>
                    <div class="d-flex gap-2 flex-wrap justify-content-end">
                        <a href="{{ route('work-schedules.index') }}" class="btn btn-outline-dark btn-sm mb-0">
                            <i class="fas fa-clock me-1"></i> Jadwal Kerja
                        </a>
                        <a href="{{ route('work_locations.index') }}" class="btn btn-light btn-sm mb-0">
                            <i class="fas fa-arrow-left me-1"></i> Kembali
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3 mb-4">
                    <div class="col-lg-4 col-md-6">
                        <div class="card border shadow-xs h-100">
                            <div class="card-body py-3">
                                <p class="text-xs text-uppercase text-secondary font-weight-bold mb-1">Lokasi</p>
                                <h6 class="mb-1">{{ $workLocation->name }}</h6>
                                <p class="text-xs text-secondary mb-0">{{ $workLocation->tenant?->name ?? 'Tenant tidak ditemukan' }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6">
                        <div class="card border shadow-xs h-100">
                            <div class="card-body py-3">
                                <p class="text-xs text-uppercase text-secondary font-weight-bold mb-1">Radius Absensi</p>
                                <h6 class="mb-1 text-info">{{ number_format((int) $workLocation->radius, 0, ',', '.') }} meter</h6>
                                <p class="text-xs text-secondary mb-0">Dipakai untuk validasi jarak perangkat.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-12">
                        <div class="card border shadow-xs h-100">
                            <div class="card-body py-3">
                                <p class="text-xs text-uppercase text-secondary font-weight-bold mb-1">Koordinat</p>
                                <h6 class="mb-1">{{ $workLocation->latitude }}, {{ $workLocation->longitude }}</h6>
                                <p class="text-xs text-secondary mb-0">Gunakan koordinat pusat area absensi.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="alert bg-gradient-info text-white border-0 mb-4">
                    <div class="d-flex align-items-start gap-3">
                        <i class="fas fa-info-circle mt-1"></i>
                        <div>
                            <p class="font-weight-bold mb-1">Lokasi kerja hanya mengatur GPS dan radius.</p>
                            <p class="text-sm mb-0">Jam masuk, jam pulang, toleransi telat, dan shift dikelola terpisah di menu Jadwal Kerja lalu dipilih pada data karyawan.</p>
                        </div>
                    </div>
                </div>

                <form action="{{ route('work_locations.update', $workLocation) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="card border shadow-xs mb-0">
                        <div class="card-header pb-0">
                            <h6 class="mb-1">Detail Lokasi</h6>
                            <p class="text-xs text-secondary mb-0">Pastikan titik koordinat dan radius sesuai area absensi sebenarnya.</p>
                        </div>
                        <div class="card-body">
                            @include('work_locations._form')
                        </div>
                    </div>
                    <div class="d-flex justify-content-end gap-2 mt-4 flex-wrap">
                        <a href="{{ route('work_locations.index') }}" class="btn btn-light mb-0">
                            <i class="fas fa-times me-1"></i> Batal
                        </a>
                        <button type="submit" class="btn bg-gradient-primary mb-0">
                            <i class="fas fa-save me-1"></i> Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection
