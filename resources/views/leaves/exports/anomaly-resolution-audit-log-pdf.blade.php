<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Audit Log Resolusi Anomali Cuti</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #1f2937;
            font-size: 11px;
            line-height: 1.45;
        }

        h1, h2 {
            margin-bottom: 6px;
        }

        .muted {
            color: #6b7280;
        }

        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 999px;
            color: #ffffff;
            font-size: 10px;
            margin-right: 6px;
        }

        .badge-success { background: #16a34a; }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 14px 0 20px;
        }

        th, td {
            border: 1px solid #d1d5db;
            padding: 8px 10px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background: #111827;
            color: #ffffff;
        }

        tbody tr:nth-child(odd) {
            background: #f9fafb;
        }
    </style>
</head>
<body>
    <h1>Audit Log Resolusi Anomali Cuti</h1>
    <p class="muted">Tenant: {{ $tenant?->name ?? 'Tenant Tidak Tersedia' }}</p>
    <p class="muted">Periode filter: {{ $monthOptions[$selectedMonth] ?? '-' }} {{ $selectedYear }}</p>
    <p class="muted">Dibuat pada {{ $generatedAt->format('d M Y H:i') }}</p>

    <div>
        <span class="badge badge-success">Total Log: {{ $summary['total'] ?? 0 }}</span>
    </div>

    <h2>Section Detail</h2>
    <table>
        <thead>
            <tr>
                <th>Employee</th>
                <th>Jenis Anomali</th>
                <th>Deskripsi</th>
                <th>Periode</th>
                <th>Manager</th>
                <th>Tindakan</th>
                <th>Catatan</th>
                <th>Timestamp</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($logs as $row)
                <tr>
                    <td>{{ $row['employee'] }}</td>
                    <td>{{ $row['jenis_anomali'] }}</td>
                    <td>{{ $row['deskripsi'] }}</td>
                    <td>{{ $row['periode'] }}</td>
                    <td>{{ $row['manager'] }}</td>
                    <td>{{ $row['tindakan'] }}</td>
                    <td>{{ $row['catatan'] }}</td>
                    <td>{{ $row['timestamp'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>