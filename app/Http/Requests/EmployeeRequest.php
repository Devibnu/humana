<?php

namespace App\Http\Requests;

use App\Models\Employee;
use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmployeeRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $employee = $this->route('employee');

        $this->merge([
            'tenant_id' => $this->resolvedTenantId(),
            'role' => $this->input('role', $employee?->role ?? 'staff'),
            'status' => $this->input('status', $employee?->status ?? 'active'),
        ]);
    }

    public function authorize(): bool
    {
        $user = $this->user();

        return (bool) $user && ($user->isAdminHr() || $user->isManager());
    }

    public function rules(): array
    {
        $tenantId = $this->resolvedTenantId();
        $employee = $this->route('employee');
        $employeeId = $employee instanceof Employee ? $employee->id : null;

        return [
            'tenant_id' => ['required', 'exists:tenants,id'],
            'user_id' => [
                'nullable',
                Rule::exists('users', 'id')->where(fn ($query) => $query
                    ->where('tenant_id', $tenantId)
                    ->where('role_id', Role::idForSystemKey('employee') ?? 0)),
                Rule::unique('employees', 'user_id')->ignore($employeeId),
            ],
            'employee_code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('employees', 'employee_code')
                    ->where(fn ($query) => $query->where('tenant_id', $tenantId))
                    ->ignore($employeeId),
            ],
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('employees', 'email')
                    ->where(fn ($query) => $query->where('tenant_id', $tenantId))
                    ->ignore($employeeId),
            ],
            'phone' => ['nullable', 'string', 'max:50'],
            'ktp_number' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('employees', 'ktp_number')
                    ->where(fn ($query) => $query->where('tenant_id', $tenantId))
                    ->ignore($employeeId),
            ],
            'kk_number' => ['nullable', 'string', 'max:20'],
            'education' => ['nullable', Rule::in(['SD', 'SMP', 'SMA', 'SMK', 'D3', 'S1', 'S2', 'S3'])],
            'dob' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', Rule::in(['male', 'female'])],
            'address' => ['nullable', 'string', 'max:500'],
            'role' => ['required', Rule::in(['staff', 'supervisor', 'manager'])],
            'position_id' => [
                'nullable',
                Rule::exists('positions', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'department_id' => [
                'nullable',
                Rule::exists('departments', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'work_location_id' => [
                'nullable',
                Rule::exists('work_locations', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'start_date' => ['nullable', 'date'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'marital_status' => ['nullable', Rule::in(['belum_menikah', 'menikah', 'cerai_hidup', 'cerai_mati'])],
            'employment_type' => ['nullable', Rule::in(['tetap', 'kontrak'])],
            'contract_start_date' => ['nullable', 'date', 'required_if:employment_type,kontrak'],
            'contract_end_date' => ['nullable', 'date', 'after_or_equal:contract_start_date', 'required_if:employment_type,kontrak'],
            'family_members' => ['nullable', 'array'],
            'family_members.*.name' => ['required_with:family_members.*.relationship,family_members.*.dob,family_members.*.education,family_members.*.job,family_members.*.marital_status', 'nullable', 'string', 'max:255'],
            'family_members.*.relationship' => ['required_with:family_members.*.name,family_members.*.dob,family_members.*.education,family_members.*.job,family_members.*.marital_status', 'nullable', Rule::in(array_keys(\App\Models\FamilyMember::modalRelationships()))],
            'family_members.*.dob' => ['required_with:family_members.*.name,family_members.*.relationship,family_members.*.education,family_members.*.job,family_members.*.marital_status', 'nullable', 'date', 'before:today'],
            'family_members.*.education' => ['nullable', 'string', 'max:100'],
            'family_members.*.job' => ['nullable', 'string', 'max:100'],
            'family_members.*.marital_status' => ['required_with:family_members.*.name,family_members.*.relationship,family_members.*.dob,family_members.*.education,family_members.*.job', 'nullable', Rule::in(['menikah', 'belum_menikah'])],
            'bank_accounts' => ['nullable', 'array'],
            'bank_accounts.*.bank_name' => ['required_with:bank_accounts.*.account_number,bank_accounts.*.account_holder', 'nullable', 'string', 'max:100'],
            'bank_accounts.*.account_number' => ['required_with:bank_accounts.*.bank_name,bank_accounts.*.account_holder', 'nullable', 'string', 'max:30'],
            'bank_accounts.*.account_holder' => ['required_with:bank_accounts.*.bank_name,bank_accounts.*.account_number', 'nullable', 'string', 'max:150'],
        ];
    }

    public function messages(): array
    {
        return [
            'family_members.*.name.required_with' => 'Nama anggota keluarga wajib diisi.',
            'family_members.*.relationship.required_with' => 'Hubungan keluarga wajib dipilih.',
            'family_members.*.relationship.in' => 'Hubungan keluarga tidak valid.',
            'family_members.*.dob.required_with' => 'Tanggal lahir anggota keluarga wajib diisi.',
            'family_members.*.dob.date' => 'Tanggal lahir anggota keluarga harus berupa tanggal yang valid.',
            'family_members.*.dob.before' => 'Tanggal lahir anggota keluarga harus sebelum hari ini.',
            'family_members.*.marital_status.required_with' => 'Status pernikahan anggota keluarga wajib dipilih.',
            'family_members.*.marital_status.in' => 'Status pernikahan anggota keluarga tidak valid.',
            'bank_accounts.*.bank_name.required_with' => 'Nama bank wajib diisi.',
            'bank_accounts.*.account_number.required_with' => 'Nomor rekening wajib diisi.',
            'bank_accounts.*.account_holder.required_with' => 'Atas nama rekening wajib diisi.',
        ];
    }

    public function resolvedTenantId(): int
    {
        $user = $this->user();

        if ($user?->isManager()) {
            return (int) $user->tenant_id;
        }

        $routeEmployee = $this->route('employee');

        return (int) ($this->integer('tenant_id') ?: $routeEmployee?->tenant_id ?: $user?->tenant_id);
    }
}