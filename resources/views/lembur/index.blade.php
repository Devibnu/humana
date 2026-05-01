@extends('layouts.user_type.auth')

@section('content')
@php($isApprovalPage = ($pageMode ?? 'submission') === 'approval')
@php($canProcessApproval = auth()->user()?->isManager() || auth()->user()?->isAdminHr())
@php($approvalBacklogFilter = $listingFilters['backlog_filter'] ?? null)
@php($pageContextBadges = collect([
    $isApprovalPage ? 'Mode: Approval Queue' : 'Mode: Riwayat Pengajuan',
    $isApprovalPage ? 'Hanya status pending' : 'Scope sesuai akun aktif',
    $isApprovalPage ? 'Butuh keputusan approver' : 'Pantau status pengajuan',
    $isApprovalPage && $approvalBacklogFilter === 'over_7_days' ? 'Filter: Backlog > 7 Hari' : null,
])->filter()->values())

<div class="row">
    <div class="col-12">
            <x-flash-messages />
            <div class="card mx-4 mb-4 shadow-xs">
                <div class="card-header pb-0">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                        <div>
                            <h5 class="mb-1">{{ $isApprovalPage ? 'Antrean Persetujuan Lembur' : 'Daftar Pengajuan Lembur' }}</h5>
                            <p class="text-sm text-secondary mb-0">{{ $isApprovalPage ? 'Tinjau pengajuan lembur yang masih pending dan ambil keputusan approval tanpa tercampur riwayat selesai.' : 'Lihat riwayat pengajuan lembur yang Anda ajukan atau terkait dengan akun Anda.' }}</p>
                        </div>
                        <div class="d-flex gap-2 flex-wrap align-items-center">
                            <span class="badge {{ $isApprovalPage ? 'bg-gradient-warning text-dark' : 'bg-gradient-info' }}">{{ $isApprovalPage ? 'Butuh Tindakan' : 'Pengajuan Aktif' }}</span>
                            @foreach ($pageContextBadges as $pageContextBadge)
                                <span class="badge bg-gradient-light text-dark">{{ $pageContextBadge }}</span>
                            @endforeach
                            @unless($isApprovalPage)
                                <a href="{{ route('lembur.create') }}" class="btn bg-gradient-primary btn-sm mb-0">Tambah Lembur</a>
                            @endunless
                            @if(auth()->user()?->hasMenuAccess('lembur.reports'))
                                <a href="{{ route('lembur.reports') }}" class="btn btn-outline-success btn-sm mb-0">Buka Laporan</a>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="card-body px-0 pt-0 pb-2">
                    <div class="px-4 pt-4">
                        <div class="row g-3 mb-4">
                            <div class="col-xl-4 col-md-6">
                                <div class="card border shadow-xs h-100">
                                    <div class="card-body py-3">
                                        <p class="text-xs text-uppercase text-secondary font-weight-bold mb-1">{{ $summary['primary']['label'] }}</p>
                                        <h5 class="mb-0 text-{{ $summary['primary']['tone'] }}">{{ $summary['primary']['value'] }}</h5>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-4 col-md-6">
                                <div class="card border shadow-xs h-100">
                                    <div class="card-body py-3">
                                        <p class="text-xs text-uppercase text-secondary font-weight-bold mb-1">{{ $summary['secondary']['label'] }}</p>
                                        <h5 class="mb-0 text-{{ $summary['secondary']['tone'] }}">{{ $summary['secondary']['value'] }}</h5>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-4 col-md-12">
                                <div class="card border shadow-xs h-100">
                                    <div class="card-body py-3">
                                        <p class="text-xs text-uppercase text-secondary font-weight-bold mb-1">{{ $summary['tertiary']['label'] }}</p>
                                        <h5 class="mb-0 text-{{ $summary['tertiary']['tone'] }}">{{ $summary['tertiary']['value'] }}</h5>
                                    </div>
                                </div>
                            </div>
                            @if ($isApprovalPage && isset($summary['quaternary']))
                                <div class="col-xl-3 col-md-6">
                                    <div class="card border shadow-xs h-100 border-danger">
                                        <div class="card-body py-3">
                                            <p class="text-xs text-uppercase text-secondary font-weight-bold mb-1">{{ $summary['quaternary']['label'] }}</p>
                                            <h5 class="mb-0 text-{{ $summary['quaternary']['tone'] }}">{{ $summary['quaternary']['value'] }}</h5>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div class="border border-radius-xl p-3 mb-4 {{ $isApprovalPage ? 'bg-gray-100' : 'bg-gray-100' }}">
                            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                                <div>
                                    <p class="text-sm font-weight-bold mb-1">{{ $isApprovalPage ? 'Fokus halaman approval' : 'Fokus halaman pengajuan' }}</p>
                                    <p class="text-sm text-secondary mb-0">{{ $isApprovalPage ? 'Halaman ini hanya menampilkan item pending yang perlu diputuskan. Riwayat pengajuan yang sudah selesai tidak dicampur di sini.' : 'Halaman ini hanya menampilkan pengajuan yang relevan dengan akun Anda, sehingga tidak bercampur dengan antrean approval tenant lain.' }}</p>
                                    @if ($isApprovalPage)
                                        <div class="d-flex flex-wrap gap-2 mt-3">
                                            <a href="{{ route('lembur.approval') }}" class="btn btn-sm {{ $approvalBacklogFilter === null ? 'bg-gradient-dark text-white' : 'btn-outline-dark' }} mb-0">Semua Pending</a>
                                            <a href="{{ route('lembur.approval', ['backlog_filter' => 'over_7_days']) }}" class="btn btn-sm {{ $approvalBacklogFilter === 'over_7_days' ? 'bg-gradient-danger text-white' : 'btn-outline-danger' }} mb-0">Backlog &gt; 7 Hari</a>
                                        </div>
                                    @endif
                                </div>
                                @if ($isApprovalPage && ! empty($approvalBacklogLink))
                                    <a href="{{ $approvalBacklogLink }}" class="btn btn-outline-danger btn-sm mb-0">Lihat Backlog > 3 Hari</a>
                                @endif
                            </div>
                        </div>

                        @if (! empty($reportSnapshot))
                            <div class="border border-radius-xl p-3 mb-4 bg-white">
                                <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
                                    <div>
                                        <p class="text-sm font-weight-bold mb-1">Snapshot Laporan Bulan Ini</p>
                                        <p class="text-sm text-secondary mb-0">{{ $isApprovalPage ? 'Ringkasan cepat antrean approval bulan ini dengan filter pending aktif.' : 'Ringkasan cepat dari dashboard laporan lembur untuk periode berjalan.' }}</p>
                                    </div>
                                    <a href="{{ $reportSnapshotLink }}" class="btn btn-outline-dark btn-sm mb-0">Lihat Dashboard</a>
                                </div>
                                <div class="row g-3">
                                    @foreach ($reportSnapshot as $snapshotItem)
                                        <div class="col-md-4">
                                            <div class="card border shadow-xs h-100">
                                                <div class="card-body py-3">
                                                    <p class="text-xs text-uppercase text-secondary font-weight-bold mb-1">{{ $snapshotItem['label'] }}</p>
                                                    <h6 class="mb-0 text-{{ $snapshotItem['tone'] }}">{{ $snapshotItem['value'] }}</h6>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>

                    @if ($lemburs->isEmpty())
                        <div class="text-center py-5">
                            <i class="fas {{ $isApprovalPage ? 'fa-user-check' : 'fa-file-signature' }} fa-3x text-secondary mb-3"></i>
                            <p class="text-secondary mb-1">{{ $isApprovalPage ? 'Tidak ada pengajuan lembur yang menunggu persetujuan.' : 'Belum ada pengajuan lembur yang relevan untuk akun ini.' }}</p>
                            <p class="text-sm text-secondary mb-0">{{ $isApprovalPage ? 'Saat ada pengajuan baru berstatus pending, antreannya akan muncul di halaman ini.' : 'Pengajuan baru akan muncul di daftar ini setelah berhasil dibuat.' }}</p>
                            @unless($isApprovalPage)
                                <div class="mt-3">
                                    <a href="{{ route('lembur.create') }}" class="btn bg-gradient-primary btn-sm mb-0">Ajukan Lembur</a>
                                </div>
                            @endunless
                        </div>
                    @else
                        <div class="table-responsive p-0">
                            <table class="table align-items-center mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-4 text-start">Karyawan</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-start">{{ $isApprovalPage ? 'Dibuat Oleh' : 'Pengaju' }}</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-start">Mulai</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-start">Selesai</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">Durasi</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">Status</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-start">Alasan</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($lemburs as $lembur)
                                        <tr class="{{ $isApprovalPage && ($lembur->approval_age_tone ?? null) === 'danger' ? 'table-danger' : ($isApprovalPage && ($lembur->approval_age_tone ?? null) === 'warning' ? 'table-warning' : '') }}">
                                            <td class="ps-4 text-start">
                                                <div>
                                                    <h6 class="mb-0 text-sm">{{ $lembur->employee?->name ?? '-' }}</h6>
                                                    <p class="text-xs text-secondary mb-0 mt-1">{{ $lembur->employee?->employee_code ?? 'Tanpa kode karyawan' }}</p>
                                                </div>
                                            </td>
                                            <td class="text-start">
                                                <span class="text-secondary text-sm">
                                                    @if($isApprovalPage)
                                                        {{ $lembur->submitter?->name ?? ucfirst($lembur->pengaju) }}
                                                    @else
                                                        {{ ucfirst($lembur->pengaju) }}
                                                    @endif
                                                </span>
                                            </td>
                                            <td class="text-start">
                                                <span class="text-secondary text-sm">{{ $lembur->waktu_mulai?->format('Y-m-d H:i') ?? '-' }}</span>
                                                @if($isApprovalPage && ! is_null($lembur->approval_age_days))
                                                    <p class="text-xs {{ ($lembur->approval_age_tone ?? null) === 'danger' ? 'text-danger' : (($lembur->approval_age_tone ?? null) === 'warning' ? 'text-warning' : 'text-secondary') }} mb-0 mt-1">
                                                        Pending {{ $lembur->approval_age_days }} hari
                                                    </p>
                                                @endif
                                            </td>
                                            <td class="text-start"><span class="text-secondary text-sm">{{ $lembur->waktu_selesai?->format('Y-m-d H:i') ?? '-' }}</span></td>
                                            <td class="text-center"><span class="text-sm font-weight-bold">{{ $lembur->durasi_jam !== null ? number_format((float) $lembur->durasi_jam, 2) . ' jam' : '-' }}</span></td>
                                            <td class="text-center">
                                                <span class="badge {{ $lembur->status === 'disetujui' ? 'bg-gradient-success' : ($lembur->status === 'ditolak' ? 'bg-gradient-danger' : 'bg-gradient-warning text-dark') }}">
                                                    {{ ucfirst($lembur->status) }}
                                                </span>
                                            </td>
                                            <td class="text-start"><span class="text-secondary text-sm">{{ $lembur->alasan ?? '-' }}</span></td>
                                            <td class="text-center">
                                                @if($isApprovalPage && $lembur->status === 'pending' && $canProcessApproval)
                                                    <div class="d-flex align-items-center justify-content-center gap-3 flex-wrap">
                                                        <form method="POST" action="{{ route('lembur.approve', $lembur) }}">
                                                            @csrf
                                                            <button class="border-0 bg-transparent p-0" type="submit" title="Setujui">
                                                                <i class="fas fa-check text-success text-sm"></i>
                                                            </button>
                                                        </form>
                                                        <form method="POST" action="{{ route('lembur.reject', $lembur) }}">
                                                            @csrf
                                                            <button class="border-0 bg-transparent p-0" type="submit" title="Tolak">
                                                                <i class="fas fa-times text-danger text-sm"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                @else
                                                    <span class="text-xs text-secondary">-</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="px-4 pt-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <p class="text-sm text-secondary mb-0">Menampilkan {{ $lemburs->count() }} dari total {{ $lemburs->total() }} {{ $isApprovalPage ? 'pengajuan pending' : 'pengajuan lembur' }}.</p>
                            {{ $lemburs->links() }}
                        </div>
                    @endif
                </div>
            </div>
    </div>
</div>
@endsection
