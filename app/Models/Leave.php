<?php

namespace App\Models;

use App\Services\LeaveAttendanceSyncService;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Leave extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::saved(function (Leave $leave): void {
            app(LeaveAttendanceSyncService::class)->syncForLeave($leave);
        });

        static::deleted(function (Leave $leave): void {
            app(LeaveAttendanceSyncService::class)->clearForLeave($leave);
        });
    }

    protected $fillable = [
        'tenant_id',
        'employee_id',
        'leave_type_id',
        'start_date',
        'end_date',
        'reason',
        'attachment_path',
        'status',
        'approval_stage',
        'current_approval_role',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    protected function duration(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->start_date && $this->end_date
                ? (int) $this->start_date->diffInDays($this->end_date) + 1
                : null,
        );
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public static function canonicalLeaveTypeCode(?string $name): ?string
    {
        if ($name === null || $name === '') {
            return null;
        }

        $normalized = mb_strtolower(trim($name));

        return match ($normalized) {
            'annual', 'cuti tahunan' => 'annual',
            'sick', 'cuti sakit', 'sakit' => 'sick',
            'permission', 'izin', 'izin pribadi' => 'permission',
            'unpaid', 'cuti tanpa upah' => 'unpaid',
            default => str_replace(' ', '_', $normalized),
        };
    }
}