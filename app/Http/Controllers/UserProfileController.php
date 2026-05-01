<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Leave;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UserProfileController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        return view('profile.index', $this->buildProfileViewData(Auth::user()));
    }

    public function edit()
    {
        return view('user.profile.edit', $this->buildProfileViewData(Auth::user()));
    }

    public function show(User $user)
    {
        $viewer = Auth::user();

        abort_unless($viewer && $viewer->isAdminHr(), 403);

        return view('user.profile.show', $this->buildUserDetailViewData($user));
    }

    public function destroy(User $user)
    {
        $viewer = Auth::user();

        abort_unless($viewer && $viewer->isAdminHr(), 403);

        try {
            $user->delete();

            return redirect()
                ->route('users.index')
                ->with('success', 'User berhasil dihapus.');
        } catch (\Throwable $exception) {
            return redirect()
                ->route('users.show-profile', $user)
                ->with('error', 'User gagal dihapus, silakan coba lagi.');
        }
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['nullable', 'string', 'min:8'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_avatar' => ['nullable', 'boolean'],
        ]);

        $removeAvatar = (bool) ($data['remove_avatar'] ?? false);

        if ($removeAvatar && $user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
            $data['avatar_path'] = null;
        }

        if ($request->hasFile('avatar')) {
            if ($user->avatar_path) {
                Storage::disk('public')->delete($user->avatar_path);
            }

            $data['avatar_path'] = $request->file('avatar')->store('avatars', 'public');
        }

        if (empty($data['password'])) {
            unset($data['password']);
        }

        unset($data['avatar']);
        unset($data['remove_avatar']);
        unset($data['password_confirmation']);

        $user->update($data);

        return redirect()
            ->route('user-profile.edit')
            ->with('success', 'Profile updated successfully.');
    }

    protected function buildProfileViewData($authUser): array
    {
        $user = $authUser->load([
            'employee.department',
            'employee.position',
            'employee.workLocation',
        ]);

        $employee = $user->employee;

        return [
            'user' => $user,
            'employee' => $employee,
            'avatarInitials' => $this->makeAvatarInitials($user->name),
            'roleBadgeClass' => $this->resolveRoleBadgeClass($user->roleKey()),
            'avatarUrl' => $user->avatar_path ? Storage::url($user->avatar_path) : null,
            'weeklyAttendanceSummary' => $this->buildWeeklyAttendanceSummary($user, $employee),
        ];
    }

    protected function buildUserDetailViewData(User $user): array
    {
        $user->load([
            'tenant',
            'employee.department',
            'employee.position',
            'employee.workLocation',
        ]);

        return [
            'user' => $user,
            'employee' => $user->employee,
            'avatarInitials' => $this->makeAvatarInitials($user->name),
            'roleBadgeClass' => $this->resolveRoleBadgeClass($user->roleKey()),
            'avatarUrl' => $user->avatar_path ? Storage::url($user->avatar_path) : null,
        ];
    }

    protected function buildWeeklyAttendanceSummary($user, $employee): ?array
    {
        if (! ($user->isEmployee() || $user->isManager()) || ! $employee) {
            return null;
        }

        $weekStart = now()->startOfWeek();
        $weekEnd = now()->endOfWeek();

        return [
            'label' => $weekStart->format('d M').' - '.$weekEnd->format('d M Y'),
            'present' => Attendance::query()
                ->where('employee_id', $employee->id)
                ->whereBetween('date', [$weekStart->toDateString(), $weekEnd->toDateString()])
                ->where('status', 'present')
                ->count(),
            'absent' => Attendance::query()
                ->where('employee_id', $employee->id)
                ->whereBetween('date', [$weekStart->toDateString(), $weekEnd->toDateString()])
                ->where('status', 'absent')
                ->count(),
            'leave' => Leave::query()
                ->where('employee_id', $employee->id)
                ->whereIn('status', ['pending', 'approved'])
                ->whereDate('start_date', '<=', $weekEnd->toDateString())
                ->whereDate('end_date', '>=', $weekStart->toDateString())
                ->count(),
        ];
    }

    protected function makeAvatarInitials(string $name): string
    {
        return Str::of($name)
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn ($segment) => Str::upper(Str::substr($segment, 0, 1)))
            ->implode('');
    }

    protected function resolveRoleBadgeClass(?string $role): string
    {
        return match ($role) {
            'admin_hr' => 'bg-gradient-danger',
            'manager' => 'bg-gradient-warning',
            'employee' => 'bg-gradient-info',
            default => 'bg-gradient-secondary',
        };
    }
}