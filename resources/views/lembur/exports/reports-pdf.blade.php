<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Lembur</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111827; }
        h1 { font-size: 18px; margin-bottom: 6px; }
        p { margin: 0 0 6px; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { border: 1px solid #d1d5db; padding: 8px; vertical-align: top; }
        th { background: #f3f4f6; text-align: left; }
        .text-center { text-align: center; }
        .summary { margin-top: 8px; }
    </style>
</head>
<body>
    <h1>{{ $pdfHeading['title'] ?? 'Laporan Lembur' }}</h1>
    <p><strong>{{ $pdfHeading['subtitle'] ?? '' }}</strong></p>
    <p>Pencarian: {{ $filters['search'] !== '' ? $filters['search'] : '-' }}</p>
    <p>Periode: {{ $filters['start_date'] ?: '-' }} sampai {{ $filters['end_date'] ?: '-' }}</p>
    <p>Status: {{ $filters['status'] ?: 'Semua Status' }} | Pengaju: {{ $filters['pengaju'] ?: 'Semua Pengaju' }}</p>
    <p>Sorting: {{ match($filters['sort_by'] ?? 'waktu_mulai') {
        'employee_name' => 'Karyawan',
        'pengaju' => 'Pengaju',
        'approver_name' => 'Approver',
        'durasi_jam' => 'Durasi',
        'status' => 'Status',
        'alasan' => 'Alasan',
        default => 'Tanggal Lembur',
    } }} ({{ strtoupper($filters['sort_order'] ?? 'desc') }})</p>
    <p class="summary">
        Total Pengajuan: {{ $summary[0]['value'] }} |
        Total Jam: {{ $summary[1]['value'] }} |
        Disetujui: {{ $summary[2]['value'] }} |
        Approval Rate: {{ $summary[3]['value'] }}
    </p>

    <table>
        <thead>
            <tr>
                <th>Karyawan</th>
                <th>Pengaju</th>
                <th>Approver</th>
                <th>Mulai</th>
                <th>Selesai</th>
                <th>Durasi</th>
                <th>Status</th>
                <th>Alasan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($reports as $report)
                <tr>
                    <td>{{ $report->employee?->name ?? '-' }}</td>
                    <td>{{ ucfirst($report->pengaju ?? '-') }}</td>
                    <td>{{ $report->approver?->name ?? 'Belum diputuskan' }}</td>
                    <td>{{ $report->waktu_mulai?->format('Y-m-d H:i') ?? '-' }}</td>
                    <td>{{ $report->waktu_selesai?->format('Y-m-d H:i') ?? '-' }}</td>
                    <td class="text-center">{{ $report->durasi_jam !== null ? number_format((float) $report->durasi_jam, 2).' jam' : '-' }}</td>
                    <td class="text-center">{{ ucfirst($report->status) }}</td>
                    <td>{{ $report->alasan ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">Tidak ada data lembur untuk filter ini</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>