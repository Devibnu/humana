@extends('layouts.user_type.auth')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card mx-4 mb-4 shadow-xs">
            <div class="card-header pb-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-1">Tambah Pengajuan Lembur</h5>
                    <p class="text-sm text-secondary mb-0">Ajukan lembur dengan waktu kerja yang jelas, alasan yang lengkap, dan validasi agar tidak terjadi duplikasi di hari yang sama.</p>
                </div>
                <a href="{{ route('lembur.index') }}" class="btn btn-light btn-sm mb-0"><i class="fas fa-times me-1"></i> Batal</a>
            </div>
            <div class="card-body">
                <x-flash-messages />

                @if($settings)
                    <div class="row g-3 mb-4">
                        <div class="col-xl-4 col-md-6">
                            <div class="card border shadow-xs h-100">
                                <div class="card-body py-3">
                                    <p class="text-xs text-uppercase text-secondary font-weight-bold mb-1">Pengaju Default</p>
                                    <h6 class="mb-0">{{ ucfirst($settings->role_pengaju) }}</h6>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-4 col-md-6">
                            <div class="card border shadow-xs h-100">
                                <div class="card-body py-3">
                                    <p class="text-xs text-uppercase text-secondary font-weight-bold mb-1">Butuh Persetujuan</p>
                                    <h6 class="mb-0 {{ $settings->butuh_persetujuan ? 'text-success' : 'text-info' }}">{{ $settings->butuh_persetujuan ? 'Ya, melalui approval' : 'Tidak, auto approve' }}</h6>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-4 col-md-12">
                            <div class="card border shadow-xs h-100">
                                <div class="card-body py-3">
                                    <p class="text-xs text-uppercase text-secondary font-weight-bold mb-1">Mode Pengajuan</p>
                                    <h6 class="mb-0 {{ $submissionRole === 'atasan' ? 'text-info' : 'text-primary' }}">{{ $submissionRole === 'atasan' ? 'Diajukan Atasan' : 'Diajukan Karyawan' }}</h6>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="border border-radius-xl p-3 mb-4 bg-gray-100">
                    <p class="text-sm font-weight-bold mb-1">Catatan validasi</p>
                    <p class="text-sm text-secondary mb-0">Satu karyawan tidak bisa memiliki lebih dari satu pengajuan lembur pada tanggal mulai yang sama. Gunakan satu pengajuan per hari agar approval dan payroll tetap konsisten.</p>
                </div>

                @if ($submissionAccessIssue)
                    <div class="border border-danger border-radius-xl p-4 text-center bg-gray-100">
                        <i class="fas fa-exclamation-triangle text-danger fa-2x mb-3"></i>
                        <h6 class="mb-2">Pengajuan belum bisa dibuat</h6>
                        <p class="text-sm text-secondary mb-0">{{ $submissionAccessIssue }}</p>
                    </div>
                @else
                    <form method="POST" action="{{ route('lembur.store') }}" data-testid="lembur-create-form">
                        @csrf

                        <div class="row g-4 align-items-start">
                            <div class="col-lg-8 col-12">
                                <div class="border border-radius-xl p-4 bg-white shadow-xs">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label d-flex align-items-center gap-1" for="employee_id">
                                                Karyawan
                                                <i class="fas fa-info-circle text-secondary text-xs" data-bs-toggle="tooltip" title="Pilih karyawan yang akan diajukan lemburnya"></i>
                                            </label>
                                            <select class="form-control @error('employee_id') is-invalid @enderror" id="employee_id" name="employee_id" required data-testid="lembur-employee-select">
                                                <option value="">Pilih karyawan</option>
                                                @foreach($employees as $employee)
                                                    <option value="{{ $employee->id }}" @selected((string) old('employee_id', $selectedEmployeeId) === (string) $employee->id)>
                                                        {{ $employee->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('employee_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                            <small class="text-muted">{{ $submissionRole === 'atasan' ? 'Anda dapat memilih karyawan tenant untuk diajukan oleh atasan.' : 'Akun karyawan hanya bisa mengajukan lembur untuk dirinya sendiri.' }}</small>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label">Status Approval</label>
                                            <div class="border border-radius-lg p-3 bg-gray-100 h-100">
                                                <div class="d-flex gap-2 flex-wrap mb-2">
                                                    <span class="badge bg-gradient-primary">Tanggal Lembur</span>
                                                    <span class="badge bg-gradient-info">Durasi</span>
                                                    <span class="badge bg-gradient-success">Approval</span>
                                                </div>
                                                <p class="text-sm text-secondary mb-1">{{ $settings->butuh_persetujuan ? 'Pengajuan akan masuk antrean approval setelah disimpan.' : 'Pengajuan akan langsung disetujui oleh sistem setelah disimpan.' }}</p>
                                                <p class="text-xs text-secondary mb-0">Pastikan waktu mulai dan selesai berada pada hari pengajuan yang benar dan tidak bentrok dengan pengajuan lembur lain untuk karyawan yang sama.</p>
                                            </div>
                                        </div>

                                        <div class="col-md-6 mt-1">
                                            <label class="form-label" for="waktu_mulai">Waktu Mulai</label>
                                            <input type="datetime-local" class="form-control @error('waktu_mulai') is-invalid @enderror" id="waktu_mulai" name="waktu_mulai" value="{{ old('waktu_mulai') }}" required data-testid="lembur-start-input">
                                            @error('waktu_mulai')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>

                                        <div class="col-md-6 mt-1">
                                            <label class="form-label" for="waktu_selesai">Waktu Selesai</label>
                                            <input type="datetime-local" class="form-control @error('waktu_selesai') is-invalid @enderror" id="waktu_selesai" name="waktu_selesai" value="{{ old('waktu_selesai') }}" required data-testid="lembur-end-input">
                                            @error('waktu_selesai')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>

                                        <div class="col-12 mt-1">
                                            <label class="form-label" for="alasan">Alasan</label>
                                            <textarea class="form-control @error('alasan') is-invalid @enderror" id="alasan" name="alasan" rows="5" placeholder="Tuliskan alasan atau konteks pekerjaan lembur">{{ old('alasan') }}</textarea>
                                            @error('alasan')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-4 col-12">
                                <div class="border border-radius-xl p-4 bg-gray-100 h-100">
                                    <p class="text-sm font-weight-bold mb-3">Preview Pengajuan</p>

                                    <div class="mb-3">
                                        <p class="text-xs text-uppercase text-secondary font-weight-bold mb-1">Estimasi Durasi</p>
                                        <h5 class="mb-0" data-testid="lembur-duration-preview">-</h5>
                                    </div>

                                    <div class="mb-3">
                                        <p class="text-xs text-uppercase text-secondary font-weight-bold mb-1">Aturan Hari Sama</p>
                                        <p class="text-sm text-secondary mb-0">Sistem akan menolak pengajuan baru bila karyawan yang sama sudah punya lembur pada tanggal mulai yang sama.</p>
                                    </div>

                                    <div>
                                        <p class="text-xs text-uppercase text-secondary font-weight-bold mb-1">Checklist Sebelum Simpan</p>
                                        <ul class="text-sm text-secondary mb-0 ps-3">
                                            <li>Pilih karyawan yang benar</li>
                                            <li>Pastikan jam selesai lebih besar dari jam mulai</li>
                                            <li>Tulis alasan lembur secara singkat dan jelas</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4 flex-wrap">
                            <a href="{{ route('lembur.index') }}" class="btn btn-light mb-0"><i class="fas fa-times me-1"></i> Batal</a>
                            <button type="submit" class="btn bg-gradient-primary mb-0" data-testid="btn-submit-lembur"><i class="fas fa-save me-1"></i> Simpan Pengajuan</button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var startInput = document.getElementById('waktu_mulai');
            var endInput = document.getElementById('waktu_selesai');
            var durationPreview = document.querySelector('[data-testid="lembur-duration-preview"]');

            if (!startInput || !endInput || !durationPreview) {
                return;
            }

            var updateDurationPreview = function () {
                if (!startInput.value || !endInput.value) {
                    durationPreview.textContent = '-';
                    return;
                }

                var start = new Date(startInput.value);
                var end = new Date(endInput.value);

                if (Number.isNaN(start.getTime()) || Number.isNaN(end.getTime()) || end <= start) {
                    durationPreview.textContent = 'Input waktu belum valid';
                    return;
                }

                var durationHours = (end.getTime() - start.getTime()) / 3600000;
                durationPreview.textContent = durationHours.toFixed(2).replace(/\.00$/, '') + ' jam';
            };

            updateDurationPreview();
            startInput.addEventListener('change', updateDurationPreview);
            endInput.addEventListener('change', updateDurationPreview);
            startInput.addEventListener('input', updateDurationPreview);
            endInput.addEventListener('input', updateDurationPreview);
        });
    </script>
@endpush
@endsection
