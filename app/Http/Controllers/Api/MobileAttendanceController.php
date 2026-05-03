<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\AttendanceController;
use App\Models\Attendance;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class MobileAttendanceController extends AttendanceController
{
    public function __construct()
    {
        //
    }

    public function status(Request $request)
    {
        $employee = $this->mobileEmployee($request);
        $context = $this->getSelfAttendanceContext($request->user());

        return response()->json([
            'employee' => $this->serializeEmployee($employee),
            'work_location' => $this->serializeWorkLocation($context['workLocation'] ?? null),
            'work_schedule' => $this->serializeWorkSchedule($context['workSchedule'] ?? null),
            'today_attendance' => $this->serializeAttendance($context['todayAttendance'] ?? null),
            'next_action' => $context['nextAction'] ?? null,
        ]);
    }

    public function history(Request $request)
    {
        $employee = $this->mobileEmployee($request);
        $attendances = Attendance::query()
            ->with(['attendanceLog.workLocation', 'workSchedule'])
            ->where('employee_id', $employee->id)
            ->orderByDesc('date')
            ->latest('id')
            ->limit(30)
            ->get()
            ->map(fn (Attendance $attendance) => $this->serializeAttendance($attendance))
            ->values();

        return response()->json([
            'data' => $attendances,
        ]);
    }

    public function submit(Request $request)
    {
        $user = $request->user();
        $employee = $this->mobileEmployee($request);
        $employee->loadMissing(['workLocation', 'workSchedule']);

        if (! $employee->workLocation) {
            throw ValidationException::withMessages([
                'work_location_id' => 'Lokasi kerja karyawan belum diatur.',
            ]);
        }

        $data = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'photo' => ['required', 'string'],
        ]);

        $today = now()->toDateString();
        $currentTime = now()->format('H:i');
        $attendance = Attendance::query()
            ->where('employee_id', $employee->id)
            ->whereNotNull('check_in')
            ->whereNull('check_out')
            ->latest('date')
            ->latest('id')
            ->first();

        $todayCompleteAttendance = Attendance::query()
            ->where('employee_id', $employee->id)
            ->whereDate('date', $today)
            ->whereNotNull('check_in')
            ->whereNotNull('check_out')
            ->first();

        if (! $attendance && $todayCompleteAttendance) {
            return response()->json([
                'message' => 'Absensi hari ini sudah lengkap.',
                'attendance' => $this->serializeAttendance($todayCompleteAttendance->loadMissing(['attendanceLog.workLocation', 'workSchedule'])),
                'next_action' => 'complete',
            ]);
        }

        $locationLog = $this->resolveAttendanceLocationLog($employee, $employee->workLocation, $data);
        $photoPath = $this->storeLiveAttendancePhoto($data['photo']);

        if (! $attendance) {
            $locationLog['check_in_photo_path'] = $photoPath;
            $attendance = Attendance::create($this->applyWorkScheduleCalculation([
                'tenant_id' => $employee->tenant_id,
                'employee_id' => $employee->id,
                'date' => $today,
                'check_in' => $currentTime,
                'check_out' => null,
                'status' => 'present',
            ], $employee));

            $message = 'Absen masuk berhasil disimpan.';
        } else {
            $locationLog['check_out_photo_path'] = $photoPath;
            $attendance->update($this->applyWorkScheduleCalculation([
                'check_out' => $currentTime,
            ], $employee, $attendance));

            $message = 'Absen pulang berhasil disimpan.';
        }

        $this->syncAttendanceLog($attendance, $locationLog);

        return response()->json([
            'message' => $message,
            'attendance' => $this->serializeAttendance($attendance->fresh(['attendanceLog.workLocation', 'workSchedule'])),
            'next_action' => $this->getSelfAttendanceContext($user)['nextAction'] ?? null,
        ]);
    }

    protected function mobileEmployee(Request $request): Employee
    {
        $user = $request->user();

        abort_unless($user?->isEmployee(), 403);

        $employee = $this->resolveSelfEmployee($user);

        if (! $employee) {
            throw ValidationException::withMessages([
                'employee_id' => 'Akun Anda belum terhubung ke data karyawan.',
            ]);
        }

        return $employee->loadMissing(['workLocation', 'workSchedule']);
    }

    protected function serializeEmployee(Employee $employee): array
    {
        return [
            'id' => $employee->id,
            'name' => $employee->name,
            'employee_code' => $employee->employee_code,
        ];
    }

    protected function serializeWorkLocation($workLocation): ?array
    {
        if (! $workLocation) {
            return null;
        }

        return [
            'id' => $workLocation->id,
            'name' => $workLocation->name,
            'radius' => (float) $workLocation->radius,
            'latitude' => (float) $workLocation->latitude,
            'longitude' => (float) $workLocation->longitude,
        ];
    }

    protected function serializeWorkSchedule($workSchedule): ?array
    {
        if (! $workSchedule) {
            return null;
        }

        return [
            'id' => $workSchedule->id,
            'name' => $workSchedule->name,
            'check_in_time' => substr($workSchedule->check_in_time, 0, 5),
            'check_out_time' => substr($workSchedule->check_out_time, 0, 5),
        ];
    }

    protected function serializeAttendance(?Attendance $attendance): ?array
    {
        if (! $attendance) {
            return null;
        }

        $attendanceLog = $attendance->attendanceLog;

        return [
            'id' => $attendance->id,
            'date' => $attendance->date ? Carbon::parse($attendance->date)->toDateString() : null,
            'status' => $attendance->status,
            'check_in' => $attendance->check_in ? substr($attendance->check_in, 0, 5) : null,
            'check_out' => $attendance->check_out ? substr($attendance->check_out, 0, 5) : null,
            'scheduled_check_in' => $attendance->scheduled_check_in ? substr($attendance->scheduled_check_in, 0, 5) : null,
            'scheduled_check_out' => $attendance->scheduled_check_out ? substr($attendance->scheduled_check_out, 0, 5) : null,
            'late_minutes' => (int) $attendance->late_minutes,
            'early_leave_minutes' => (int) $attendance->early_leave_minutes,
            'work_location' => $this->serializeWorkLocation($attendanceLog?->workLocation),
            'distance_meters' => $attendanceLog?->distance_meters ? (float) $attendanceLog->distance_meters : null,
        ];
    }
}
