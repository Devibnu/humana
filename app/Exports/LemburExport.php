<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class LemburExport implements FromView
{
    protected $lemburs;

    public function __construct($lemburs)
    {
        $this->lemburs = $lemburs;
    }

    public function view(): View
    {
        return view('exports.lembur', [
            'lemburs' => $this->lemburs,
        ]);
    }
}
