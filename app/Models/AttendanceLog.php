<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_id',
        'tenant_id',
        'employee_id',
        'work_location_id',
        'latitude',
        'longitude',
        'distance_meters',
        'check_in_photo_path',
        'check_out_photo_path',
    ];

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function workLocation()
    {
        return $this->belongsTo(WorkLocation::class);
    }
}
