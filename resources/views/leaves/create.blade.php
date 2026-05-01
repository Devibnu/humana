@extends('layouts.user_type.auth')

@section('content')

<div class="row">
    <div class="col-12">
        <div class="card mx-4 mb-4 shadow-xs">
            <div class="card-header pb-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-1">Tambah Permintaan Cuti</h5>
                    <p class="text-sm text-secondary mb-0">Ajukan permintaan cuti dengan tenant, karyawan, periode, dan alasan yang jelas agar proses review lebih cepat.</p>
                </div>
                <a href="{{ route('leaves.index') }}" class="btn btn-light btn-sm mb-0"><i class="fas fa-times me-1"></i> Batal</a>
            </div>
            <div class="card-body">
                <x-flash-messages />

                @if ($employees->isEmpty())
                    <div class="border border-secondary border-radius-md p-4 text-center bg-gray-100" data-testid="leaves-create-empty-state">
                        <i class="fas fa-calendar-times text-secondary fa-2x mb-3"></i>
                        <h6 class="mb-2">Belum ada karyawan, silakan buat karyawan terlebih dahulu</h6>
                        <p class="text-sm text-secondary mb-0">Permintaan cuti hanya dapat dibuat jika data karyawan sudah tersedia pada tenant yang dipilih.</p>
                    </div>
                @else
                    <form action="{{ route('leaves.store') }}" method="POST" enctype="multipart/form-data" data-testid="leaves-create-form">
                        @csrf
                        @include('leaves._form')
                        <div class="d-flex justify-content-end gap-2 mt-4 flex-wrap">
                            <a href="{{ route('leaves.index') }}" class="btn btn-light mb-0"><i class="fas fa-times me-1"></i> Batal</a>
                            <button type="submit" class="btn bg-gradient-primary mb-0"><i class="fas fa-save me-1"></i> Simpan Permintaan</button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection