@extends('layouts.user_type.auth')

@section('content')

@php($activeEmployeeLeaveFilters = array_filter([
    $selectedMonth ? 'Month: '.($monthOptions[$selectedMonth] ?? $selectedMonth) : null,
    $selectedYear ? 'Year: '.$selectedYear : null,
]))

<div class="row">
    <div class="col-12">
        <div class="card mb-4 mx-4">
            <div class="card-header pb-0">
                <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap">
                    <div>
                        <h5 class="mb-0">Leave Summary: {{ $employee->name }}</h5>
                        <p class="text-sm mb-0">{{ $employee->employee_code }} • {{ $employee->tenant?->name ?? '-' }}</p>
                        <p class="text-xs text-secondary mb-0 mt-1">Filtered scope: {{ $filteredSummaryLabel }}</p>
                        <div class="mt-2 d-flex gap-2 flex-wrap">
                            <span class="badge bg-gradient-warning">Pending: {{ $summary['pending']['requests'] ?? 0 }} requests / {{ $summary['pending']['days'] ?? 0 }} days</span>
                            <span class="badge bg-gradient-success">Approved: {{ $summary['approved']['requests'] ?? 0 }} requests / {{ $summary['approved']['days'] ?? 0 }} days</span>
                            <span class="badge bg-gradient-danger">Rejected: {{ $summary['rejected']['requests'] ?? 0 }} requests / {{ $summary['rejected']['days'] ?? 0 }} days</span>
                        </div>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="{{ route('employees.leaves.export', array_merge(['employee' => $employee], request()->only(['month', 'year']), ['format' => 'csv'])) }}" class="btn btn-outline-dark btn-sm mb-0">Export CSV</a>
                        <a href="{{ route('employees.leaves.export', array_merge(['employee' => $employee], request()->only(['month', 'year']), ['format' => 'xlsx'])) }}" class="btn btn-outline-success btn-sm mb-0">Export XLSX</a>
                        <a href="{{ route('leaves.index') }}" class="btn btn-light btn-sm mb-0">Back to Leaves</a>
                    </div>
                </div>
                <form action="{{ route('employees.leaves.show', $employee) }}" method="GET" class="row mt-3">
                    <div class="col-md-3 col-sm-6 mb-3 mb-md-0">
                        <label class="form-label">Month</label>
                        <select name="month" class="form-control">
                            <option value="">All Months</option>
                            @foreach ($monthOptions as $monthValue => $monthLabel)
                                <option value="{{ $monthValue }}" @selected($selectedMonth === $monthValue)>{{ $monthLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3 mb-md-0">
                        <label class="form-label">Year</label>
                        <select name="year" class="form-control">
                            <option value="">All Years</option>
                            @foreach ($yearOptions as $yearOption)
                                <option value="{{ $yearOption }}" @selected($selectedYear === $yearOption)>{{ $yearOption }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 col-sm-12 d-flex align-items-end gap-2">
                        <button type="submit" class="btn bg-gradient-dark mb-0">Filter</button>
                        <a href="{{ route('employees.leaves.show', $employee) }}" class="btn btn-light mb-0">Reset</a>
                        @if (count($activeEmployeeLeaveFilters) > 0)
                            <div class="d-flex gap-2 flex-wrap ms-md-2">
                                @foreach ($activeEmployeeLeaveFilters as $activeFilter)
                                    <span class="badge bg-gradient-light text-dark">{{ $activeFilter }}</span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </form>
            </div>
            <div class="card-body pt-3 pb-0">
                <div class="row">
                    <div class="col-lg-4 mb-4">
                        <div class="card bg-gradient-dark h-100">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                                    <div>
                                        <p class="text-white text-sm mb-1">Monthly Leave Pattern</p>
                                        <h6 class="text-white mb-0">{{ $employeeMonthlySparkline['year'] }}</h6>
                                        <p class="text-white-50 text-xs mb-0">Total leave days per month.</p>
                                    </div>
                                    <span class="badge bg-gradient-light text-dark">{{ $employeeMonthlySparkline['aggregation_mode'] === 'split_range' ? 'Split Range' : 'Start Date' }}</span>
                                </div>
                                <div class="mt-3">
                                    @if ($employeeMonthlySparkline['has_data'])
                                        <div class="chart" style="height: 120px;">
                                            <canvas id="employee-leave-sparkline" class="chart-canvas"></canvas>
                                        </div>
                                    @else
                                        <div class="border border-radius-lg p-3 text-center bg-gradient-faded-dark">
                                            <p class="text-white text-sm mb-1">Sparkline Empty State</p>
                                            <p class="text-white-50 text-xs mb-0">{{ $employeeMonthlySparkline['empty_state'] }}</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-8">
                        <div class="row">
                            @foreach ($employeeCardSummary as $summaryCard)
                            <div class="col-xl-4 col-md-6 mb-4">
                                <div class="card h-100">
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between align-items-start gap-3">
                                            <div>
                                                <p class="text-sm mb-1">{{ $filteredSummaryLabel }} Summary</p>
                                                <h6 class="mb-2">{{ $summaryCard['status_label'] }}</h6>
                                                <p class="text-xs text-secondary mb-0">{{ $summaryCard['requests'] }} requests</p>
                                                <p class="text-xs text-secondary mb-0">{{ $summaryCard['days'] }} days</p>
                                            </div>
                                            <span class="badge {{ $summaryCard['status'] === 'approved' ? 'bg-gradient-success' : ($summaryCard['status'] === 'rejected' ? 'bg-gradient-danger' : 'bg-gradient-warning') }}">{{ strtoupper(substr($summaryCard['status_label'], 0, 3)) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body px-0 pt-0 pb-2">
                <div class="table-responsive p-0">
                    <table class="table align-items-center mb-0">
                        <thead>
                            <tr>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-4">Leave Type</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">Date Range</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">Duration</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">Reason</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($leaves as $leave)
                                <tr>
                                    <td class="ps-4"><span class="text-secondary text-xs font-weight-bold">{{ $leave->leaveType?->name ?? '—' }}</span></td>
                                    <td class="text-center"><span class="text-secondary text-xs font-weight-bold">{{ optional($leave->start_date)->format('d M Y') }} - {{ optional($leave->end_date)->format('d M Y') }}</span></td>
                                    <td class="text-center"><span class="text-secondary text-xs font-weight-bold">{{ $leave->duration }} day{{ $leave->duration === 1 ? '' : 's' }}</span></td>
                                    <td class="text-center"><span class="text-secondary text-xs font-weight-bold">{{ \Illuminate\Support\Str::limit($leave->reason, 60) }}</span></td>
                                    <td class="text-center">
                                        <span class="badge badge-sm {{ $leave->status === 'approved' ? 'bg-gradient-success' : ($leave->status === 'rejected' ? 'bg-gradient-danger' : 'bg-gradient-warning') }}">
                                            {{ ucfirst($leave->status) }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-sm text-secondary">No leave requests found for this employee.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="px-4 pt-3">{{ $leaves->links() }}</div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('dashboard')
<script>
    window.addEventListener('load', function () {
        var chartElement = document.getElementById('employee-leave-sparkline');
        var sparklineHasData = @json($employeeMonthlySparkline['has_data']);

        if (!chartElement || !sparklineHasData) {
            return;
        }

        var chartContext = chartElement.getContext('2d');
        var gradientFill = chartContext.createLinearGradient(0, 120, 0, 0);

        gradientFill.addColorStop(1, 'rgba(255,255,255,0.04)');
        gradientFill.addColorStop(0.2, 'rgba(255,255,255,0.14)');
        gradientFill.addColorStop(0, 'rgba(255,255,255,0.22)');

        new Chart(chartContext, {
            type: 'line',
            data: {
                labels: @json($employeeMonthlySparkline['labels']),
                datasets: [{
                    label: 'Leave Days',
                    data: @json($employeeMonthlySparkline['days']),
                    tension: 0.35,
                    borderWidth: 2,
                    pointRadius: 0,
                    pointHoverRadius: 4,
                    fill: true,
                    borderColor: '#ffffff',
                    backgroundColor: gradientFill,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false,
                    },
                    tooltip: {
                        intersect: false,
                        mode: 'index',
                        callbacks: {
                            label: function (context) {
                                return context.parsed.y + ' {{ $employeeMonthlySparkline['tooltip_suffix'] }}';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false,
                            drawBorder: false,
                        },
                        ticks: {
                            color: 'rgba(255,255,255,0.75)',
                            maxRotation: 0,
                            autoSkip: true,
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(255,255,255,0.08)',
                            drawBorder: false,
                        },
                        ticks: {
                            color: 'rgba(255,255,255,0.75)',
                            precision: 0,
                        }
                    }
                }
            }
        });
    });
</script>
@endpush