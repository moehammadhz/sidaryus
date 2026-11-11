@extends('layouts.admin')

@section('title', 'Kelola Jadwal Pendadaran')
@section('crumb', 'Jadwal Pendadaran')
@section('page_title', 'Kelola Jadwal Pendadaran')

@push('styles')
<style>
    .panel { background: #fff; border: 1px solid var(--line); border-radius: 16px; padding: 24px; }
    .alert { margin-bottom: 16px; padding: 12px 16px; border-radius: 12px; background: #dcfce7; color: #166534; font-size: 14px; font-weight: 600; }
    .alert--error { background: #fee2e2; color: #991b1b; }

    .chips { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 18px; }
    .chip { display: inline-flex; align-items: center; gap: 8px; padding: 6px 12px; border-radius: 999px; font-size: 12.5px; font-weight: 600; background: #f1f4f9; color: #374151; }
    .chip b { font-size: 14px; }
    .chip--pending   { background: #fef3c7; color: #92400e; }
    .chip--disetujui { background: #dcfce7; color: #166534; }
    .chip--ditolak   { background: #fee2e2; color: #991b1b; }

    .toolbar { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
    .btn--excel { background: #15803d; color: #fff; }
    .btn--excel:hover { background: #166534; }
    .search { display: flex; gap: 8px; align-items: center; }
    .search__box {
        display: flex; align-items: center; gap: 8px; height: 40px; width: 280px; max-width: 100%;
        padding: 0 12px; border: 1.5px solid #d5dbe5; border-radius: 10px; background: #f6f8fb; color: #9aa1ad;
    }
    .search__box:focus-within { border-color: var(--primary); background: #fff; box-shadow: 0 0 0 3px rgba(43,114,213,.15); }
    .search__box input { flex: 1; min-width: 0; border: 0; outline: 0; background: transparent; font: 500 14px var(--font); color: var(--ink); }

    .table-wrap { margin-top: 18px; overflow-x: auto; border: 1px solid var(--line); border-radius: 14px; }
    table { width: 100%; min-width: 980px; border-collapse: collapse; font-size: 13.5px; }
    thead th { background: #f6f8fb; text-align: left; padding: 0; white-space: nowrap; }
    thead a {
        display: flex; align-items: center; justify-content: space-between; gap: 8px;
        height: 46px; padding: 0 14px; text-decoration: none; color: #374151;
        font-size: 12px; font-weight: 700; letter-spacing: .03em; text-transform: uppercase;
    }
    thead a:hover { color: var(--primary); }
    thead a svg { opacity: .35; }
    thead a.is-sorted { color: var(--primary); }
    thead a.is-sorted svg { opacity: 1; }
    thead .th-plain { display: block; height: 46px; line-height: 46px; padding: 0 14px; font-size: 12px; font-weight: 700; letter-spacing: .03em; text-transform: uppercase; color: #374151; }
    tbody td { padding: 12px 14px; border-top: 1px solid var(--line); vertical-align: middle; color: #1f2937; line-height: 1.4; }
    tbody tr:hover td { background: #fafcff; }
    .nim { font-weight: 700; }
    .judul { min-width: 220px; font-style: italic; }
    .badge { display: inline-block; padding: 4px 10px; border-radius: 999px; font-size: 11px; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; }
    .badge--pending   { background: #fef3c7; color: #92400e; }
    .badge--disetujui { background: #dcfce7; color: #166534; }
    .badge--ditolak   { background: #fee2e2; color: #991b1b; }
    .btn--sm { height: 34px; padding: 0 14px; font-size: 12.5px; white-space: nowrap; }
    .aksi { display: flex; gap: 8px; }
    .aksi form { display: inline; }
    .aksi .btn { height: 34px; padding: 0 14px; font-size: 12.5px; }
    .done { font-size: 12.5px; color: var(--muted); }
    .empty { padding: 40px 16px; text-align: center; color: var(--muted); font-size: 14px; }

    .footer { margin-top: 16px; display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; font-size: 13.5px; color: #374151; }
    .pager { display: flex; gap: 6px; align-items: center; }
    .pager a, .pager span {
        min-width: 38px; height: 38px; padding: 0 10px; display: grid; place-items: center;
        border-radius: 10px; border: 1px solid var(--line); background: #fff;
        font-size: 13.5px; font-weight: 600; text-decoration: none; color: #374151;
    }
    .pager a:hover { border-color: var(--primary); color: var(--primary); }
    .pager .is-current { background: var(--primary); border-color: var(--primary); color: #fff; }
    .pager .is-disabled { opacity: .4; }

    .tabs { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 16px; }
    .tab { display: inline-flex; align-items: center; gap: 8px; height: 36px; padding: 0 14px; border-radius: 999px; border: 1px solid var(--line); background: #fff; color: #374151; font-size: 13px; font-weight: 600; text-decoration: none; }
    .tab b { min-width: 22px; padding: 1px 7px; border-radius: 999px; background: #f1f4f9; font-size: 12px; text-align: center; }
    .tab:hover { border-color: var(--primary); color: var(--primary); }
    .tab.is-active { background: var(--navy); border-color: var(--navy); color: #fff; }
    .tab.is-active b { background: rgba(255,255,255,.2); color: #fff; }
    .tahap { display: inline-block; padding: 4px 10px; border-radius: 999px; font-size: 11.5px; font-weight: 700; white-space: nowrap; }
    .tahap--pending        { background: #fef3c7; color: #92400e; }
    .tahap--menunggu_bayar { background: #fff7ed; color: #9a3412; border: 1px dashed #fdba74; }
    .tahap--cek_bayar      { background: #fef9c3; color: #854d0e; }
    .tahap--bayar_ditolak  { background: #fee2e2; color: #991b1b; border: 1px dashed #fca5a5; }
    .tahap--ditolak        { background: #fee2e2; color: #991b1b; }
    .tahap--perlu_undangan { background: #e0f2fe; color: #0369a1; }
    .tahap--menunggu_ujian { background: #f1f4f9; color: #4b5563; }
    .tahap--perlu_nilai    { background: #ede9fe; color: #5b21b6; }
    .tahap--lulus          { background: #dcfce7; color: #166534; }
    .tahap--tidak_lulus    { background: #fee2e2; color: #991b1b; }
    .nilai-kecil { display: block; margin-top: 3px; font-size: 12px; color: var(--muted); }

    @media (max-width: 720px) {
        .panel { padding: 16px; }
        .search { width: 100%; }
        .search__box { flex: 1; width: auto; min-width: 0; }
        .toolbar { flex-direction: column; align-items: stretch; }
    }
</style>
@endpush

@section('content')
@php
    $kolom = [
        ['id', 'No'], ['nim', 'NIM'], ['nama', 'Nama Mahasiswa'], ['judul', 'Judul Skripsi'],
        ['tanggal_ujian', 'Tanggal Ujian'], ['jam_ujian', 'Jam'], ['status', 'Status'],
    ];
    $labelStatus = ['pending' => 'Pending', 'disetujui' => 'Disetujui', 'ditolak' => 'Ditolak'];
    $labelTahap = [
        'menunggu_bayar' => 'Menunggu bukti bayar', 'cek_bayar' => 'Periksa bukti bayar', 'bayar_ditolak' => 'Bukti bayar ditolak', 'pending' => 'Verifikasi jadwal', 'ditolak' => 'Ditolak', 'perlu_undangan' => 'Perlu undangan',
        'menunggu_ujian' => 'Menunggu ujian', 'perlu_nilai' => 'Perlu nilai', 'lulus' => 'Lulus', 'tidak_lulus' => 'Tidak Lulus',
    ];
    $halaman = $jadwal->currentPage();
    $akhir   = $jadwal->lastPage();
    $mulai   = max(1, $halaman - 2);
    $selesai = min($akhir, $halaman + 2);
@endphp

    @if (session('status'))
        <div class="alert" role="status">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert--error" role="alert">{{ session('error') }}</div>
    @endif

    <section class="panel">

        <nav class="tabs" aria-label="Tahap jadwal">
            @foreach (\App\Http\Controllers\Admin\JadwalController::TAB as $kunci => $label)
                <a href="{{ route('admin.jadwal.index', array_filter(['tab' => $kunci === 'semua' ? null : $kunci, 'q' => $q])) }}"
                   @class(['tab', 'is-active' => $tab === $kunci]) @if ($tab === $kunci) aria-current="page" @endif>
                    {{ $label }} <b>{{ $hitung[$kunci] }}</b>
                </a>
            @endforeach
        </nav>

        <div class="toolbar">
            <a class="btn btn--excel" href="{{ route('admin.jadwal.ekspor', array_filter(['q' => $q, 'sort' => $sort, 'dir' => $dir, 'tab' => $tab === 'semua' ? null : $tab])) }}">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 4v11M7 11l5 5 5-5M5 20h14"/></svg>
                Ekspor ke Excel
            </a>

            <form class="search" method="GET" action="{{ route('admin.jadwal.index') }}" role="search">
                <input type="hidden" name="sort" value="{{ $sort }}">
                <input type="hidden" name="dir" value="{{ $dir }}">
                @if ($tab !== 'semua') <input type="hidden" name="tab" value="{{ $tab }}"> @endif
                <label class="search__box">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
                    <input type="search" name="q" value="{{ $q }}" placeholder="Cari NIM, nama, judul, dosen" aria-label="Pencarian">
                </label>
                <button class="btn btn--primary" type="submit">Cari</button>
                @if ($q !== '')
                    <a class="btn btn--ghost" href="{{ route('admin.jadwal.index') }}">Reset</a>
                @endif
            </form>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        @foreach ($kolom as [$kunci, $judulKolom])
                            @php
                                $aktif = $sort === $kunci;
                                $urlUrut = request()->fullUrlWithQuery([
                                    'sort' => $kunci,
                                    'dir'  => ($aktif && $dir === 'asc') ? 'desc' : 'asc',
                                    'page' => null,
                                ]);
                            @endphp
                            <th scope="col" aria-sort="{{ $aktif ? ($dir === 'asc' ? 'ascending' : 'descending') : 'none' }}">
                                <a href="{{ $urlUrut }}" @class(['is-sorted' => $aktif])>
                                    {{ $judulKolom }}
                                    <x-icon :name="$aktif && $dir === 'desc' ? 'arrow-down' : 'arrow-up'" :size="14" />
                                </a>
                            </th>
                        @endforeach
                        <th scope="col"><span class="th-plain">Tahap</span></th>
                        <th scope="col"><span class="th-plain">Aksi</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($jadwal as $i => $j)
                        <tr>
                            <td>{{ $jadwal->firstItem() + $i }}</td>
                            <td class="nim">{{ $j->nim }}</td>
                            <td>{{ $j->nama }}</td>
                            <td class="judul">{{ $j->judul }}</td>
                            <td>{{ $j->tanggalIndo() }}</td>
                            <td>{{ $j->jamWib() }}</td>
                            <td><span class="badge badge--{{ $j->status }}">{{ $labelStatus[$j->status] ?? $j->status }}</span></td>
                            <td>
                                @php $t = $j->tahap(); @endphp
                                <span class="tahap tahap--{{ $t }}">{{ $labelTahap[$t] ?? $t }}</span>
                                @if ($j->hasil)
                                    <span class="nilai-kecil">Nilai {{ \App\Models\JadwalPendadaran::formatNilai($j->nilai_akhir) }} ({{ $j->hurufAkhir() }})</span>
                                @endif
                            </td>
                            <td>
                                <a class="btn {{ in_array($t, ['pending', 'perlu_undangan', 'perlu_nilai'], true) ? 'btn--primary' : 'btn--ghost' }} btn--sm" href="{{ route('admin.jadwal.show', $j) }}">
                                    {{ ['pending' => 'Verifikasi', 'perlu_undangan' => 'Unggah undangan', 'perlu_nilai' => 'Input nilai'][$t] ?? 'Detail' }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="empty">
                                @if ($q !== '')
                                    Tidak ada jadwal yang cocok dengan "{{ $q }}".
                                @else
                                    Tidak ada jadwal pada tab ini.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="footer">
            <div>
                @if ($jadwal->total() > 0)
                    Data {{ $jadwal->firstItem() }} sampai {{ $jadwal->lastItem() }} dari {{ $jadwal->total() }} data
                @else
                    Tidak ada data
                @endif
            </div>

            @if ($jadwal->hasPages())
                <nav class="pager" aria-label="Halaman">
                    @if ($jadwal->onFirstPage())
                        <span class="is-disabled" aria-hidden="true">&lt;&lt;</span>
                    @else
                        <a href="{{ $jadwal->previousPageUrl() }}" aria-label="Halaman sebelumnya">&lt;&lt;</a>
                    @endif

                    @for ($h = $mulai; $h <= $selesai; $h++)
                        @if ($h === $halaman)
                            <span class="is-current" aria-current="page">{{ $h }}</span>
                        @else
                            <a href="{{ $jadwal->url($h) }}">{{ $h }}</a>
                        @endif
                    @endfor

                    @if ($jadwal->hasMorePages())
                        <a href="{{ $jadwal->nextPageUrl() }}" aria-label="Halaman berikutnya">&gt;&gt;</a>
                    @else
                        <span class="is-disabled" aria-hidden="true">&gt;&gt;</span>
                    @endif
                </nav>
            @endif
        </div>
    </section>
@endsection