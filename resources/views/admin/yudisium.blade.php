@extends('layouts.admin')

@php use App\Models\JadwalPendadaran as J; @endphp

@section('title', 'Data Yudisium')
@section('crumb', 'Data Yudisium')
@section('page_title', 'Data Yudisium')

@section('actions')
    <a class="btn btn--ghost" href="{{ route('admin.periode.index') }}">Periode Yudisium</a>
@endsection

@push('styles')
<style>
    .alert { margin-bottom: 16px; padding: 12px 16px; border-radius: 12px; background: #dcfce7; color: #166534; font-size: 14px; font-weight: 600; }
    .alert--error { background: #fee2e2; color: #991b1b; }
    .note { margin-bottom: 16px; padding: 12px 16px; border-radius: 12px; background: #e3eefc; color: var(--navy); font-size: 13.5px; }
    .panel { background: #fff; border: 1px solid var(--line); border-radius: 16px; padding: 22px; }
    .toolbar { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; justify-content: space-between; }
    .chips { display: flex; flex-wrap: wrap; gap: 8px; }
    .chip { padding: 6px 12px; border-radius: 999px; background: #f1f4f9; font-size: 12.5px; font-weight: 600; color: #374151; }
    .chip b { font-size: 14px; margin-left: 4px; }
    .filter { display: flex; flex-wrap: wrap; gap: 8px; }
    .select, .search { height: 40px; padding: 0 12px; border: 1.5px solid #d5dbe5; border-radius: 10px; background: #f6f8fb; font: 500 13.5px var(--font); color: var(--ink); }
    .search { min-width: 220px; }
    .filter .select { max-width: 100%; }
    @media (max-width: 640px) {
        .filter { width: 100%; }
        .filter .select { flex: 1 1 100%; min-width: 0; }
        .filter .search { flex: 1; min-width: 0; }
    }
    .table-wrap { margin-top: 16px; overflow-x: auto; border: 1px solid var(--line); border-radius: 14px; }
    table { width: 100%; min-width: 1100px; border-collapse: collapse; font-size: 13.5px; }
    th { background: #f6f8fb; text-align: left; padding: 12px 14px; font-size: 12px; font-weight: 700; letter-spacing: .03em; text-transform: uppercase; color: #374151; white-space: nowrap; }
    td { padding: 12px 14px; border-top: 1px solid var(--line); vertical-align: middle; }
    .nim { font-weight: 700; }
    .judul { min-width: 220px; font-style: italic; }
    .huruf { display: inline-grid; place-items: center; width: 24px; height: 24px; margin-left: 6px; border-radius: 7px; background: #e3eefc; color: var(--primary); font-size: 12px; font-weight: 800; }
    .pill { display: inline-block; padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 700; white-space: nowrap; text-decoration: none; }
    .pill--info { background: #e0f2fe; color: #0369a1; } .pill--mute { background: #f1f4f9; color: #4b5563; }
    .btn--sm { height: 32px; padding: 0 12px; font-size: 12.5px; }
    .ubah { display: flex; gap: 6px; align-items: center; }
    .select--sm { height: 32px; max-width: 260px; font-size: 12.5px; }
    .empty { padding: 36px 16px; text-align: center; color: var(--muted); }
    .footer { margin-top: 14px; }
    dialog { border: 0; border-radius: 18px; padding: 0; width: min(440px, calc(100vw - 32px)); box-shadow: 0 24px 60px rgba(10,37,99,.28); }
    dialog::backdrop { background: rgba(10,37,99,.45); }
    dialog form { padding: 24px; display: grid; gap: 14px; }
    dialog h3 { font-size: 18px; font-weight: 700; color: var(--navy); }
    dialog p { font-size: 14px; line-height: 1.5; color: #374151; }
    .dlg-actions { display: flex; justify-content: flex-end; gap: 10px; }
</style>
@endpush

@section('content')
    @if (session('status')) <div class="alert" role="status">{{ session('status') }}</div> @endif
    @if (session('error')) <div class="alert alert--error" role="alert">{{ session('error') }}</div> @endif

    <div class="note">Daftar ini terisi otomatis setelah nilai pendadaran diinput dan hasilnya Lulus. Mahasiswa mendaftar sendiri ke periode yang dibuka dari dashboard mereka; setelah itu hanya admin dan pengajaran yang bisa memindahkan atau melepas periodenya.</div>

    <section class="panel">
        <div class="toolbar">
            <div class="chips">
                <span class="chip">Lulus pendadaran <b>{{ $ringkas['lulus'] }}</b></span>
                <span class="chip">Belum mendaftar periode <b>{{ $ringkas['belum'] }}</b></span>
            </div>
            <form class="filter" method="GET" action="{{ route('admin.yudisium.index') }}" role="search">
                <select class="select" name="periode" aria-label="Periode" onchange="this.form.submit()">
                    <option value="">Semua periode</option>
                    <option value="belum" @selected($filter === 'belum')>Belum mendaftar</option>
                    @foreach ($periode as $p)
                        <option value="{{ $p->id }}" @selected($filter === (string) $p->id)>{{ $p->nama }}{{ $p->dibuka ? ' (dibuka)' : '' }}</option>
                    @endforeach
                </select>
                <input class="search" type="search" name="q" value="{{ $q }}" placeholder="Cari NIM, nama, judul" aria-label="Pencarian">
                <button class="btn btn--primary" type="submit">Cari</button>
            </form>
        </div>

        <div class="table-wrap">
            <table>
                <thead><tr><th>No</th><th>NIM</th><th>Nama</th><th>Prodi</th><th>Judul skripsi</th><th>Nilai akhir</th><th>Periode yudisium</th><th></th></tr></thead>
                <tbody>
                    @forelse ($daftar as $i => $j)
                        @php $m = $j->user; @endphp
                        <tr>
                            <td>{{ $daftar->firstItem() + $i }}</td>
                            <td class="nim">{{ $m->username }}</td>
                            <td>{{ $m->name }}</td>
                            <td>{{ $m->program_studi ?? '-' }}</td>
                            <td class="judul">{{ $j->judul }}</td>
                            <td style="white-space:nowrap">{{ J::formatNilai($j->nilai_akhir) }}<span class="huruf">{{ $j->hurufAkhir() }}</span></td>
                            <td>
                                <form class="ubah" method="POST" action="{{ route('admin.yudisium.periode', $m) }}">
                                    @csrf @method('PUT')
                                    <select class="select select--sm" name="periode_id" aria-label="Periode {{ $m->name }}" required>
                                        @unless ($m->periode) <option value="" selected disabled>Belum mendaftar</option> @endunless
                                        @foreach ($periode as $p)
                                            <option value="{{ $p->id }}" @selected($m->periode_id === $p->id)>{{ $p->nama }}{{ $p->dibuka ? '' : ' (ditutup)' }}</option>
                                        @endforeach
                                    </select>
                                    <button class="btn btn--ghost btn--sm" type="submit">Simpan</button>
                                </form>
                            </td>
                            <td style="white-space:nowrap">
                                <a class="btn btn--ghost btn--sm" href="{{ route('admin.jadwal.show', $j) }}">Nilai</a>
                                @if ($m->periode)
                                    <button class="btn btn--ghost btn--sm" type="button" data-lepas data-url="{{ route('admin.yudisium.lepas', $m) }}" data-nama="{{ $m->name }}">Lepas</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="empty">Belum ada mahasiswa pada tampilan ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($daftar->hasPages()) <div class="footer">{{ $daftar->links('pagination::simple-default') }}</div> @endif
    </section>

    @include('admin.partials.dialog-lepas')
@endsection
