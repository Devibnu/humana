<table>
    <thead>
        <tr>
            <th>Karyawan</th>
            <th>Pengaju</th>
            <th>Approver</th>
            <th>Waktu Mulai</th>
            <th>Waktu Selesai</th>
            <th>Durasi (jam)</th>
            <th>Status</th>
            <th>Alasan</th>
        </tr>
    </thead>
    <tbody>
        @foreach($lemburs as $lembur)
            <tr>
                <td>{{ $lembur->employee?->name ?? '-' }}</td>
                <td>{{ $lembur->pengaju }}</td>
                <td>{{ $lembur->approver?->name ?? '-' }}</td>
                <td>{{ $lembur->waktu_mulai }}</td>
                <td>{{ $lembur->waktu_selesai }}</td>
                <td>{{ $lembur->durasi_jam }}</td>
                <td>{{ $lembur->status }}</td>
                <td>{{ $lembur->alasan }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
