<?php

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\Employee;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BankAccountsTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $admin;
    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name'   => 'Bank Test Tenant',
            'slug'   => 'bank-test',
            'domain' => 'bank-test.test',
            'status' => 'active',
        ]);

        $this->admin = User::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Bank Admin',
            'email'     => 'bank-admin@example.test',
            'password'  => 'password',
            'role'      => 'admin_hr',
            'status'    => 'active',
        ]);

        $this->employee = Employee::create([
            'tenant_id'     => $this->tenant->id,
            'employee_code' => 'EMP-BANK-001',
            'name'          => 'Test Employee Bank',
            'email'         => 'test-emp-bank@example.test',
            'status'        => 'active',
        ]);
    }

    public function test_employee_show_page_renders_with_bank_tab(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('employees.show', $this->employee));

        $response->assertOk();
        $response->assertSee('Informasi Keuangan');
        $response->assertSee('data-testid="tab-bank"', false);
        $response->assertSee('data-testid="bank-empty-state"', false);
    }

    public function test_admin_can_add_bank_account(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('employees.bank-accounts.store', $this->employee), [
                'bank_name'      => 'BCA',
                'account_number' => '1234567890',
                'account_holder' => 'Test Employee Bank',
            ]);

        $response->assertRedirect(route('employees.show', $this->employee));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('bank_accounts', [
            'employee_id'    => $this->employee->id,
            'bank_name'      => 'BCA',
            'account_number' => '1234567890',
            'account_holder' => 'Test Employee Bank',
        ]);
    }

    public function test_bank_account_appears_in_employee_show(): void
    {
        BankAccount::create([
            'employee_id'    => $this->employee->id,
            'bank_name'      => 'Mandiri',
            'account_number' => '9876543210',
            'account_holder' => 'Test Employee Bank',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('employees.show', $this->employee));

        $response->assertOk();
        $response->assertSee('Mandiri');
        $response->assertSee('9876543210');
        $response->assertSee('data-testid="bank-table"', false);
    }

    public function test_admin_can_update_bank_account(): void
    {
        $account = BankAccount::create([
            'employee_id'    => $this->employee->id,
            'bank_name'      => 'BNI',
            'account_number' => '1111111111',
            'account_holder' => 'Old Name',
        ]);

        $response = $this->actingAs($this->admin)
            ->put(route('employees.bank-accounts.update', [$this->employee, $account]), [
                'bank_name'      => 'BNI',
                'account_number' => '1111111111',
                'account_holder' => 'New Name Updated',
            ]);

        $response->assertRedirect(route('employees.show', $this->employee));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('bank_accounts', [
            'id'             => $account->id,
            'account_holder' => 'New Name Updated',
        ]);
    }

    public function test_admin_can_delete_bank_account(): void
    {
        $account = BankAccount::create([
            'employee_id'    => $this->employee->id,
            'bank_name'      => 'BSI',
            'account_number' => '2222222222',
            'account_holder' => 'To Delete',
        ]);

        $response = $this->actingAs($this->admin)
            ->delete(route('employees.bank-accounts.destroy', [$this->employee, $account]));

        $response->assertRedirect(route('employees.show', $this->employee));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('bank_accounts', ['id' => $account->id]);
    }

    public function test_store_validates_required_fields(): void
    {
        $this->actingAs($this->admin)
            ->post(route('employees.bank-accounts.store', $this->employee), [])
            ->assertSessionHasErrors(['bank_name', 'account_number', 'account_holder']);
    }

    public function test_store_rejects_duplicate_account_number_for_same_employee(): void
    {
        BankAccount::create([
            'employee_id'    => $this->employee->id,
            'bank_name'      => 'BCA',
            'account_number' => '3333333333',
            'account_holder' => 'First',
        ]);

        $this->actingAs($this->admin)
            ->post(route('employees.bank-accounts.store', $this->employee), [
                'bank_name'      => 'BCA',
                'account_number' => '3333333333',
                'account_holder' => 'Duplicate',
            ])
            ->assertSessionHasErrors('account_number');
    }

    public function test_employee_role_cannot_add_bank_account(): void
    {
        $employeeUser = User::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Emp User Bank',
            'email'     => 'emp-user-bank@example.test',
            'password'  => 'password',
            'role'      => 'employee',
            'status'    => 'active',
        ]);

        $this->actingAs($employeeUser)
            ->post(route('employees.bank-accounts.store', $this->employee), [
                'bank_name'      => 'Should Fail',
                'account_number' => '9999999999',
                'account_holder' => 'Fail',
            ])
            ->assertForbidden();
    }

    public function test_cannot_delete_bank_account_belonging_to_another_employee(): void
    {
        $otherEmployee = Employee::create([
            'tenant_id'     => $this->tenant->id,
            'employee_code' => 'EMP-OTHER-BANK',
            'name'          => 'Other Employee Bank',
            'email'         => 'other-emp-bank@example.test',
            'status'        => 'active',
        ]);

        $account = BankAccount::create([
            'employee_id'    => $otherEmployee->id,
            'bank_name'      => 'Danamon',
            'account_number' => '5555555555',
            'account_holder' => 'Not Mine',
        ]);

        $this->actingAs($this->admin)
            ->delete(route('employees.bank-accounts.destroy', [$this->employee, $account]))
            ->assertForbidden();
    }
}
