<?php

namespace App\Exports;

use App\Models\Employee;
use Illuminate\Support\Collection;

class LeavesEmployeeExport extends LeavesEmployeeSheetExport
{
    public function __construct(Employee $employee, Collection $leaves, array $summary = [])
    {
        parent::__construct($employee, $leaves, $summary, [
            'format' => 'xlsx',
        ]);
    }
}