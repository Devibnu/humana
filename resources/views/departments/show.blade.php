@extends('layouts.user_type.auth')

@section('breadcrumb_title', 'Departemen')
@section('page_title', 'Detail Departemen')
@section('hide_nav_download', '1')
@section('hide_nav_search', '1')

@section('content')

<style>
    body,
    .main-content,
    .main-content .container-fluid.py-4 {
        background: #fff !important;
    }

    .department-detail-page .detail-card {
        background: #fff;
        border: 1px solid #edf2f7;
        box-shadow: none;
    }

    .department-detail-page .detail-card .card-header,
    .department-detail-page .detail-card .card-footer {
        background: #fff;
    }

    .department-detail-page .detail-block {
        background: #fff;
        border: 1px solid #edf2f7;
        border-radius: 1rem;
        padding: 1rem 1.15rem;
        height: 100%;
    }

    .department-detail-page .detail-description {
        min-height: 120px;
    }
</style>

<div class="department-detail-page">
    <div class="row">
        <div class="col-12">
        <x-flash-messages />

            <div class="card detail-card border-0 mx-4 mb-4">
                <div class="card-header pb-0 border-0">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                        <div>
                            <p class="text-xs text-uppercase text-secondary font-weight-bold mb-1">Detail Departemen</p>
                            <h4 class="mb-1">{{ $department->name }}</h4>
                            <p class="text-sm text-secondary mb-0">Informasi inti departemen yang aktif di tenant terkait.</p>
                        </div>
                        <span class="badge {{ $department->status === 'active' ? 'bg-gradient-success' : 'bg-gradient-secondary' }} px-3 py-2">
                            {{ $department->status === 'active' ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </div>
                </div>

                <div class="card-body pt-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="detail-block">
                                <p class="text-xs text-uppercase text-secondary font-weight-bold mb-1">Kode:</p>
                                <p class="text-sm font-weight-bold text-dark mb-0">{{ $department->code ?? '—' }}</p>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="detail-block">
                                <p class="text-xs text-uppercase text-secondary font-weight-bold mb-1">Tenant:</p>
                                <p class="text-sm font-weight-bold text-dark mb-0">{{ $department->tenant?->name ?? '—' }}</p>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="detail-block">
                                <p class="text-xs text-uppercase text-secondary font-weight-bold mb-1">Status:</p>
                                <p class="text-sm font-weight-bold text-dark mb-0">
                                    {{ $department->status === 'active' ? 'Aktif' : 'Nonaktif' }}
                                </p>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="detail-block">
                                <p class="text-xs text-uppercase text-secondary font-weight-bold mb-1">Nama Departemen:</p>
                                <p class="text-sm font-weight-bold text-dark mb-0">{{ $department->name }}</p>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="detail-block detail-description">
                                <p class="text-xs text-uppercase text-secondary font-weight-bold mb-2">Deskripsi:</p>
                                <p class="text-sm text-dark mb-0">
                                    {{ $department->description ?: 'Belum ada deskripsi departemen.' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-footer border-0 pt-0">
                    <a href="{{ route('departments.index') }}" class="btn btn-light btn-sm mb-0">
                        <i class="fas fa-arrow-left me-1"></i> Kembali
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
