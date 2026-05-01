@extends('layouts.user_type.auth')

@section('content')

<div class="row">
    <div class="col-12">

        <x-flash-messages />

        <div class="card mx-4 mb-4 shadow-xs">
            <div class="card-header pb-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-1">Tambah Karyawan Baru</h5>
                    <p class="text-sm text-secondary mb-0">Tambahkan data inti karyawan terlebih dahulu. Data keluarga dan rekening dapat dikelola setelah karyawan tersimpan.</p>
                </div>
                <a href="{{ route('employees.index') }}" class="btn btn-light btn-sm mb-0">
                    <i class="fas fa-arrow-left me-1"></i> Kembali ke Data Karyawan
                </a>
            </div>

            <div class="card-body">
                <div class="alert alert-info text-white mx-0 mb-4">
                    Setelah data karyawan tersimpan, lengkapi anggota keluarga dan rekening bank melalui halaman detail karyawan.
                </div>

                <form action="{{ route('employees.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @include('employees._form')

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="{{ route('employees.index') }}" class="btn btn-light mb-0">
                            <i class="fas fa-times me-1"></i> Batal
                        </a>
                        <button type="submit" class="btn bg-gradient-primary mb-0">
                            <i class="fas fa-save me-1"></i> Tambah Karyawan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection