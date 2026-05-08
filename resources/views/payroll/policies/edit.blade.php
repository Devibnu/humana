@extends('layouts.user_type.auth')

@section('content')
<div class="row">
    <div class="col-12">
        <x-flash-messages />

        <div class="card mx-4 mb-4 shadow-xs">
            <div class="card-header pb-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-1">Edit Payroll Policy</h5>
                    <p class="text-sm text-secondary mb-0">Perbarui kebijakan payroll dan komponen perhitungannya.</p>
                </div>
                <a href="{{ route('payroll.policies.index', ['tenant_id' => $policy->tenant_id]) }}" class="btn btn-light btn-sm mb-0">
                    <i class="fas fa-arrow-left me-1"></i> Kembali
                </a>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('payroll.policies.update', $policy) }}">
                    @csrf
                    @method('PUT')
                    @include('payroll.policies._form')
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
