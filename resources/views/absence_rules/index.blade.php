@extends('layouts.user_type.auth')

@section('content')

<div class="container-fluid py-4">
  <div class="row">
    <div class="col-12">
      <div class="card mb-4 mx-4 shadow-xs">
        <div class="card-header pb-0 d-flex justify-content-between align-items-center">
          <h6 class="mb-1">Aturan Absensi</h6>
          <a href="{{ route('absence_rules.create') }}" class="btn bg-gradient-primary btn-sm">Tambah Aturan</a>
        </div>
        <div class="card-body px-0 pt-0 pb-2">
          <div class="table-responsive p-0">
            <table class="table align-items-center mb-0">
              <thead>
                <tr>
                  <th>Tenant</th>
                  <th>Jam/Hari</th>
                  <th>Hari/Bulan</th>
                  <th>Toleransi (menit)</th>
                  <th>Rate Type</th>
                  <th>Alpha Full Day</th>
                </tr>
              </thead>
              <tbody>
                @forelse($rules as $rule)
                  <tr>
                    <td>{{ $rule->tenant->name ?? '-' }}</td>
                    <td>{{ $rule->working_hours_per_day }}</td>
                    <td>{{ $rule->working_days_per_month }}</td>
                    <td>{{ $rule->tolerance_minutes }}</td>
                    <td>{{ ucfirst($rule->rate_type) }}</td>
                    <td>{{ $rule->alpha_full_day ? 'Ya' : 'Tidak' }}</td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="6" class="text-center">Belum ada aturan absensi.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>

          <div class="px-4 pt-3">
            {{ $rules->links() }}
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

@endsection
