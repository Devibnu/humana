@extends('layouts.user_type.auth')

@section('content')

<div class="row">
    <div class="col-12">
        <div class="card mb-4 mx-4 shadow-xs">
            <div class="card-header pb-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-1">Tambah Lokasi Kerja Baru</h5>
                    <p class="text-sm text-secondary mb-0">Tambahkan lokasi kerja dengan tenant, radius validasi kehadiran, dan koordinat operasional yang akurat.</p>
                </div>
                <a href="{{ route('work_locations.index') }}" class="btn btn-light btn-sm mb-0"><i class="fas fa-times me-1"></i> Batal</a>
            </div>
            <div class="card-body">
                <x-flash-messages />

                @if ($tenants->isEmpty())
                    <div class="border border-secondary border-radius-md p-4 text-center bg-gray-100" data-testid="work-locations-create-empty-state">
                        <i class="fas fa-map-marker-alt text-secondary fa-2x mb-3"></i>
                        <h6 class="mb-2">Belum ada tenant, silakan buat tenant terlebih dahulu</h6>
                        <p class="text-sm text-secondary mb-0">Tenant diperlukan agar lokasi kerja dapat tersimpan sesuai ruang lingkup operasional.</p>
                    </div>
                @else
                    <form action="{{ route('work_locations.store') }}" method="POST" data-testid="work-locations-create-form">
                        @csrf
                        @include('work_locations._form')
                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="{{ route('work_locations.index') }}" class="btn btn-light mb-0"><i class="fas fa-times me-1"></i> Batal</a>
                            <button type="submit" class="btn bg-gradient-primary mb-0"><i class="fas fa-save me-1"></i> Simpan Lokasi Kerja</button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection
