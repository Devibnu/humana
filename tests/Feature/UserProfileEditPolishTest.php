<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WorkLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserProfileEditPolishTest extends TestCase
{
    use RefreshDatabase;

    public function test_avatar_can_be_removed(): void
    {
        Storage::fake('public');

        [$user] = $this->createProfileContext();

        Storage::disk('public')->put('avatars/existing-avatar.png', 'avatar-content');

        $user->update([
            'avatar_path' => 'avatars/existing-avatar.png',
        ]);

        $response = $this->actingAs($user)->put('/user-profile', [
            'name' => $user->name,
            'email' => $user->email,
            'remove_avatar' => '1',
        ]);

        $response->assertRedirect('/user-profile/edit');

        $user->refresh();

        $this->assertNull($user->avatar_path);
        Storage::disk('public')->assertMissing('avatars/existing-avatar.png');
    }

    public function test_password_update_fails_when_confirmation_does_not_match(): void
    {
        [$user] = $this->createProfileContext();

        $originalPasswordHash = $user->password;

        $response = $this->from('/user-profile/edit')->actingAs($user)->put('/user-profile', [
            'name' => $user->name,
            'email' => $user->email,
            'password' => 'updated-password-123',
            'password_confirmation' => 'different-password-123',
        ]);

        $response->assertRedirect('/user-profile/edit');
        $response->assertSessionHasErrors('password');

        $user->refresh();

        $this->assertSame($originalPasswordHash, $user->password);
        $this->assertTrue(Hash::check('password123', $user->password));
    }

    public function test_password_update_succeeds_when_confirmation_matches(): void
    {
        [$user] = $this->createProfileContext();

        $response = $this->actingAs($user)->put('/user-profile', [
            'name' => $user->name,
            'email' => $user->email,
            'password' => 'updated-password-123',
            'password_confirmation' => 'updated-password-123',
        ]);

        $response->assertRedirect('/user-profile/edit');

        $user->refresh();

        $this->assertTrue(Hash::check('updated-password-123', $user->password));
    }

    protected function createProfileContext(): array
    {
        $tenant = Tenant::create([
            'name' => 'Edit Profile Polish Tenant',
            'slug' => 'edit-profile-polish-tenant',
            'domain' => 'edit-profile-polish-tenant.test',
            'status' => 'active',
        ]);

        $department = Department::create([
            'tenant_id' => $tenant->id,
            'name' => 'People Operations',
            'status' => 'active',
        ]);

        $position = Position::create([
            'tenant_id' => $tenant->id,
            'name' => 'HR Specialist',
            'status' => 'active',
        ]);

        $workLocation = WorkLocation::create([
            'tenant_id' => $tenant->id,
            'name' => 'Jakarta HQ',
            'address' => 'Jakarta',
            'latitude' => -6.2,
            'longitude' => 106.8,
            'radius' => 100,
        ]);

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Profile Polish User',
            'email' => 'profile-polish@example.test',
            'password' => 'password123',
            'role' => 'employee',
            'status' => 'active',
        ]);

        $employee = Employee::create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'employee_code' => 'EMP-POLISH-001',
            'name' => 'Profile Polish Employee',
            'email' => 'profile-polish-employee@example.test',
            'position_id' => $position->id,
            'department_id' => $department->id,
            'work_location_id' => $workLocation->id,
            'status' => 'active',
        ]);

        return [$user, $employee];
    }
}