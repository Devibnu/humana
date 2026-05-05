<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Lembur;
use App\Models\LemburSetting;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class MobileOvertimeController extends Controller
{
    public function __construct()
    {
        // Mobile API uses Sanctum auth instead of web middleware.
    }

    public function index(Request $request): JsonResponse
    {
        $employee = $this->mobileEmployee($request);
        $history = Lembur::query()
            ->where('tenant_id', $employee->tenant_id)
            ->where('employee_id', $employee->id)
            ->orderByDesc('waktu_mulai')
            ->latest('id')
            ->limit(20)
            ->get();

        return response()->json([
            'employee' => $this->serializeEmployee($employee),
            'settings' => [
                'submission_role' => $this->resolveSubmissionRole($this->resolveSettings($request->user())),
                'approval_required' => (bool) $this->resolveSettings($request->user())->butuh_persetujuan,
            ],
            'summary' => [
                'total' => $history->count(),
                'pending' => $history->where('status', 'pending')->count(),
                'approved' => $history->where('status', 'disetujui')->count(),
                'rejected' => $history->where('status', 'ditolak')->count(),
                'total_hours' => round((float) $history->sum(fn (Lembur $item) => (float) ($item->durasi_jam ?? 0)), 2),
            ],
            'data' => $history->map(fn (Lembur $lembur) => $this->serializeLembur($lembur))->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $currentUser = $request->user();
        $employee = $this->mobileEmployee($request);
        $settings = $this->resolveSettings($currentUser);
        $submissionRole = $this->resolveSubmissionRole($settings);
        $submissionEmployees = $this->submissionEmployees($currentUser, $submissionRole);
        $submissionAccessIssue = $this->submissionAccessIssue($currentUser, $submissionRole, $submissionEmployees);

        if ($submissionAccessIssue !== null) {
            throw ValidationException::withMessages([
                'employee_id' => $submissionAccessIssue,
            ]);
        }

        $validated = $request->validate([
            'waktu_mulai' => ['required', 'date'],
            'waktu_selesai' => ['required', 'date', 'after:waktu_mulai'],
            'alasan' => ['nullable', 'string'],
        ]);

        $start = Carbon::parse($validated['waktu_mulai']);
        $end = Carbon::parse($validated['waktu_selesai']);

        $duplicateExists = Lembur::query()
            ->where('tenant_id', $employee->tenant_id)
            ->where('employee_id', $employee->id)
            ->whereDate('waktu_mulai', $start->toDateString())
            ->exists();

        if ($duplicateExists) {
            throw ValidationException::withMessages([
                'waktu_mulai' => 'Pengajuan lembur pada tanggal yang sama sudah ada.',
            ]);
        }

        $payload = [
            'tenant_id' => $employee->tenant_id,
            'employee_id' => $employee->id,
            'submitted_by' => $currentUser?->id,
            'waktu_mulai' => $start,
            'waktu_selesai' => $end,
            'durasi_jam' => round($start->floatDiffInHours($end), 2),
            'alasan' => $validated['alasan'] ?? null,
            'pengaju' => $submissionRole,
            'status' => $settings->butuh_persetujuan ? 'pending' : 'disetujui',
        ];

        if ($payload['status'] === 'disetujui') {
            $payload['approver_id'] = $currentUser?->id;
        }

        $lembur = Lembur::create($payload);

        return response()->json([
            'message' => 'Pengajuan lembur berhasil dikirim.',
            'data' => $this->serializeLembur($lembur),
        ], 201);
    }

    protected function mobileEmployee(Request $request): Employee
    {
        $user = $request->user()?->loadMissing(['assignedEmployee', 'employee']);

        abort_unless($user?->isEmployee(), 403);
        abort_unless($user->hasMenuAccess('lembur.submit'), 403);

        $employee = $user->assignedEmployee ?: $user->employee;

        if (! $employee) {
            throw ValidationException::withMessages([
                'employee_id' => 'Akun Anda belum terhubung ke data karyawan.',
            ]);
        }

        return $employee;
    }

    protected function serializeEmployee(Employee $employee): array
    {
        return [
            'id' => $employee->id,
            'name' => $employee->name,
            'employee_code' => $employee->employee_code,
        ];
    }

    protected function serializeLembur(Lembur $lembur): array
    {
        return [
            'id' => $lembur->id,
            'waktu_mulai' => $lembur->waktu_mulai?->toIso8601String(),
            'waktu_selesai' => $lembur->waktu_selesai?->toIso8601String(),
            'tanggal' => $lembur->waktu_mulai?->toDateString(),
            'durasi_jam' => (float) ($lembur->durasi_jam ?? 0),
            'status' => $lembur->status,
            'status_label' => $this->statusLabel($lembur->status),
            'alasan' => $lembur->alasan,
        ];
    }

    protected function statusLabel(?string $status): string
    {
        return match ($status) {
            'disetujui' => 'Disetujui',
            'ditolak' => 'Ditolak',
            default => 'Pending',
        };
    }

    protected function resolveSettings($currentUser): LemburSetting
    {
        return LemburSetting::query()
            ->where('tenant_id', $currentUser?->tenant_id)
            ->first()
            ?? new LemburSetting([
                'role_pengaju' => 'karyawan',
                'butuh_persetujuan' => true,
                'tipe_tarif' => 'per_jam',
            ]);
    }

    protected function resolveSubmissionRole(LemburSetting $settings): string
    {
        return $settings->role_pengaju === 'atasan' ? 'atasan' : 'karyawan';
    }

    protected function submissionEmployees($currentUser, string $submissionRole): Collection
    {
        $query = Employee::query()
            ->when($currentUser?->tenant_id, fn ($builder) => $builder->where('tenant_id', $currentUser->tenant_id));

        $linkedEmployeeId = $this->linkedEmployeeId($currentUser);

        if ($submissionRole === 'karyawan') {
            if ($linkedEmployeeId === null) {
                return collect();
            }

            $query->whereKey($linkedEmployeeId);
        }

        return $query->orderBy('name')->get(['id', 'name']);
    }

    protected function submissionAccessIssue($currentUser, string $submissionRole, Collection $employees): ?string
    {
        if ($submissionRole === 'atasan' && ! ($currentUser?->isManager() || $currentUser?->isSupervisor())) {
            return 'Tenant ini mengharuskan pengajuan lembur dibuat oleh atasan atau supervisor.';
        }

        if ($submissionRole === 'karyawan' && ! $currentUser?->isEmployee()) {
            return 'Tenant ini mengharuskan pengajuan lembur dibuat langsung oleh karyawan terkait.';
        }

        if ($submissionRole === 'karyawan' && $employees->isEmpty()) {
            return 'Akun Anda belum terhubung ke data karyawan, sehingga belum bisa mengajukan lembur.';
        }

        return null;
    }

    protected function linkedEmployeeId($currentUser): ?int
    {
        $employeeId = $currentUser?->employee_id
            ?? $currentUser?->assignedEmployee?->id
            ?? $currentUser?->employee?->id;

        return $employeeId ? (int) $employeeId : null;
    }
}