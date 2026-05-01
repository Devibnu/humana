@extends('layouts.user_type.auth')

@section('content')

<div class="row">
    <div class="col-12">
        <x-flash-messages />

        <div class="card mx-4 mb-4 shadow-xs">
            <div class="card-header pb-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-1">Ubah Data Karyawan</h5>
                    <p class="text-sm text-secondary mb-0">Perbarui data inti karyawan. Data keluarga dan rekening dikelola terpisah melalui halaman detail karyawan.</p>
                </div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <a href="{{ route('employees.show', $employee) }}" class="btn btn-outline-primary btn-sm mb-0">
                        <i class="fas fa-id-card me-1"></i> Lihat Detail Karyawan
                    </a>
                    <a href="{{ route('employees.index') }}" class="btn btn-light btn-sm mb-0">
                        <i class="fas fa-arrow-left me-1"></i> Kembali ke Data Karyawan
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="alert alert-info text-white mx-0 mb-4">
                    Anggota keluarga dan rekening bank tidak diubah dari form ini. Gunakan halaman detail karyawan untuk mengelola data pendukung tersebut.
                </div>

                <form action="{{ route('employees.update', $employee) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    @include('employees._form')
                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="{{ route('employees.index') }}" class="btn btn-light mb-0">
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