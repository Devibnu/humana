<?php

namespace App\Exports;

class LeaveEmployeeExport extends LeavesExport
{
    public function title(): string
    {
        $parts = ['Employee Leaves'];

        if (! empty($this->filters['tenant_name'])) {
            $parts[] = (string) $this->filters['tenant_name'];
        }

        return substr(implode(' ', $parts), 0, 31);
    }
}