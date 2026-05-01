<?php

namespace App\Http\Controllers;

use App\Exports\LeavesAnomalyResolutionExport;
use App\Exports\LeavesAnomalyResolutionAuditDashboardExport;
use App\Exports\LeavesAnomalyResolutionAuditLogExport;
use App\Services\LeavesAnomalyService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;

class LeavesAnomalyResolutionExportController extends Controller
{
    public function __construct(protected LeavesAnomalyService $anomalyService)
    {
        $this->middleware('permission:leaves.manage');
    }

    public function xlsx(Request $request)
    {
        $currentUser = $request->user() ?? auth()->user();
        $payload = $this->anomalyService->buildResolutionExportPayload($currentUser, $request->integer('tenant_id'));

        return Excel::download(
            new LeavesAnomalyResolutionExport($payload),
            $this->anomalyService->buildResolutionFilename($currentUser, $payload['tenant'], 'xlsx'),
            ExcelFormat::XLSX
        );
    }

    public function pdf(Request $request)
    {
        $currentUser = $request->user() ?? auth()->user();
        $payload = $this->anomalyService->buildResolutionExportPayload($currentUser, $request->integer('tenant_id'));

        $pdf = Pdf::loadView('leaves.exports.anomaly-resolutions-pdf', $payload)
            ->setPaper('a4', 'landscape');

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$this->anomalyService->buildResolutionFilename($currentUser, $payload['tenant'], 'pdf').'"',
        ]);
    }

    public function auditLogXlsx(Request $request)
    {
        $currentUser = $request->user() ?? auth()->user();
        $payload = $this->anomalyService->buildResolutionAuditLogExportPayload(
            $currentUser,
            $request->integer('tenant_id'),
            $request->integer('month'),
            $request->integer('year'),
            $request->string('search')->toString()
        );

        return Excel::download(
            new LeavesAnomalyResolutionAuditLogExport($payload),
            $this->anomalyService->buildResolutionAuditLogFilename($currentUser, $payload['tenant'], 'xlsx'),
            ExcelFormat::XLSX
        );
    }

    public function auditLogPdf(Request $request)
    {
        $currentUser = $request->user() ?? auth()->user();
        $payload = $this->anomalyService->buildResolutionAuditLogExportPayload(
            $currentUser,
            $request->integer('tenant_id'),
            $request->integer('month'),
            $request->integer('year'),
            $request->string('search')->toString()
        );

        $pdf = Pdf::loadView('leaves.exports.anomaly-resolution-audit-log-pdf', $payload)
            ->setPaper('a4', 'landscape');

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$this->anomalyService->buildResolutionAuditLogFilename($currentUser, $payload['tenant'], 'pdf').'"',
        ]);
    }

    public function auditDashboardXlsx(Request $request)
    {
        $currentUser = $request->user() ?? auth()->user();
        $payload = $this->anomalyService->buildResolutionAuditDashboardExportPayload(
            $currentUser,
            $request->integer('tenant_id'),
            $request->integer('month'),
            $request->integer('year'),
            $request->string('search')->toString()
        );

        return Excel::download(
            new LeavesAnomalyResolutionAuditDashboardExport($payload),
            $this->anomalyService->buildResolutionAuditDashboardFilename($currentUser, $payload['tenant'], 'xlsx'),
            ExcelFormat::XLSX
        );
    }

    public function auditDashboardPdf(Request $request)
    {
        $currentUser = $request->user() ?? auth()->user();
        $payload = $this->anomalyService->buildResolutionAuditDashboardExportPayload(
            $currentUser,
            $request->integer('tenant_id'),
            $request->integer('month'),
            $request->integer('year'),
            $request->string('search')->toString()
        );

        $pdf = Pdf::loadView('leaves.exports.anomaly-resolution-audit-dashboard-pdf', $payload)
            ->setPaper('a4', 'landscape');

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$this->anomalyService->buildResolutionAuditDashboardFilename($currentUser, $payload['tenant'], 'pdf').'"',
        ]);
    }
}