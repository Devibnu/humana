<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class DepartmentsImport implements WithHeadingRow, SkipsEmptyRows
{
}
