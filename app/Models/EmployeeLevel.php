<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeLevel extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'code',
        'name',
        'description',
        'status',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public static function defaults(): array
    {
        return [
            ['code' => 'staff', 'name' => 'Staff', 'sort_order' => 10],
            ['code' => 'supervisor', 'name' => 'Supervisor', 'sort_order' => 20],
            ['code' => 'manager', 'name' => 'Manager', 'sort_order' => 30],
        ];
    }

    public static function defaultsAsOptions(): array
    {
        return collect(self::defaults())
            ->mapWithKeys(fn (array $level) => [$level['code'] => $level['name']])
            ->all();
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function employees()
    {
        return $this->hasMany(Employee::class, 'role', 'code')
            ->whereColumn('employees.tenant_id', 'employee_levels.tenant_id');
    }
}
