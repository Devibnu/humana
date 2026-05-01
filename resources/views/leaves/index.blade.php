@extends('layouts.user_type.auth')

@section('content')

@php($currentUser = $currentUser ?? auth()->user())
@php($statusLabels = ['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'])
@php($statusClasses = ['pending' => 'bg-gradient-warning text-dark', 'approved' => 'bg-gradient-success', 'rejected' => 'bg-gradient-danger'])
@php($approvalStageLabels = ['supervisor' => 'Supervisor', 'manager' => 'Manager', 'hr' => 'HR'])
@php($approvalStageClasses = ['supervisor' => 'bg-gradient-info', 'manager' => 'bg-gradient-warning text-dark', 'hr' => 'bg-gradient-primary'])

<div class="row">
    <div class="col-12">
        <x-flash-messages />

        <div class="card mb-4 mx-4 shadow-xs">
            <div class="card-header pb-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-1">Daftar Permintaan Cuti</h5>
                    <p class="text-sm text-secondary mb-0">Audit permintaan cuti karyawan dengan filter tanggal, ringkasan status, dan aksi operasional yang konsisten.</p>
                    @if ($currentUser && ($currentUser->isSupervisor() || $currentUser->isManager()))
                        <p class="text-xs text-secondary mb-0 mt-2">Tenant terkunci: Anda hanya melihat permintaan cuti dari {{ $currentUser->tenant?->name ?? 'tenant Anda' }}.</p>
                    @endif
                    @if ($currentUser && $currentUser->isEmployee())
                        <p class="text-xs text-secondary mb-0 mt-2">Anda hanya melihat permintaan cuti milik Anda sendiri.</p>
                    @endif
                </div>
                @if ($currentUser && ($currentUser->isAdminHr() || $currentUser->isEmployee() || $currentUser->isManager() || $currentUser->isSupervisor()))
                    <div class="d-flex flex-wrap gap-2 justify-content-end">
                        @if ($currentUser->isAdminHr() || $currentUser->isManager())
                            <a href="{{ route('jenis-cuti.index') }}" class="btn btn-light btn-sm mb-0">
                                <i class="fas fa-layer-group me-1"></i> Jenis Cuti
                            </a>
                        @endif
                        @if ($currentUser->isAdminHr() || $currentUser->isEmployee())
                            <button type="button" class="btn bg-gradient-primary btn-sm mb-0" data-bs-toggle="modal" data-bs-target="#addLeaveIndexModal" data-testid="btn-open-add-leave-modal">
                                <i class="fas fa-plus me-1"></i> Tambah Permintaan Cuti
                            </button>
                        @endif
                    </div>
                @endif
            </div>
            <div class="card-body px-0 pt-0 pb-2">
                <div class="px-4 py-3 border-bottom">
                    <form action="{{ route('leaves.index') }}" method="GET" class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label">Tanggal Mulai</label>
                            <input type="date" name="start_date" class="form-control" value="{{ $selectedStartDate }}" data-testid="leave-start-date-filter">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Tanggal Selesai</label>
                            <input type="date" name="end_date" class="form-control" value="{{ $selectedEndDate }}" data-testid="leave-end-date-filter">
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex gap-2 flex-wrap">
                                <button type="submit" class="btn bg-gradient-dark mb-0" data-testid="btn-apply-leave-filter">
                                    <i class="fas fa-filter me-1"></i> Terapkan Filter
                                </button>
                                <a href="{{ route('leaves.index') }}" class="btn btn-light mb-0">Reset</a>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="px-4 py-3 border-bottom">
                    <div class="d-flex flex-wrap gap-2">
                        <span class="badge bg-gradient-warning text-dark" data-testid="leaves-summary-pending">Pending: {{ $summary['pending']['requests'] ?? 0 }} permintaan / {{ $summary['pending']['days'] ?? 0 }} hari</span>
                        <span class="badge bg-gradient-success" data-testid="leaves-summary-approved">Approved: {{ $summary['approved']['requests'] ?? 0 }} permintaan / {{ $summary['approved']['days'] ?? 0 }} hari</span>
                        <span class="badge bg-gradient-danger" data-testid="leaves-summary-rejected">Rejected: {{ $summary['rejected']['requests'] ?? 0 }} permintaan / {{ $summary['rejected']['days'] ?? 0 }} hari</span>
                    </div>
                </div>

                @if ($leaves->isEmpty())
                    <div class="text-center py-5" data-testid="leaves-empty-state">
                        <i class="fas fa-calendar-times fa-3x text-secondary mb-3"></i>
                        <p class="text-secondary mb-0">Belum ada permintaan cuti untuk periode ini</p>
                    </div>
                @else
                    <div class="table-responsive p-0">
                        <table class="table align-items-center mb-0" data-testid="leaves-table">
                            <thead>
                                <tr>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-4">Karyawan</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Jenis Cuti</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">Tanggal Mulai</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">Tanggal Selesai</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">Durasi</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">Status</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">Tahap Persetujuan</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Alasan</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($leaves as $leave)
                                    @php($canReviewLeave = $currentUser && ($currentUser->isAdminHr() || ($currentUser->isSupervisor() && $leave->current_approval_role === 'supervisor' && $currentUser->hasMenuAccess('leaves.approval.supervisor')) || ($currentUser->isManager() && $leave->current_approval_role === 'manager' && $currentUser->hasMenuAccess('leaves.approval.manager'))))
                                    <tr>
                                        <td class="ps-4">
                                            <h6 class="mb-0 text-sm">{{ $leave->employee?->name ?? '—' }}</h6>
                                            <p class="text-xs text-secondary mb-0">{{ $leave->employee?->employee_code ?? '—' }}</p>
                                        </td>
                                        <td>
                                            <span class="text-sm font-weight-bold">{{ $leave->leaveType?->name ?? '—' }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="text-secondary text-sm font-weight-bold">{{ optional($leave->start_date)->format('d M Y') ?? '—' }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="text-secondary text-sm font-weight-bold">{{ optional($leave->end_date)->format('d M Y') ?? '—' }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="text-secondary text-sm font-weight-bold">{{ $leave->duration }} hari</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge {{ $statusClasses[$leave->status] ?? 'bg-secondary' }}" data-testid="leave-status-{{ $leave->id }}">
                                                {{ $statusLabels[$leave->status] ?? ucfirst($leave->status) }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            @php($approvalStageLabel = $leave->status === 'approved' ? 'Approved' : ($leave->status === 'rejected' ? 'Rejected' : ($approvalStageLabels[$leave->current_approval_role] ?? 'Menunggu')))
                                            @php($approvalStageClass = $leave->status === 'approved' ? 'bg-gradient-success' : ($leave->status === 'rejected' ? 'bg-gradient-danger' : ($approvalStageClasses[$leave->current_approval_role] ?? 'bg-secondary')))
                                            <span class="badge {{ $approvalStageClass }}" data-testid="leave-stage-{{ $leave->id }}">
                                                {{ $approvalStageLabel }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="text-secondary text-sm font-weight-bold">{{ \Illuminate\Support\Str::limit($leave->reason, 80) }}</span>
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex justify-content-center align-items-center gap-3">
                                                @if ($leave->employee)
                                                    <a href="{{ route('employees.leaves.show', $leave->employee) }}" class="mx-1" data-bs-toggle="tooltip" title="Lihat" data-testid="btn-view-leave-{{ $leave->id }}">
                                                        <i class="fas fa-eye text-info"></i>
                                                    </a>
                                                @endif
                                                @if ($canReviewLeave)
                                                    <a href="{{ route('leaves.edit', ['leaf' => $leave->getKey()]) }}" class="mx-1" data-bs-toggle="tooltip" title="Edit" data-testid="btn-edit-leave-{{ $leave->id }}">
                                                        <i class="fas fa-edit text-secondary"></i>
                                                    </a>
                                                @endif
                                                @if ($currentUser && $currentUser->isAdminHr())
                                                    <form action="{{ route('leaves.destroy', ['leaf' => $leave->getKey()]) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="border-0 bg-transparent p-0 mx-1" data-bs-toggle="tooltip" title="Hapus" data-testid="btn-delete-leave-{{ $leave->id }}" onclick="return confirm('Hapus permintaan cuti ini?')">
                                                            <i class="fas fa-trash text-danger"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="px-4 pt-3">{{ $leaves->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</div>

@if ($currentUser && ($currentUser->isAdminHr() || $currentUser->isEmployee()))
    <div class="modal fade" id="addLeaveIndexModal" tabindex="-1" aria-labelledby="addLeaveIndexModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addLeaveIndexModalLabel">Tambah Permintaan Cuti</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    @if (($createForm['employees'] ?? collect())->isEmpty())
                        <div class="border border-secondary border-radius-md p-4 text-center bg-gray-100" data-testid="leaves-modal-empty-state">
                            <i class="fas fa-calendar-times text-secondary fa-2x mb-3"></i>
                            <h6 class="mb-2">Belum ada karyawan, silakan buat karyawan terlebih dahulu</h6>
                            <p class="text-sm text-secondary mb-0">Permintaan cuti hanya dapat dibuat jika data karyawan sudah tersedia pada tenant terkait.</p>
                        </div>
                    @else
                        <form action="{{ route('leaves.store') }}" method="POST" enctype="multipart/form-data" data-testid="leaves-index-create-form">
                            @csrf
                            @include('leaves._form', $createForm)
                            <div class="d-flex justify-content-end gap-2 mt-4 flex-wrap">
                                <button type="button" class="btn btn-light mb-0" data-bs-dismiss="modal"><i class="fas fa-times me-1"></i> Batal</button>
                                <button type="submit" class="btn btn-primary mb-0"><i class="fas fa-save me-1"></i> Simpan Permintaan</button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endif

@if ($errors->has('tenant_id') || $errors->has('employee_id') || $errors->has('leave_type_id') || $errors->has('start_date') || $errors->has('end_date') || $errors->has('reason') || $errors->has('attachment'))
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var modalElement = document.getElementById('addLeaveIndexModal');

                if (modalElement && typeof bootstrap !== 'undefined') {
                    bootstrap.Modal.getOrCreateInstance(modalElement).show();
                }
            });
        </script>
    @endpush
@endif

@endsection