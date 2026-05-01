<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class AttendancesDashboardController extends Controller
{
    public function index(Request $request)
    {
        $currentUser = $request->user() ?? auth()->user();

        if ($currentUser?->isEmployee()) {
            return redirect()->route('attendances.index');
        }

        $today = Carbon::today();
        $todayDate = $today->toDateString();
        $todayCounts = $this->buildMappedStatusCounts(
            $this->baseAttendanceQuery($currentUser)->whereDate('date', $todayDate)
        );

        $totalKehadiranHariIni = array_sum($todayCounts);
        $totalHadir = $todayCounts['present'];
        $totalIzin = $todayCounts['leave'];
        $totalSakit = $todayCounts['sick'];
        $totalAlpha = $todayCounts['absent'];
        $totalTidakHadir = $totalIzin + $totalSakit + $totalAlpha;
        $totalKaryawanScope = $this->baseEmployeeQuery($currentUser)->count();

        $statusDistributionChart = [
            'labels' => ['Hadir', 'Izin', 'Sakit', 'Alpha'],
            'counts' => [$totalHadir, $totalIzin, $totalSakit, $totalAlpha],
            'backgroundColor' => ['#82d616', '#fbcf33', '#17c1e8', '#ea0606'],
        ];

        $attendanceTrendChart = $this->buildAttendanceTrendChart($currentUser, $today);

        $summaryBadges = [
            'Hadir' => $totalHadir,
            'Izin' => $totalIzin,
            'Sakit' => $totalSakit,
            'Alpha' => $totalAlpha,
        ];

        return view('attendances.dashboard', compact(
            'currentUser',
            'totalKehadiranHariIni',
            'totalHadir',
            'totalIzin',
            'totalSakit',
            'totalAlpha',
            'totalTidakHadir',
            'totalKaryawanScope',
            'statusDistributionChart',
            'attendanceTrendChart',
            'summaryBadges'
        ));
    }

    protected function buildMappedStatusCounts(Builder $query): array
    {
        $rawCounts = (clone $query)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return [
            'present' => (int) ($rawCounts->get('present', 0) + $rawCounts->get('late', 0)),
            'leave' => (int) $rawCounts->get('leave', 0),
            'sick' => (int) $rawCounts->get('sick', 0),
            'absent' => (int) $rawCounts->get('absent', 0),
        ];
    }

    protected function buildAttendanceTrendChart($currentUser, Carbon $today): array
    {
        $startDate = $today->copy()->subDays(6)->toDateString();
        $endDate = $today->toDateString();

        $rows = $this->baseAttendanceQuery($currentUser)
            ->whereBetween('date', [$startDate, $endDate])
            ->selectRaw('date, COUNT(*) as aggregate')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('aggregate', 'date');

        $labels = [];
        $counts = [];

        for ($offset = 6; $offset >= 0; $offset--) {
            $date = $today->copy()->subDays($offset);
            $dateKey = $date->toDateString();
            $labels[] = $date->translatedFormat('d M');
            $counts[] = (int) $rows->get($dateKey, 0);
        }

        return [
            'labels' => $labels,
            'counts' => $counts,
            'backgroundColor' => '#17c1e8',
            'borderColor' => '#17c1e8',
        ];
    }

    protected function baseAttendanceQuery($currentUser): Builder
    {
        return Attendance::query()
            ->when($currentUser?->isManager(), fn (Builder $query) => $query->where('tenant_id', $currentUser->tenant_id));
    }

    protected function baseEmployeeQuery($currentUser): Builder
    {
        return Employee::query()
            ->when($currentUser?->isManager(), fn (Builder $query) => $query->where('tenant_id', $currentUser->tenant_id));
    }
}