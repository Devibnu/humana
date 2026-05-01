<?php

namespace App\Exports;

use App\Models\Employee;
use Illuminate\Support\Collection;

class AttendanceEmployeeCsvExport extends AttendanceEmployeeSheetExport
{
    public function __construct(Employee $employee, Collection $attendances, array $filters = [])
    {
        parent::__construct($employee, $attendances, [
            ...$filters,
            'format' => 'csv',
        ]);
    }
}