<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\LeaveController;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MobileLeaveController extends LeaveController
{
    public function __construct()
    {
        // Mobile API uses Sanctum auth instead of web middleware.
    }

    public function index(Request $request): JsonResponse
    {
        $user = $this->mobileUser($request);
        $employee = $this->resolveAuthenticatedEmployee($user);
        $history = Leave::query()
            ->with('leaveType')
            ->where('tenant_id', $employee->tenant_id)
            ->where('employee_id', $employee->id)
            ->orderByDesc('start_date')
            ->latest('id')
            ->limit(20)
            ->get();

        return response()->json([
            'employee' => $this->serializeEmployee($employee),
            'leave_types' => $this->serializeLeaveTypes($employee->tenant_id),
            'summary' => $this->buildMobileSummary($history),
            'data' => $history->map(fn (Leave $leave) => $this->serializeLeave($leave))->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $currentUser = $this->mobileUser($request);
        $employee = $this->resolveAuthenticatedEmployee($currentUser);
        $tenantId = (int) $employee->tenant_id;

        $data = $request->validate([
            'leave_type_id' => [
                'required',
                Rule::exists('leave_types', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['required', 'string'],
            'attachment' => ['nullable', 'file', 'max:2048', 'mimes:pdf,jpg,jpeg,png'],
        ]);

        $leaveType = LeaveType::query()
            ->where('tenant_id', $tenantId)
            ->whereKey($data['leave_type_id'])
            ->firstOrFail();

        if ($leaveType->wajib_lampiran && ! $request->hasFile('attachment')) {
            throw ValidationException::withMessages([
                'attachment' => 'Lampiran bukti wajib diunggah untuk jenis cuti ini.',
            ]);
        }

        if ($request->hasFile('attachment')) {
            $data['attachment_path'] = $request->file('attachment')->store('leave-attachments', 'public');
        }

        $leave = Leave::create(array_merge($data, [
            'tenant_id' => $tenantId,
            'employee_id' => $employee->id,
        ], $this->buildInitialApprovalState($leaveType)));

        return response()->json([
            'message' => 'Pengajuan cuti berhasil dikirim.',
            'data' => $this->serializeLeave($leave->loadMissing('leaveType')),
        ], 201);
    }

    protected function mobileUser(Request $request): User
    {
        $user = $request->user();

        abort_unless($user?->isEmployee(), 403);
        abort_unless($user->hasMenuAccess('leaves.create'), 403);

        return $user;
    }

    protected function serializeEmployee(Employee $employee): array
    {
        return [
            'id' => $employee->id,
            'name' => $employee->name,
            'employee_code' => $employee->employee_code,
        ];
    }

    protected function serializeLeaveTypes(int $tenantId): array
    {
        return LeaveType::query()
            ->where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get()
            ->map(fn (LeaveType $type) => [
                'id' => $type->id,
                'name' => $type->name,
                'code' => Leave::canonicalLeaveTypeCode($type->name),
                'is_paid' => (bool) $type->is_paid,
                'requires_attachment' => (bool) $type->wajib_lampiran,
                'requires_approval' => (bool) $type->wajib_persetujuan,
                'approval_flow' => $type->alur_persetujuan,
            ])
            ->values()
            ->all();
    }

    protected function buildMobileSummary(Collection $history): array
    {
        $summary = $this->buildSummary($history);

        return [
            'total' => $history->count(),
            'pending_requests' => (int) ($summary['pending']['requests'] ?? 0),
            'approved_requests' => (int) ($summary['approved']['requests'] ?? 0),
            'rejected_requests' => (int) ($summary['rejected']['requests'] ?? 0),
            'approved_days' => (int) ($summary['approved']['days'] ?? 0),
        ];
    }

    protected function serializeLeave(Leave $leave): array
    {
        return [
            'id' => $leave->id,
            'leave_type' => [
                'id' => $leave->leaveType?->id,
                'name' => $leave->leaveType?->name,
                'code' => Leave::canonicalLeaveTypeCode($leave->leaveType?->name),
            ],
            'start_date' => $leave->start_date?->toDateString(),
            'end_date' => $leave->end_date?->toDateString(),
            'duration_days' => (int) ($leave->duration ?? 0),
            'status' => $leave->status,
            'status_label' => ucfirst($leave->status ?? '-'),
            'reason' => $leave->reason,
            'requires_attachment' => (bool) ($leave->leaveType?->wajib_lampiran ?? false),
            'attachment_name' => $leave->attachment_path ? basename($leave->attachment_path) : null,
            'attachment_url' => $leave->attachment_path ? asset('storage/'.$leave->attachment_path) : null,
        ];
    }
}