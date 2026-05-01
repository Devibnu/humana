@extends('layouts.user_type.auth')

@section('content')

<div class="row">
    <div class="col-12">
        <div class="card mx-4 mb-4 shadow-xs">
            <div class="card-header pb-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-1">Tambah Kehadiran</h5>
                    <p class="text-sm text-secondary mb-0">Tambahkan catatan kehadiran dengan tenant, karyawan, lokasi kerja, dan koordinat perangkat yang tervalidasi.</p>
                </div>
                <a href="{{ route('attendances.index') }}" class="btn btn-light btn-sm mb-0"><i class="fas fa-times me-1"></i> Batal</a>
            </div>
            <div class="card-body">
                <x-flash-messages />

                @if ($employees->isEmpty())
                    <div class="border border-secondary border-radius-md p-4 text-center bg-gray-100" data-testid="attendances-create-empty-state">
                        <i class="fas fa-user-clock text-secondary fa-2x mb-3"></i>
                        <h6 class="mb-2">Belum ada karyawan, silakan buat karyawan terlebih dahulu</h6>
                        <p class="text-sm text-secondary mb-0">Kehadiran hanya dapat dicatat jika data karyawan sudah tersedia di tenant terkait.</p>
                    </div>
                @else
                    <form action="{{ route('attendances.store') }}" method="POST" data-testid="attendances-create-form">
                        @csrf
                        @include('attendances._form')
                        <div class="d-flex justify-content-end gap-2 mt-4 flex-wrap">
                            <button type="button" class="btn btn-info mb-0" id="detect-attendance-location"><i class="fas fa-location-arrow me-1"></i> Gunakan Lokasi Perangkat</button>
                            <a href="{{ route('attendances.index') }}" class="btn btn-light mb-0"><i class="fas fa-times me-1"></i> Batal</a>
                            <button type="submit" class="btn bg-gradient-primary mb-0"><i class="fas fa-save me-1"></i> Simpan Kehadiran</button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection