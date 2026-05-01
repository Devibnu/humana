<?php

namespace App\Exports;

use App\Models\Employee;
use Illuminate\Support\Collection;

class LeavesEmployeeCsvExport extends LeavesEmployeeSheetExport
{
    public function __construct(Employee $employee, Collection $leaves, array $summary = [])
    {
        parent::__construct($employee, $leaves, $summary, [
            'format' => 'csv',
        ]);
    }
}