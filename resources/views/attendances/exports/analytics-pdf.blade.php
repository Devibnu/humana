<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Analitik Absensi</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #1f2937;
            font-size: 12px;
            line-height: 1.5;
        }

        .page-break {
            page-break-after: always;
        }

        .cover {
            padding-top: 180px;
            text-align: center;
        }

        .cover h1 {
            font-size: 28px;
            margin-bottom: 8px;
        }

        .cover p {
            color: #6b7280;
            margin: 0;
        }

        .badge-row {
            margin: 18px 0;
        }

        .badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 999px;
            color: #ffffff;
            font-size: 11px;
            margin-right: 6px;
        }

        .section-title {
            font-size: 18px;
            margin: 18px 0 10px;
        }

        .summary-grid {
            width: 100%;
            margin-bottom: 18px;
        }

        .summary-card {
            display: inline-block;
            width: 31%;
            vertical-align: top;
            margin-right: 2%;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 12px;
            box-sizing: border-box;
        }

        .summary-card:last-child {
            margin-right: 0;
        }

        .summary-card h3 {
            margin: 0 0 6px;
            font-size: 14px;
        }

        .summary-card .value {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 4px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
        }

        th, td {
            border: 1px solid #d1d5db;
            padding: 8px 10px;
            text-align: left;
        }

        th {
            background: #111827;
            color: #ffffff;
            font-weight: bold;
        }

        tbody tr:nth-child(odd) {
            background: #f9fafb;
        }

        .chart-block {
            text-align: center;
            margin: 12px 0 24px;
        }

        .muted {
            color: #6b7280;
        }

        .status-present { background: #82d616; }
        .status-leave { background: #fbcf33; color: #111827; }
        .status-sick { background: #17c1e8; }
        .status-absent { background: #ea0606; }
    </style>
</head>
<body>
    <div class="cover page-break">
        <h1>Laporan Analitik Absensi</h1>
        <p>Periode analitik: {{ $selectedPeriodLabel }}</p>
        <p>Dibuat pada {{ $generatedAt->format('d M Y H:i') }}</p>
    </div>

    <h2 class="section-title">Ringkasan Umum</h2>
    <div class="badge-row">
        <span class="badge status-present">Hadir: {{ $monthSummary['status_counts']['present'] }}</span>
        <span class="badge status-leave">Izin: {{ $monthSummary['status_counts']['leave'] }}</span>
        <span class="badge status-sick">Sakit: {{ $monthSummary['status_counts']['sick'] }}</span>
        <span class="badge status-absent">Alpha: {{ $monthSummary['status_counts']['absent'] }}</span>
    </div>

    <div class="summary-grid">
        <div class="summary-card">
            <h3>Total Kehadiran Bulan Ini</h3>
            <div class="value">{{ $monthSummary['total_attendances'] }}</div>
            <div class="muted">{{ $monthSummary['total_work_hours_label'] }}</div>
        </div>
        <div class="summary-card">
            <h3>Total Kehadiran Tahun Ini</h3>
            <div class="value">{{ $yearSummary['total_attendances'] }}</div>
            <div class="muted">{{ $yearSummary['total_work_hours_label'] }}</div>
        </div>
        <div class="summary-card">
            <h3>Periode Detail</h3>
            <div class="value" style="font-size: 18px;">{{ $selectedPeriodLabel }}</div>
            <div class="muted">Status Hadir sudah termasuk Terlambat</div>
        </div>
    </div>

    <h2 class="section-title">Section 1: Summary Bulanan</h2>
    <p class="muted">Tabel dan grafik tren status absensi untuk 12 bulan terakhir.</p>
    <table>
        <thead>
            <tr>
                <th>Tahun</th>
                <th>Bulan</th>
                <th>Hadir</th>
                <th>Izin</th>
                <th>Sakit</th>
                <th>Alpha</th>
                <th>Total Jam Kerja</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($monthlySummaryRows as $row)
                <tr>
                    <td>{{ $row['year'] }}</td>
                    <td>{{ $row['month_label'] }}</td>
                    <td>{{ $row['present'] }}</td>
                    <td>{{ $row['leave'] }}</td>
                    <td>{{ $row['sick'] }}</td>
                    <td>{{ $row['absent'] }}</td>
                    <td>{{ $row['total_work_hours_label'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <div class="chart-block">{!! $monthlyTrendSvg !!}</div>

    <div class="page-break"></div>

    <h2 class="section-title">Section 2: Summary Tahunan</h2>
    <p class="muted">Tabel dan grafik distribusi status absensi untuk 5 tahun terakhir.</p>
    <table>
        <thead>
            <tr>
                <th>Tahun</th>
                <th>Hadir</th>
                <th>Izin</th>
                <th>Sakit</th>
                <th>Alpha</th>
                <th>Total Jam Kerja</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($yearlySummaryRows as $row)
                <tr>
                    <td>{{ $row['year'] }}</td>
                    <td>{{ $row['present'] }}</td>
                    <td>{{ $row['leave'] }}</td>
                    <td>{{ $row['sick'] }}</td>
                    <td>{{ $row['absent'] }}</td>
                    <td>{{ $row['total_work_hours_label'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <div class="chart-block">{!! $yearlyDistributionSvg !!}</div>

    <h2 class="section-title">Section 3: Pie Chart Distribusi Status Bulan Berjalan</h2>
    <p class="muted">Proporsi status absensi untuk periode {{ $selectedPeriodLabel }}.</p>
    <div class="chart-block">{!! $statusDistributionSvg !!}</div>
</body>
</html>