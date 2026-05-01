<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\Employee;
use Illuminate\Http\Request;

class BankAccountsController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:employees');
    }

    public function store(Request $request, Employee $employee)
    {
        $this->authorizeEmployeeAccess($employee);

        $data = $request->validate([
            'bank_name'      => ['required', 'string', 'max:100'],
            'account_number' => [
                'required',
                'string',
                'max:30',
                \Illuminate\Validation\Rule::unique('bank_accounts', 'account_number')
                    ->where(fn ($q) => $q->where('employee_id', $employee->id)),
            ],
            'account_holder' => ['required', 'string', 'max:150'],
        ]);

        $data['employee_id'] = $employee->id;

        BankAccount::create($data);

        return redirect()
            ->route('employees.show', $employee)
            ->with('success', 'Rekening bank berhasil ditambahkan.');
    }

    public function update(Request $request, Employee $employee, BankAccount $bankAccount)
    {
        $this->authorizeEmployeeAccess($employee);
        abort_unless($bankAccount->employee_id === $employee->id, 403);

        $data = $request->validate([
            'bank_name'      => ['required', 'string', 'max:100'],
            'account_number' => [
                'required',
                'string',
                'max:30',
                \Illuminate\Validation\Rule::unique('bank_accounts', 'account_number')
                    ->where(fn ($q) => $q->where('employee_id', $employee->id))
                    ->ignore($bankAccount->id),
            ],
            'account_holder' => ['required', 'string', 'max:150'],
        ]);

        $bankAccount->update($data);

        return redirect()
            ->route('employees.show', $employee)
            ->with('success', 'Rekening bank berhasil diperbarui.');
    }

    public function destroy(Employee $employee, BankAccount $bankAccount)
    {
        $this->authorizeEmployeeAccess($employee);
        abort_unless($bankAccount->employee_id === $employee->id, 403);

        $bankAccount->delete();

        return redirect()
            ->route('employees.show', $employee)
            ->with('success', 'Rekening bank berhasil dihapus.');
    }

    protected function authorizeEmployeeAccess(Employee $employee): void
    {
        $user = auth()->user();

        if ($user?->isManager() && $user->tenant_id !== $employee->tenant_id) {
            abort(403);
        }
    }
}
