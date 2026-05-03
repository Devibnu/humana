<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'slug',
        'domain',
        'status',
        'subscription_plan',
        'description',
        'address',
        'contact',
        'login_footer_text',
        'branding_path',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::created(function (Tenant $tenant): void {
            if (Schema::hasTable('employee_levels')) {
                foreach (EmployeeLevel::defaults() as $level) {
                    $tenant->employeeLevels()->firstOrCreate(
                        ['code' => $level['code']],
                        [
                            'name' => $level['name'],
                            'status' => 'active',
                            'sort_order' => $level['sort_order'],
                        ]
                    );
                }
            }

            if (Schema::hasTable('work_schedules')) {
                foreach (WorkSchedule::defaults() as $schedule) {
                    $tenant->workSchedules()->firstOrCreate(
                        ['code' => $schedule['code']],
                        [
                            'name' => $schedule['name'],
                            'check_in_time' => $schedule['check_in_time'],
                            'check_out_time' => $schedule['check_out_time'],
                            'late_tolerance_minutes' => $schedule['late_tolerance_minutes'],
                            'early_leave_tolerance_minutes' => $schedule['early_leave_tolerance_minutes'],
                            'status' => 'active',
                            'sort_order' => $schedule['sort_order'],
                        ]
                    );
                }
            }
        });
    }

    public function workSchedules()
    {
        return $this->hasMany(WorkSchedule::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    public function departments()
    {
        return $this->hasMany(Department::class);
    }

    public function employeeLevels()
    {
        return $this->hasMany(EmployeeLevel::class);
    }

    public function payrollSetting()
    {
        return $this->hasOne(PayrollSetting::class);
    }

    public function payrollPeriods()
    {
        return $this->hasMany(PayrollPeriod::class);
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }
}
