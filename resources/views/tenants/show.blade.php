@extends('layouts.user_type.auth')

@section('breadcrumb_title', 'Tenant')
@section('page_title', 'Detail Tenant')
@section('hide_nav_download', '1')
@section('hide_nav_search', '1')

@section('content')

<style>
    body,
    .main-content,
    .main-content .container-fluid.py-4 {
        background: #fff !important;
    }

    .tenant-detail-page .detail-card {
        background: #fff;
        border: 1px solid #edf2f7;
        box-shadow: none;
    }

    .tenant-detail-page .detail-card .card-header,
    .tenant-detail-page .detail-card .card-footer {
        background: #fff;
    }

    .tenant-detail-page .detail-block {
        background: #fff;
        border: 1px solid #edf2f7;
        border-radius: 1rem;
        padding: 1rem 1.15rem;
        height: 100%;
    }

    .tenant-detail-page .detail-description {
        min-height: 120px;
    }
</style>

<div class="tenant-detail-page">
    <div class="row">
        <div class="col-12">
            <x-flash-messages />

            <div class="card detail-card border-0 mx-4 mb-4" data-testid="tenant-show-card">
                <div class="card-header pb-0 border-0">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                        <div>
                            <p class="text-xs text-uppercase text-secondary font-weight-bold mb-1">Detail Tenant</p>
                            <h4 class="mb-1">{{ $tenant->name }}</h4>
                            <p class="text-sm text-secondary mb-0">Informasi inti tenant dan ringkasan operasional pada sistem.</p>
                        </div>
                        <span class="badge {{ $tenant->status === 'active' ? 'bg-gradient-success' : 'bg-gradient-secondary' }} px-3 py-2">
                            {{ $tenant->status === 'active' ? 'Aktif' : 'Tidak Aktif' }}
                        </span>
                    </div>
                </div>

                <div class="card-body pt-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="detail-block">
                                <p class="text-xs text-uppercase text-secondary font-weight-bold mb-1">Kode:</p>
                                <p class="text-sm font-weight-bold text-dark mb-0">{{ $tenant->code ?? '—' }}</p>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="detail-block">
                                <p class="text-xs text-uppercase text-secondary font-weight-bold mb-1">Domain:</p>
                                <p class="text-sm font-weight-bold text-dark mb-0">{{ $tenant->domain }}</p>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="detail-block">
                                <p class="text-xs text-uppercase text-secondary font-weight-bold mb-1">Kontak:</p>
                                <p class="text-sm font-weight-bold text-dark mb-0">{{ $tenant->contact ?? '—' }}</p>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="detail-block">
                                <p class="text-xs text-uppercase text-secondary font-weight-bold mb-1">Alamat:</p>
                                <p class="text-sm font-weight-bold text-dark mb-0">{{ $tenant->address ?? '—' }}</p>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="detail-block">
                                <p class="text-xs text-uppercase text-secondary font-weight-bold mb-1">Total User:</p>
                                <p class="text-sm font-weight-bold text-dark mb-0">{{ $tenant->users_count }}</p>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="detail-block">
                                <p class="text-xs text-uppercase text-secondary font-weight-bold mb-1">Total Karyawan:</p>
                                <p class="text-sm font-weight-bold text-dark mb-0">{{ $tenant->employees_count }}</p>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="detail-block">
                                <p class="text-xs text-uppercase text-secondary font-weight-bold mb-1">Total Departemen:</p>
                                <p class="text-sm font-weight-bold text-dark mb-0">{{ $tenant->departments_count }}</p>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="detail-block">
                                <p class="text-xs text-uppercase text-secondary font-weight-bold mb-1">Dibuat Pada:</p>
                                <p class="text-sm font-weight-bold text-dark mb-0">{{ optional($tenant->created_at)->format('d/m/Y H:i') }}</p>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="detail-block">
                                <p class="text-xs text-uppercase text-secondary font-weight-bold mb-1">Status:</p>
                                <p class="text-sm font-weight-bold text-dark mb-0">{{ $tenant->status === 'active' ? 'Aktif' : 'Tidak Aktif' }}</p>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="detail-block detail-description" data-testid="tenant-show-branding-preview">
                                <p class="text-xs text-uppercase text-secondary font-weight-bold mb-2">Branding Tenant:</p>
                                @php($activeBrandingPath = $tenant->branding_path)
                                @if($activeBrandingPath)
                                    <p class="text-xs text-secondary mb-3">Satu file branding dipakai untuk logo navbar/sidebar dan favicon aplikasi.</p>
                                    <div class="d-flex align-items-center gap-3 flex-wrap">
                                        <div>
                                            <small class="d-block text-secondary mb-1">Logo</small>
                                            <img src="{{ asset($activeBrandingPath) }}" alt="Logo {{ $tenant->name }}" height="60" class="border rounded bg-white p-1">
                                        </div>
                                        <div>
                                            <small class="d-block text-secondary mb-1">Favicon</small>
                                            <img src="{{ asset($activeBrandingPath) }}" alt="Favicon {{ $tenant->name }}" height="32" class="border rounded bg-white p-1">
                                        </div>
                                    </div>
                                @else
                                    <p class="text-sm text-dark mb-0">Belum ada file branding.</p>
                                @endif
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="detail-block detail-description">
                                <p class="text-xs text-uppercase text-secondary font-weight-bold mb-2">Deskripsi:</p>
                                <p class="text-sm text-dark mb-0">{{ $tenant->description ?: 'Belum ada deskripsi tenant.' }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-footer border-0 pt-0">
                    <a href="{{ route('tenants.index') }}" class="btn btn-light btn-sm mb-0">
                        <i class="fas fa-arrow-left me-1"></i> Kembali
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection