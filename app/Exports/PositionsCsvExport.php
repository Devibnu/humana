<?php

namespace App\Exports;

use Illuminate\Support\Collection;

class PositionsCsvExport extends PositionsDataSheetExport
{
    public function __construct(Collection $positions, array $filters = [])
    {
        parent::__construct($positions, [
            ...$filters,
            'format' => 'csv',
        ]);
    }
}