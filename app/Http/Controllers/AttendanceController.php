<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendanceLog;
use App\Models\Employee;
use App\Models\Tenant;
use App\Models\WorkLocation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AttendanceController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:attendances.manage')->only(['create', 'edit', 'store', 'update']);
        $this->middleware('permission:attendances.destroy')->only('destroy');
    }

    public function index(Request $request)
    {
        $currentUser = $request->user() ?? auth()->user();
        $tenantId = $currentUser->isManager() ? $currentUser->tenant_id : null;
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $selfAttendanceContext = $this->getSelfAttendanceContext($currentUser);

        $attendanceQuery = $this->buildIndexQuery($currentUser, $tenantId)
            ->when($startDate, fn ($query) => $query->whereDate('date', '>=', $startDate))
            ->when($endDate, fn ($query) => $query->whereDate('date', '<=', $endDate));

        $attendances = (clone $attendanceQuery)
            ->orderByDesc('date')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        $summaryCounts = (clone $attendanceQuery)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $summary = [
            'present' => (int) ($summaryCounts->get('present', 0) + $summaryCounts->get('late', 0)),
            'leave' => (int) $summaryCounts->get('leave', 0),
            'sick' => (int) $summaryCounts->get('sick', 0),
            'absent' => (int) $summaryCounts->get('absent', 0),
        ];

        return view('attendances.index', [
            'attendances' => $attendances,
            'summary' => $summary,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'currentUser' => $currentUser,
            'selfAttendanceContext' => $selfAttendanceContext,
        ] + $this->getFormData(new Attendance()));
    }

    public function create()
    {
        return view('attendances.create', $this->getFormData(new Attendance()));
    }

    public function store(Request $request)
    {
        $tenantId = $this->resolveTenantId($request);

        $data = $request->validate([
            'tenant_id' => ['required', 'exists:tenants,id'],
            'employee_id' => [
                'required',
                Rule::exists('employees', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'work_location_id' => [
                'required',
                Rule::exists('work_locations', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'date' => [
                'required',
                'date',
                Rule::unique('attendances', 'date')->where(fn ($query) => $query->where('employee_id', $request->employee_id)),
            ],
            'check_in' => ['nullable', 'date_format:H:i'],
            'check_out' => ['nullable', 'date_format:H:i'],
            'status' => ['required', Rule::in(array_keys($this->statuses()))],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $data['tenant_id'] = $tenantId;
        $employee = Employee::with(['workLocation', 'workSchedule'])->findOrFail($data['employee_id']);
        $workLocation = WorkLocation::query()->findOrFail($data['work_location_id']);
        $locationLog = $this->resolveAttendanceLocationLog($employee, $workLocation, $data);

        unset($data['latitude'], $data['longitude'], $data['work_location_id']);
        $data = $this->applyWorkScheduleCalculation($data, $employee);

        $attendance = Attendance::create($data);
        $this->syncAttendanceLog($attendance, $locationLog);

        session()->flash('success', 'Kehadiran berhasil ditambahkan');

        return redirect()->route('attendances.index');
    }

    public function selfService(Request $request)
    {
        $currentUser = $request->user() ?? auth()->user();

        abort_unless($currentUser?->isEmployee(), 403);

        $employee = $this->resolveSelfEmployee($currentUser);

        if (! $employee) {
            throw ValidationException::withMessages([
                'employee_id' => 'Akun Anda belum terhubung ke data karyawan.',
            ]);
        }

        $employee->loadMissing(['workLocation', 'workSchedule']);

        if (! $employee->workLocation) {
            throw ValidationException::withMessages([
                'work_location_id' => 'Lokasi kerja karyawan belum diatur.',
            ]);
        }

        $data = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'attendance_photo_data' => ['required', 'string'],
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
            return redirect()
                ->route('attendances.index')
                ->with('success', 'Absensi hari ini sudah lengkap.');
        }

        $locationLog = $this->resolveAttendanceLocationLog($employee, $employee->workLocation, $data);
        $photoPath = $this->storeLiveAttendancePhoto($data['attendance_photo_data']);

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

        return redirect()->route('attendances.index')->with('success', $message);
    }

    public function edit(Attendance $attendance)
    {
        $this->ensureManagerCanAccessAttendance($attendance);

        return view('attendances.edit', $this->getFormData($attendance));
    }

    public function update(Request $request, Attendance $attendance)
    {
        $this->ensureManagerCanAccessAttendance($attendance);

        $tenantId = $this->resolveTenantId($request, $attendance);

        $data = $request->validate([
            'tenant_id' => ['required', 'exists:tenants,id'],
            'employee_id' => [
                'required',
                Rule::exists('employees', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'work_location_id' => [
                'required',
                Rule::exists('work_locations', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'date' => [
                'required',
                'date',
                Rule::unique('attendances', 'date')
                    ->where(fn ($query) => $query->where('employee_id', $request->employee_id))
                    ->ignore($attendance->id),
            ],
            'check_in' => ['nullable', 'date_format:H:i'],
            'check_out' => ['nullable', 'date_format:H:i'],
            'status' => ['required', Rule::in(array_keys($this->statuses()))],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $data['tenant_id'] = $tenantId;
        $employee = Employee::with(['workLocation', 'workSchedule'])->findOrFail($data['employee_id']);
        $workLocation = WorkLocation::query()->findOrFail($data['work_location_id']);
        $locationLog = $this->resolveAttendanceLocationLog($employee, $workLocation, $data);

        unset($data['latitude'], $data['longitude'], $data['work_location_id']);
        $data = $this->applyWorkScheduleCalculation($data, $employee, $attendance);

        $attendance->update($data);
        $this->syncAttendanceLog($attendance, $locationLog);

        return redirect()->route('attendances.index')->with('success', 'Kehadiran berhasil diperbarui');
    }

    public function destroy(Attendance $attendance)
    {
        $this->ensureManagerCanAccessAttendance($attendance);

        $this->deleteAttendanceLogPhotos($attendance->attendanceLog()->first());
        $attendance->delete();

        return redirect()->route('attendances.index')->with('success', 'Kehadiran berhasil dihapus');
    }

    protected function statuses(): array
    {
        return [
            'present' => 'Hadir',
            'late' => 'Terlambat',
            'leave' => 'Izin',
            'sick' => 'Sakit',
            'absent' => 'Alpha',
        ];
    }

    protected function buildIndexQuery($currentUser, ?int $tenantId = null): Builder
    {
        return Attendance::query()
            ->with(['tenant', 'employee.workLocation', 'employee.workSchedule', 'workSchedule', 'attendanceLog.workLocation'])
            ->when($tenantId, fn ($query) => $query->where('tenant_id', $tenantId))
            ->when($currentUser->isEmployee(), function ($query) use ($currentUser) {
                $query->whereHas('employee', function ($employeeQuery) use ($currentUser) {
                    $employeeQuery
                        ->where('user_id', $currentUser->id)
                        ->when($currentUser->employee_id, fn ($query) => $query->orWhere('id', $currentUser->employee_id));
                });
            });
    }

    protected function getSelfAttendanceContext($currentUser): array
    {
        if (! $currentUser?->isEmployee()) {
            return [
                'employee' => null,
                'workLocation' => null,
                'todayAttendance' => null,
                'nextAction' => null,
            ];
        }

        $employee = $this->resolveSelfEmployee($currentUser);

        if (! $employee) {
            return [
                'employee' => null,
                'workLocation' => null,
                'todayAttendance' => null,
                'nextAction' => null,
            ];
        }

        $employee->loadMissing(['workLocation', 'workSchedule']);

        $openAttendance = Attendance::query()
            ->where('employee_id', $employee->id)
            ->whereNotNull('check_in')
            ->whereNull('check_out')
            ->latest('date')
            ->latest('id')
            ->first();

        $todayAttendance = $openAttendance ?: Attendance::query()
            ->where('employee_id', $employee->id)
            ->whereDate('date', now()->toDateString())
            ->first();

        $nextAction = ! $todayAttendance || ! $todayAttendance->check_in
            ? 'check_in'
            : (! $todayAttendance->check_out ? 'check_out' : 'complete');

        return [
            'employee' => $employee,
            'workLocation' => $employee->workLocation,
            'workSchedule' => $employee->workSchedule,
            'todayAttendance' => $todayAttendance,
            'nextAction' => $nextAction,
        ];
    }

    protected function resolveSelfEmployee($currentUser): ?Employee
    {
        if (! $currentUser) {
            return null;
        }

        return Employee::query()
            ->with('workLocation')
            ->with('workSchedule')
            ->where('user_id', $currentUser->id)
            ->when($currentUser->employee_id, fn ($query) => $query->orWhere('id', $currentUser->employee_id))
            ->first();
    }

    protected function ensureManagerCanAccessAttendance(Attendance $attendance): void
    {
        $currentUser = auth()->user();

        if ($currentUser?->isManager() && $currentUser->tenant_id !== $attendance->tenant_id) {
            abort(403);
        }
    }

    protected function resolveTenantId(Request $request, ?Attendance $attendance = null): int
    {
        $currentUser = $request->user() ?? auth()->user();

        if ($currentUser?->isManager()) {
            return (int) $currentUser->tenant_id;
        }

        return (int) ($request->integer('tenant_id') ?: $attendance?->tenant_id);
    }

    protected function getFormData(Attendance $attendance): array
    {
        $currentUser = auth()->user();
        $isManager = (bool) $currentUser?->isManager();
        $tenantId = $isManager
            ? $currentUser?->tenant_id
            : ($attendance->tenant_id ?? $currentUser?->tenant_id);

        return [
            'attendance' => $attendance,
            'currentUser' => $currentUser,
            'isTenantLocked' => $isManager,
            'scopedTenantId' => $tenantId,
            'tenants' => $isManager && $tenantId
                ? Tenant::whereKey($tenantId)->get()
                : Tenant::orderBy('name')->get(),
            'employees' => $this->getScopedEmployees($currentUser, $tenantId),
            'workLocations' => $this->getScopedWorkLocations($tenantId),
            'statuses' => $this->statuses(),
            'attendanceLocationLog' => $attendance->relationLoaded('attendanceLog')
                ? $attendance->attendanceLog
                : $attendance->attendanceLog()->first(),
        ];
    }

    protected function getScopedEmployees($currentUser, ?int $tenantId)
    {
        return Employee::query()
            ->with('workLocation')
            ->with('workSchedule')
            ->when($currentUser?->isEmployee(), fn ($query) => $query->where('user_id', $currentUser->id))
            ->when($tenantId, fn ($query) => $query->where('tenant_id', $tenantId))
            ->orderBy('name')
            ->get();
    }

    protected function getScopedWorkLocations(?int $tenantId)
    {
        return WorkLocation::query()
            ->when($tenantId, fn ($query) => $query->where('tenant_id', $tenantId))
            ->orderBy('name')
            ->get();
    }

    protected function resolveAttendanceLocationLog(Employee $employee, WorkLocation $workLocation, array $data): ?array
    {
        if ((int) $workLocation->tenant_id !== (int) $employee->tenant_id) {
            throw ValidationException::withMessages([
                'work_location_id' => 'Lokasi kerja harus sesuai tenant karyawan.',
            ]);
        }

        if (! array_key_exists('latitude', $data) || ! array_key_exists('longitude', $data) || $data['latitude'] === null || $data['longitude'] === null) {
            throw ValidationException::withMessages([
                'latitude' => 'Tidak dapat menangkap koordinat perangkat, silakan izinkan akses lokasi',
            ]);
        }

        $distanceMeters = $this->calculateDistanceInMeters(
            (float) $workLocation->latitude,
            (float) $workLocation->longitude,
            (float) $data['latitude'],
            (float) $data['longitude']
        );

        if ($distanceMeters > (float) $workLocation->radius) {
            throw ValidationException::withMessages([
                'latitude' => 'Kehadiran berada di luar radius lokasi kerja yang diizinkan.',
            ]);
        }

        return [
            'tenant_id' => $employee->tenant_id,
            'employee_id' => $employee->id,
            'work_location_id' => $workLocation->id,
            'latitude' => round((float) $data['latitude'], 7),
            'longitude' => round((float) $data['longitude'], 7),
            'distance_meters' => round($distanceMeters, 2),
        ];
    }

    protected function storeLiveAttendancePhoto(string $photoData): string
    {
        if (! preg_match('/^data:image\/(jpeg|jpg|png|webp);base64,/', $photoData, $matches)) {
            throw ValidationException::withMessages([
                'attendance_photo_data' => 'Foto absensi harus diambil langsung dari kamera.',
            ]);
        }

        $base64Payload = substr($photoData, strpos($photoData, ',') + 1);
        $binaryPhoto = base64_decode($base64Payload, true);

        if ($binaryPhoto === false || strlen($binaryPhoto) > 2 * 1024 * 1024) {
            throw ValidationException::withMessages([
                'attendance_photo_data' => 'Foto absensi tidak valid atau melebihi 2 MB.',
            ]);
        }

        $extension = $matches[1] === 'jpg' ? 'jpeg' : $matches[1];
        $path = 'attendance-photos/'.now()->format('Y/m').'/'.uniqid('attendance-', true).'.'.$extension;

        Storage::disk('public')->put($path, $binaryPhoto);

        return $path;
    }

    protected function applyWorkScheduleCalculation(array $data, Employee $employee, ?Attendance $attendance = null): array
    {
        $employee->loadMissing('workSchedule');
        $schedule = $employee->workSchedule;

        $data['work_schedule_id'] = $schedule?->id;
        $data['scheduled_check_in'] = $schedule?->check_in_time ? substr($schedule->check_in_time, 0, 5) : null;
        $data['scheduled_check_out'] = $schedule?->check_out_time ? substr($schedule->check_out_time, 0, 5) : null;
        $data['late_minutes'] = 0;
        $data['early_leave_minutes'] = 0;

        if (! $schedule) {
            return $data;
        }

        $date = $data['date'] ?? $attendance?->date?->format('Y-m-d') ?? now()->toDateString();
        $checkIn = $data['check_in'] ?? $attendance?->check_in;
        $checkOut = $data['check_out'] ?? $attendance?->check_out;

        if ($checkIn) {
            $data['late_minutes'] = $this->calculateLateMinutes($date, $checkIn, $schedule);
        }

        if ($checkOut) {
            $data['early_leave_minutes'] = $this->calculateEarlyLeaveMinutes($date, $checkIn, $checkOut, $schedule);
        }

        if (in_array(($data['status'] ?? $attendance?->status), ['present', 'late'], true)) {
            $data['status'] = $data['late_minutes'] > 0 ? 'late' : 'present';
        }

        return $data;
    }

    protected function calculateLateMinutes(string $date, string $checkIn, $schedule): int
    {
        $actualCheckIn = Carbon::parse($date.' '.$checkIn);
        $allowedCheckIn = Carbon::parse($date.' '.substr($schedule->check_in_time, 0, 5))
            ->addMinutes((int) $schedule->late_tolerance_minutes);

        return $actualCheckIn->greaterThan($allowedCheckIn)
            ? $allowedCheckIn->diffInMinutes($actualCheckIn)
            : 0;
    }

    protected function calculateEarlyLeaveMinutes(string $date, ?string $checkIn, string $checkOut, $schedule): int
    {
        $scheduledCheckIn = Carbon::parse($date.' '.substr($schedule->check_in_time, 0, 5));
        $scheduledCheckOut = Carbon::parse($date.' '.substr($schedule->check_out_time, 0, 5));

        if ($scheduledCheckOut->lessThanOrEqualTo($scheduledCheckIn)) {
            $scheduledCheckOut->addDay();
        }

        $actualCheckOut = Carbon::parse($date.' '.$checkOut);

        if ($checkIn && $actualCheckOut->lessThanOrEqualTo(Carbon::parse($date.' '.$checkIn))) {
            $actualCheckOut->addDay();
        }

        $allowedCheckOut = $scheduledCheckOut->copy()->subMinutes((int) $schedule->early_leave_tolerance_minutes);

        return $actualCheckOut->lessThan($allowedCheckOut)
            ? $actualCheckOut->diffInMinutes($allowedCheckOut)
            : 0;
    }

    protected function syncAttendanceLog(Attendance $attendance, ?array $locationLog): void
    {
        if ($locationLog === null) {
            $existingLog = $attendance->attendanceLog()->first();
            $this->deleteAttendanceLogPhotos($existingLog);
            $existingLog?->delete();

            return;
        }

        AttendanceLog::updateOrCreate(
            ['attendance_id' => $attendance->id],
            $locationLog + ['attendance_id' => $attendance->id]
        );
    }

    protected function deleteAttendanceLogPhotos(?AttendanceLog $attendanceLog): void
    {
        if (! $attendanceLog) {
            return;
        }

        foreach (['check_in_photo_path', 'check_out_photo_path'] as $column) {
            if ($attendanceLog->{$column}) {
                Storage::disk('public')->delete($attendanceLog->{$column});
            }
        }
    }

    protected function calculateDistanceInMeters(float $originLatitude, float $originLongitude, float $targetLatitude, float $targetLongitude): float
    {
        $earthRadius = 6371000;
        $latitudeDelta = deg2rad($targetLatitude - $originLatitude);
        $longitudeDelta = deg2rad($targetLongitude - $originLongitude);
        $originLatitudeRadians = deg2rad($originLatitude);
        $targetLatitudeRadians = deg2rad($targetLatitude);

        $haversine = sin($latitudeDelta / 2) ** 2
            + cos($originLatitudeRadians) * cos($targetLatitudeRadians) * sin($longitudeDelta / 2) ** 2;

        return 2 * $earthRadius * asin(min(1, sqrt($haversine)));
    }
}
