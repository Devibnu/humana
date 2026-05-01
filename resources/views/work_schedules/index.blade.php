@extends('layouts.user_type.auth')

@section('content')

@php($activeScheduleFilters = collect([
    $search !== '' ? 'Pencarian: '.$search : null,
    $selectedTenantName ? 'Tenant: '.$selectedTenantName : null,
    $selectedStatus && isset($statuses[$selectedStatus]) ? 'Status: '.$statuses[$selectedStatus] : null,
])->filter()->values())
@php($hasActiveScheduleFilters = $activeScheduleFilters->isNotEmpty())

<div class="row">
    <div class="col-12">
        <x-flash-messages />

        @if ($errors->any())
            <div class="alert alert-danger text-white mx-4">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="card mb-4 mx-4 shadow-xs">
            <div class="card-header pb-0">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                    <div>
                        <h5 class="mb-1">Jadwal Kerja</h5>
                        <p class="text-sm text-secondary mb-0">Kelola jam masuk, jam pulang, dan toleransi untuk karyawan non-shift maupun shift.</p>
                    </div>
                    <div class="d-flex gap-2 flex-wrap align-items-center justify-content-end">
                        @if ($hasActiveScheduleFilters)
                            <span class="badge bg-gradient-info">Filter aktif</span>
                            @foreach ($activeScheduleFilters as $activeScheduleFilter)
                                <span class="badge bg-gradient-light text-dark">{{ $activeScheduleFilter }}</span>
                            @endforeach
                        @endif
                        <button type="button" class="btn bg-gradient-primary btn-sm mb-0" data-bs-toggle="modal" data-bs-target="#addWorkScheduleModal" data-testid="btn-add-work-schedule">
                            <i class="fas fa-plus me-1"></i> Tambah Jadwal
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body px-0 pt-0 pb-2">
                <div class="px-4 pt-4">
                    <div class="row g-3 mb-4">
                        <div class="col-xl-4 col-md-6">
                            <div class="card border shadow-xs h-100">
                                <div class="card-body py-3">
                                    <p class="text-xs text-uppercase text-secondary font-weight-bold mb-1">Total Jadwal</p>
                                    <h5 class="mb-0">{{ $summary['total'] }}</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-4 col-md-6">
                            <div class="card border shadow-xs h-100">
                                <div class="card-body py-3">
                                    <p class="text-xs text-uppercase text-secondary font-weight-bold mb-1">Jadwal Aktif</p>
                                    <h5 class="mb-0 text-success">{{ $summary['active'] }}</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-4 col-md-12">
                            <div class="card border shadow-xs h-100">
                                <div class="card-body py-3">
                                    <p class="text-xs text-uppercase text-secondary font-weight-bold mb-1">Jadwal Nonaktif</p>
                                    <h5 class="mb-0 text-secondary">{{ $summary['inactive'] }}</h5>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="border border-radius-xl p-3 mb-4 bg-gray-100">
                        <form action="{{ route('work-schedules.index') }}" method="GET" class="row g-3 align-items-end" data-testid="work-schedules-filter-form">
                            <div class="col-lg-4 col-md-6">
                                <label for="search" class="form-label">Cari Jadwal</label>
                                <div class="input-group">
                                    <span class="input-group-text text-body"><i class="fas fa-search" aria-hidden="true"></i></span>
                                    <input type="text" id="search" name="search" class="form-control" value="{{ $search }}" placeholder="Cari nama, kode, jam, atau deskripsi">
                                </div>
                            </div>
                            <div class="col-lg-2 col-md-6">
                                <label for="tenant_id" class="form-label">Tenant</label>
                                <select name="tenant_id" id="tenant_id" class="form-control">
                                    <option value="">Semua Tenant</option>
                                    @foreach ($tenants as $tenant)
                                        <option value="{{ $tenant->id }}" @selected($selectedTenantId === $tenant->id)>{{ $tenant->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-2 col-md-6">
                                <label for="status" class="form-label">Status</label>
                                <select name="status" id="status" class="form-control">
                                    <option value="">Semua Status</option>
                                    @foreach ($statuses as $statusValue => $statusLabel)
                                        <option value="{{ $statusValue }}" @selected($selectedStatus === $statusValue)>{{ $statusLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-4 col-md-12">
                                <div class="d-flex gap-2 justify-content-lg-end mt-lg-4">
                                    <button type="submit" class="btn bg-gradient-dark mb-0">
                                        <i class="fas fa-filter me-1"></i> Terapkan
                                    </button>
                                    <a href="{{ route('work-schedules.index') }}" class="btn btn-light mb-0">Reset</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                @if ($workSchedules->isEmpty())
                    <div class="text-center py-5" data-testid="work-schedules-empty-state">
                        <i class="fas fa-clock fa-3x text-secondary mb-3"></i>
                        <p class="text-secondary mb-0">Belum ada jadwal kerja.</p>
                    </div>
                @else
                    <div class="table-responsive p-0">
                        <table class="table align-items-center mb-0" data-testid="work-schedules-table">
                            <thead>
                                <tr>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-4 text-start">Nama Jadwal</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-start">Jam Kerja</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-start">Toleransi</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-start">Tenant</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">Status</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-start">Dipakai</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-start">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($workSchedules as $schedule)
                                    <tr>
                                        <td class="ps-4 text-start">
                                            <h6 class="mb-0 text-sm">{{ $schedule->name }}</h6>
                                            <span class="text-secondary text-xs">{{ $schedule->code }}</span>
                                        </td>
                                        <td class="text-start">
                                            <span class="text-sm font-weight-bold">{{ substr($schedule->check_in_time, 0, 5) }} - {{ substr($schedule->check_out_time, 0, 5) }}</span>
                                        </td>
                                        <td class="text-start">
                                            <span class="text-secondary text-xs">Telat {{ $schedule->late_tolerance_minutes }} menit</span><br>
                                            <span class="text-secondary text-xs">Pulang cepat {{ $schedule->early_leave_tolerance_minutes }} menit</span>
                                        </td>
                                        <td class="text-start"><span class="text-secondary text-sm">{{ $schedule->tenant?->name ?? '—' }}</span></td>
                                        <td class="text-center">
                                            <span class="badge {{ $schedule->status === 'active' ? 'bg-gradient-success' : 'bg-gradient-secondary' }}">{{ $schedule->status === 'active' ? 'Aktif' : 'Nonaktif' }}</span>
                                        </td>
                                        <td class="text-start"><span class="badge bg-info">{{ $schedule->employees_count }} Karyawan</span></td>
                                        <td class="text-start">
                                            <div class="d-flex align-items-center gap-3">
                                                <a href="{{ route('work-schedules.edit', $schedule) }}" class="mx-1" data-bs-toggle="tooltip" title="Edit jadwal" data-testid="btn-edit-work-schedule-{{ $schedule->id }}">
                                                    <i class="fas fa-edit text-secondary"></i>
                                                </a>
                                                <button type="button" class="border-0 bg-transparent p-0 mx-1" data-bs-toggle="modal" data-bs-target="#deleteWorkScheduleModal-{{ $schedule->id }}" title="Hapus jadwal" data-testid="btn-delete-work-schedule-{{ $schedule->id }}">
                                                    <i class="fas fa-trash text-danger"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>

                                    <div class="modal fade" id="deleteWorkScheduleModal-{{ $schedule->id }}" tabindex="-1" aria-labelledby="deleteWorkScheduleModalLabel-{{ $schedule->id }}" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="deleteWorkScheduleModalLabel-{{ $schedule->id }}">Konfirmasi Hapus Jadwal</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                                                </div>
                                                <div class="modal-body">
                                                    Apakah Anda yakin ingin menghapus jadwal <strong>{{ $schedule->name }}</strong>?
                                                </div>
                                                <div class="modal-footer">
                                                    <form action="{{ route('work-schedules.destroy', $schedule) }}" method="POST">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="button" class="btn btn-light mb-0" data-bs-dismiss="modal">Batal</button>
                                                        <button type="submit" class="btn btn-danger mb-0">Hapus</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="px-4 pt-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <p class="text-sm text-secondary mb-0">Menampilkan {{ $workSchedules->count() }} dari total {{ $workSchedules->total() }} jadwal.</p>
                        {{ $workSchedules->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addWorkScheduleModal" tabindex="-1" aria-labelledby="addWorkScheduleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="addWorkScheduleModalLabel">Tambah Jadwal Kerja</h5>
                    <p class="text-sm text-secondary mb-0">Jadwal ini bisa dipilih di form karyawan.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <form action="{{ route('work-schedules.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    @include('work_schedules._form')
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light mb-0" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn bg-gradient-primary mb-0">
                        <i class="fas fa-save me-1"></i> Simpan Jadwal
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
