<?php

namespace App\Http\Controllers;

use App\Exports\AttendanceAnalyticsExport;
use App\Support\AttendanceAnalyticsReportBuilder;
use App\Support\AttendanceAnalyticsSvgBuilder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;

class AttendancesAnalyticsExportController extends Controller
{
    public function __construct(
        protected AttendanceAnalyticsReportBuilder $reportBuilder,
        protected AttendanceAnalyticsSvgBuilder $svgBuilder,
    ) {
    }

    public function xlsx(Request $request)
    {
        $currentUser = $request->user() ?? auth()->user();
        abort_if($currentUser?->isEmployee(), 403);

        $report = $this->reportBuilder->build(
            $currentUser,
            $request->integer('year'),
            $request->integer('month'),
            $request->integer('tenant_id')
        );

        return Excel::download(
            new AttendanceAnalyticsExport($report),
            $this->buildFilename($currentUser, $report['tenant'], (int) $report['selectedYear'], 'xlsx'),
            ExcelFormat::XLSX
        );
    }

    public function pdf(Request $request)
    {
        $currentUser = $request->user() ?? auth()->user();
        abort_if($currentUser?->isEmployee(), 403);

        $report = $this->reportBuilder->build(
            $currentUser,
            $request->integer('year'),
            $request->integer('month'),
            $request->integer('tenant_id')
        );

        $payload = [
            ...$report,
            'monthlyTrendSvg' => $this->svgBuilder->buildLineChart($report['monthlyTrendChart'], $report['statusMeta']),
            'yearlyDistributionSvg' => $this->svgBuilder->buildBarChart($report['yearlyDistributionChart']),
            'statusDistributionSvg' => $this->svgBuilder->buildPieChart($report['statusDistributionChart']),
        ];

        $pdf = Pdf::loadView('attendances.exports.analytics-pdf', $payload)
            ->setPaper('a4', 'portrait');

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$this->buildFilename($currentUser, $report['tenant'], (int) $report['selectedYear'], 'pdf').'"',
        ]);
    }

    protected function buildFilename($currentUser, $tenant, int $selectedYear, string $extension): string
    {
        return 'attendance_analytics_'.$this->buildTenantScopeLabel($currentUser, $tenant).'_'.$selectedYear.'_'.now()->format('Ymd').'.'.$extension;
    }

    protected function buildTenantScopeLabel($currentUser, $tenant): string
    {
        if ($currentUser?->isManager()) {
            return Str::slug($currentUser->tenant?->name ?? 'tenant-manager', '-');
        }

        return Str::slug($tenant?->name ?? $currentUser?->tenant?->name ?? 'tenant-admin', '-');
    }
}