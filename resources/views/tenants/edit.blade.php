@extends('layouts.user_type.auth')

@section('content')

<div class="row">
    <div class="col-12">
        <div class="card mb-4 mx-4 shadow-xs" data-testid="tenants-edit-card">
            <div class="card-header pb-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-1">Edit Tenant</h5>
                    <p class="text-sm text-secondary mb-0">Perbarui identitas tenant, branding, kontak, dan status operasional dengan layout penuh seperti modul departemen.</p>
                </div>
                <a href="{{ route('tenants.index') }}" class="btn btn-light btn-sm mb-0"><i class="fas fa-times me-1"></i> Batal</a>
            </div>
            <div class="card-body">
                @if ($errors->any())
                    <div class="alert alert-danger text-white">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('tenants.update', $tenant) }}" method="POST" enctype="multipart/form-data" data-testid="tenants-edit-form">
                    @csrf
                    @method('PUT')
                    @include('tenants._form')

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="{{ route('tenants.index') }}" class="btn btn-light mb-0"><i class="fas fa-times me-1"></i> Batal</a>
                        <button type="submit" class="btn bg-gradient-primary mb-0"><i class="fas fa-save me-1"></i> Simpan Perubahan</button>
                    </div>
                </form>

                @if($tenant->branding_path)
                    <div class="d-flex justify-content-start mt-3">
                        <button type="button" class="btn btn-outline-danger mb-0" data-testid="btn-remove-branding" data-bs-toggle="modal" data-bs-target="#removeBrandingModal">
                            <i class="fas fa-trash-alt me-1"></i> Hapus Branding
                        </button>
                    </div>

                    <div class="modal fade" id="removeBrandingModal" tabindex="-1" aria-labelledby="removeBrandingModalLabel" aria-hidden="true" data-testid="tenant-remove-branding-modal">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="removeBrandingModalLabel">Konfirmasi Hapus Branding</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                                </div>
                                <div class="modal-body">
                                    Branding untuk tenant <strong>{{ $tenant->name }}</strong> akan dihapus dari sistem.
                                    <div class="text-sm text-secondary mt-2">Aksi ini akan menghapus file branding yang saat ini dipakai tenant.</div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-light mb-0" data-bs-dismiss="modal">Batal</button>
                                    <form action="{{ route('tenants.branding.destroy', $tenant) }}" method="POST" data-testid="tenant-remove-branding-form">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger mb-0" data-testid="confirm-remove-branding">
                                            Hapus Branding
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection