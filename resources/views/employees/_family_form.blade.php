{{-- Partial: _family_form.blade.php
     Variables: $fm (FamilyMember|null), $familyRelationships, $educationOptions, $familyMaritalStatuses
--}}
<div class="mb-3">
    <label class="form-label">Nama <span class="text-danger">*</span></label>
    <input type="text" name="name"
        class="form-control @error('name') is-invalid @enderror"
        value="{{ old('name', $fm?->name ?? '') }}" required>
    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="mb-3">
    <label class="form-label">Hubungan Keluarga <span class="text-danger">*</span></label>
    <select name="relationship" class="form-control @error('relationship') is-invalid @enderror" required>
        <option value="">— Pilih —</option>
        @foreach ($familyRelationships as $key => $label)
            <option value="{{ $key }}" @selected(old('relationship', $fm?->relationship ?? '') === $key)>{{ $label }}</option>
        @endforeach
    </select>
    @error('relationship')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="mb-3">
    <label class="form-label">Tanggal Lahir</label>
    <input type="date" name="dob"
        class="form-control @error('dob') is-invalid @enderror"
        value="{{ old('dob', optional($fm?->dob)->format('Y-m-d') ?? '') }}">
    @error('dob')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="mb-3">
    <label class="form-label">Pendidikan Terakhir</label>
    <select name="education" class="form-control @error('education') is-invalid @enderror">
        <option value="">— Pilih —</option>
        @foreach ($educationOptions as $edu)
            <option value="{{ $edu }}" @selected(old('education', $fm?->education ?? '') === $edu)>{{ $edu }}</option>
        @endforeach
    </select>
    @error('education')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="mb-0">
    <label class="form-label">Pekerjaan</label>
    <input type="text" name="job"
        class="form-control @error('job') is-invalid @enderror"
        value="{{ old('job', $fm?->job ?? '') }}" placeholder="Contoh: Karyawan Swasta">
    @error('job')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="mb-0 mt-3">
    <label class="form-label">Status Pernikahan</label>
    <select name="marital_status" class="form-control @error('marital_status') is-invalid @enderror">
        <option value="">— Pilih —</option>
        @foreach ($familyMaritalStatuses ?? \App\Models\FamilyMember::maritalStatuses() as $msKey => $msLabel)
            <option value="{{ $msKey }}" @selected(old('marital_status', $fm?->marital_status ?? '') === $msKey)>{{ $msLabel }}</option>
        @endforeach
    </select>
    @error('marital_status')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
