<div class="modal fade" id="addFamilyMemberModal" tabindex="-1" aria-labelledby="addFamilyMemberLabel" aria-hidden="true" data-testid="family-member-modal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-gray-100 border-bottom-0">
                <div>
                    <h5 class="modal-title mb-1" id="addFamilyMemberLabel">Tambah Anggota Keluarga</h5>
                    <p class="text-xs text-secondary mb-0">Lengkapi data keluarga inti untuk kebutuhan administrasi karyawan.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <form action="{{ route('family-members.store', $employee) }}" method="POST" data-testid="form-add-family">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="family_member_name" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="family_member_name" name="name" value="{{ old('name') }}" required>
                            @error('name')
                                <small class="text-danger d-block mt-1">{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="family_member_relationship" class="form-label">Hubungan <span class="text-danger">*</span></label>
                            <select class="form-select @error('relationship') is-invalid @enderror" id="family_member_relationship" name="relationship" required>
                                <option value="">-- Pilih Hubungan --</option>
                                @foreach ($familyModalRelationships as $value => $label)
                                    <option value="{{ $value }}" @selected(old('relationship') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('relationship')
                                <small class="text-danger d-block mt-1">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="family_member_dob" class="form-label">Tanggal Lahir <span class="text-danger">*</span></label>
                            <input type="date" class="form-control @error('dob') is-invalid @enderror" id="family_member_dob" name="dob" value="{{ old('dob') }}" required>
                            @error('dob')
                                <small class="text-danger d-block mt-1">{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="family_member_education" class="form-label">Pendidikan Terakhir</label>
                            <input type="text" class="form-control @error('education') is-invalid @enderror" id="family_member_education" name="education" value="{{ old('education') }}" placeholder="Contoh: SMA atau S1">
                            @error('education')
                                <small class="text-danger d-block mt-1">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="family_member_job" class="form-label">Pekerjaan</label>
                            <input type="text" class="form-control @error('job') is-invalid @enderror" id="family_member_job" name="job" value="{{ old('job') }}" placeholder="Contoh: Karyawan Swasta">
                            @error('job')
                                <small class="text-danger d-block mt-1">{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="family_member_marital_status" class="form-label">Status Pernikahan <span class="text-danger">*</span></label>
                            <select class="form-select @error('marital_status') is-invalid @enderror" id="family_member_marital_status" name="marital_status" required>
                                <option value="">-- Pilih Status --</option>
                                @foreach ($familyMaritalStatuses as $value => $label)
                                    <option value="{{ $value }}" @selected(old('marital_status') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('marital_status')
                                <small class="text-danger d-block mt-1">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="fa fa-save me-1"></i> Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

@if ($errors->has('name') || $errors->has('relationship') || $errors->has('dob') || $errors->has('education') || $errors->has('job') || $errors->has('marital_status'))
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var modalElement = document.getElementById('addFamilyMemberModal');
            if (modalElement && typeof bootstrap !== 'undefined') {
                bootstrap.Modal.getOrCreateInstance(modalElement).show();
            }
        });
    </script>
@endif