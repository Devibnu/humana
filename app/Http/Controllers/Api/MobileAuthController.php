<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class MobileAuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $user = User::query()
            ->with(['tenant', 'assignedEmployee', 'employee'])
            ->where('email', $credentials['email'])
            ->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => 'Email atau password tidak sesuai.',
            ]);
        }

        if ($user->status !== 'active') {
            throw ValidationException::withMessages([
                'email' => 'Akun Anda tidak aktif.',
            ]);
        }

        if (! $user->isEmployee()) {
            throw ValidationException::withMessages([
                'email' => 'Aplikasi mobile hanya untuk karyawan.',
            ]);
        }

        $employee = $user->assignedEmployee ?: $user->employee;

        if (! $employee) {
            throw ValidationException::withMessages([
                'email' => 'Akun Anda belum terhubung ke data karyawan.',
            ]);
        }

        $token = $user->createToken($credentials['device_name'] ?? 'humana-mobile')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $this->serializeUser($user),
        ]);
    }

    public function me(Request $request)
    {
        return response()->json([
            'user' => $this->serializeUser($request->user()->loadMissing(['tenant', 'assignedEmployee', 'employee'])),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Logout berhasil.',
        ]);
    }

    protected function serializeUser(User $user): array
    {
        $employee = $user->assignedEmployee ?: $user->employee;

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'tenant' => [
                'id' => $user->tenant?->id,
                'name' => $user->tenant?->name,
            ],
            'employee' => $employee ? [
                'id' => $employee->id,
                'name' => $employee->name,
                'employee_code' => $employee->employee_code,
            ] : null,
        ];
    }
}
