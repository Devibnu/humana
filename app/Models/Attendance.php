<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'employee_id',
        'work_schedule_id',
        'leave_id',
        'date',
        'check_in',
        'check_out',
        'scheduled_check_in',
        'scheduled_check_out',
        'late_minutes',
        'early_leave_minutes',
        'status',
        'overtime_hours',
    ];

    protected $casts = [
        'date' => 'date',
        'late_minutes' => 'integer',
        'early_leave_minutes' => 'integer',
        'overtime_hours' => 'decimal:2',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function workSchedule()
    {
        return $this->belongsTo(WorkSchedule::class);
    }

    public function leave()
    {
        return $this->belongsTo(Leave::class);
    }

    public function attendanceLog()
    {
        return $this->hasOne(AttendanceLog::class);
    }
}
