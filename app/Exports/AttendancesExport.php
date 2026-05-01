<?php

namespace App\Exports;

use Illuminate\Support\Collection;

class AttendancesExport extends AttendancesSheetExport
{
    public function __construct(Collection $attendances, array $filters = [])
    {
        parent::__construct($attendances, [
            ...$filters,
            'format' => 'xlsx',
        ]);
    }
}