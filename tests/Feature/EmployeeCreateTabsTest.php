<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeCreateTabsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesTableSeeder::class);
    }

    public function test_create_page_focuses_on_core_employee_data_and_shows_follow_up_notice(): void
    {
        $user = User::factory()->create([
            'role_id' => Role::where('name', 'Admin HR')->firstOrFail()->id,
        ]);

        $response = $this->actingAs($user)->get('/employees/create');

        $response->assertOk();
        $response->assertSee('Tambah Karyawan Baru');
        $response->assertSee('Data keluarga dan rekening dapat dikelola setelah karyawan tersimpan.');
        $response->assertDontSee('data-testid="create-tab-employee"', false);
        $response->assertDontSee('data-testid="create-tab-family"', false);
    }

    public function test_create_page_hides_inline_family_inputs(): void
    {
        $user = User::factory()->create([
            'role_id' => Role::where('name', 'Admin HR')->firstOrFail()->id,
        ]);

        $response = $this->actingAs($user)->get('/employees/create');

        $response->assertOk();
        $response->assertDontSee('+ Tambah Anggota', false);
        $response->assertDontSee('name="family_members[0][name]"', false);
    }

    public function test_create_page_hides_inline_finance_inputs(): void
    {
        $user = User::factory()->create([
            'role_id' => Role::where('name', 'Admin HR')->firstOrFail()->id,
        ]);

        $response = $this->actingAs($user)->get('/employees/create');

        $response->assertOk();
        $response->assertDontSee('data-testid="create-tab-finance"', false);
        $response->assertDontSee('+ Tambah Rekening', false);
        $response->assertDontSee('name="bank_accounts[0][bank_name]"', false);
    }
}