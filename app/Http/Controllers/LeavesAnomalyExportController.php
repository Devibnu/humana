<?php

namespace App\Http\Controllers;

use App\Exports\LeavesAnomalyExport;
use App\Services\LeavesAnomalyService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;

class LeavesAnomalyExportController extends Controller
{
    public function __construct(protected LeavesAnomalyService $anomalyService)
    {
        $this->middleware('permission:leaves.manage');
    }

    public function xlsx(Request $request)
    {
        $currentUser = $request->user() ?? auth()->user();
        $payload = $this->anomalyService->buildExportPayload($currentUser, $request->integer('tenant_id'));

        return Excel::download(
            new LeavesAnomalyExport($payload),
            $this->anomalyService->buildFilename($currentUser, $payload['tenant'], 'xlsx'),
            ExcelFormat::XLSX
        );
    }

    public function pdf(Request $request)
    {
        $currentUser = $request->user() ?? auth()->user();
        $payload = $this->anomalyService->buildExportPayload($currentUser, $request->integer('tenant_id'));

        $pdf = Pdf::loadView('leaves.exports.anomalies-pdf', $payload)
            ->setPaper('a4', 'portrait');

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$this->anomalyService->buildFilename($currentUser, $payload['tenant'], 'pdf').'"',
        ]);
    }
}