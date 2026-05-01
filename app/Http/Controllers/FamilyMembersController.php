<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\FamilyMember;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FamilyMembersController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:employees');
    }

    public function store(Request $request, Employee $employee)
    {
        $this->authorizeEmployeeAccess($employee);

        $data = $request->validate([
            'name'           => ['required', 'string', 'max:150'],
            'relationship'   => ['required', Rule::in(array_keys(FamilyMember::modalRelationships()))],
            'dob'            => ['required', 'date', 'before:tomorrow'],
            'education'      => ['nullable', 'string', 'max:100'],
            'job'            => ['nullable', 'string', 'max:100'],
            'marital_status' => ['required', Rule::in(array_keys(FamilyMember::maritalStatuses()))],
        ]);

        $data['employee_id'] = $employee->id;

        FamilyMember::create($data);

        return redirect()
            ->route('employees.show', $employee)
            ->with('success', 'Anggota keluarga berhasil ditambahkan.');
    }

    public function update(Request $request, Employee $employee, FamilyMember $familyMember)
    {
        $this->authorizeEmployeeAccess($employee);
        abort_unless($familyMember->employee_id === $employee->id, 403);

        $data = $request->validate([
            'name'           => ['required', 'string', 'max:150'],
            'relationship'   => ['required', Rule::in(array_keys(FamilyMember::relationships()))],
            'dob'            => ['required', 'date', 'before:tomorrow'],
            'education'      => ['nullable', 'string', 'max:100'],
            'job'            => ['nullable', 'string', 'max:100'],
            'marital_status' => ['required', Rule::in(array_keys(FamilyMember::maritalStatuses()))],
        ]);

        $familyMember->update($data);

        return redirect()
            ->route('employees.show', $employee)
            ->with('success', 'Data anggota keluarga berhasil diperbarui.');
    }

    public function destroy(Employee $employee, FamilyMember $familyMember)
    {
        $this->authorizeEmployeeAccess($employee);
        abort_unless($familyMember->employee_id === $employee->id, 403);

        $familyMember->delete();

        return redirect()
            ->route('employees.show', $employee)
            ->with('success', 'Anggota keluarga berhasil dihapus.');
    }

    protected function authorizeEmployeeAccess(Employee $employee): void
    {
        $user = auth()->user();

        if ($user?->isManager() && $user->tenant_id !== $employee->tenant_id) {
            abort(403);
        }
    }
}
