@extends('layouts.user_type.auth')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card mx-4 mb-4 shadow-xs">
            <div class="card-header pb-0 d-flex justify-content-between align-items-start flex-wrap gap-2">
                <div>
                    <h5 class="mb-1">Tambah Jadwal Kerja</h5>
                    <p class="text-sm text-secondary mb-0">Tambahkan pola jam masuk, jam pulang, dan toleransi absensi.</p>
                </div>
                <a href="{{ route('work-schedules.index') }}" class="btn btn-light btn-sm mb-0">
                    <i class="fas fa-arrow-left me-1"></i> Kembali
                </a>
            </div>
            <div class="card-body">
                <form action="{{ route('work-schedules.store') }}" method="POST">
                    @csrf
                    @include('work_schedules._form')
                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="{{ route('work-schedules.index') }}" class="btn btn-light mb-0">Batal</a>
                        <button type="submit" class="btn bg-gradient-primary mb-0">
                            <i class="fas fa-save me-1"></i> Simpan Jadwal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
