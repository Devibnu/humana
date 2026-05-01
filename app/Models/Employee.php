<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'tenant_id',
        'employee_code',
        'name',
        'email',
        'phone',
        'ktp_number',
        'kk_number',
        'education',
        'dob',
        'gender',
        'address',
        'role',
        'position_id',
        'department_id',
        'work_location_id',
        'start_date',
        'avatar_path',
        'status',
        'marital_status',
        'employment_type',
        'contract_start_date',
        'contract_end_date',
    ];

    protected $casts = [
        'start_date'          => 'date',
        'dob'                 => 'date',
        'contract_start_date' => 'date',
        'contract_end_date'   => 'date',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function position()
    {
        return $this->belongsTo(Position::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function workLocation()
    {
        return $this->belongsTo(WorkLocation::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function leaves()
    {
        return $this->hasMany(Leave::class);
    }

    public function familyMembers()
    {
        return $this->hasMany(FamilyMember::class);
    }

    public function bankAccounts()
    {
        return $this->hasMany(BankAccount::class);
    }
}