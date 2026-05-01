<?php

namespace App\Http\Controllers;

use App\Models\AbsenceRule;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AbsenceRuleController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:attendances');
    }

    public function index()
    {
        $rules = AbsenceRule::with('tenant')->paginate(10);
        return view('absence_rules.index', compact('rules'));
    }

    public function create()
    {
        $tenants = Tenant::orderBy('name')->get();
        return view('absence_rules.create', compact('tenants'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tenant_id' => ['required', Rule::exists('tenants', 'id')],
            'working_hours_per_day' => ['required','integer','min:1'],
            'working_days_per_month' => ['required','integer','min:1'],
            'tolerance_minutes' => ['required','integer','min:0'],
            'rate_type' => ['required','in:flat,proportional'],
            'alpha_full_day' => ['sometimes','boolean'],
        ]);

        AbsenceRule::updateOrCreate([
            'tenant_id' => $validated['tenant_id'],
        ], array_merge($validated, [
            'alpha_full_day' => $request->has('alpha_full_day') ? (bool) $request->input('alpha_full_day') : true,
        ]));

        return redirect()->route('absence_rules.index')->with('success', 'Aturan absensi berhasil disimpan');
    }
}
