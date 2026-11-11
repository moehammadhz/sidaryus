@extends('layouts.admin')

@php use App\Models\JadwalPendadaran as J; @endphp

@section('title', $periode->nama)
@section('crumb', 'Periode Yudisium')
@section('page_title', $periode->nama)

@section('actions')
    <a class="btn btn--ghost" href="{{ route('admin.periode.index') }}">Kembali ke Periode Yudisium</a>
@endsection

@push('styles')
<style>
    .alert { margin-bottom: 16px; padding: 12px 16px; border-radius: 12px; background: #dcfce7; color: #166534; font-size: 14px; font-weight: 600; }
    .alert--error { background: #fee2e2; color: #991b1b; }
    .sum { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; }
    .stat { background: #fff; border: 1px solid var(--line); border-radius: 16px; padding: 16px 18px; }
    .stat__lbl { font-size: 12.5px; color: var(--muted); font-weight: 600; }
    .stat__val { margin-top: 4px; font-size: 26px; font-weight: 800; color: var(--navy); font-variant-numeric: tabular-nums; }
    .stat__sub { font-size: 12.5px; color: var(--muted); }
    .chips { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 6px; }
    .chip { padding: 4px 10px; border-radius: 999px; background: #e0f2fe; color: #0369a1; font-size: 12px; font-weight: 700; }
    .status { display: inline-block; padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 700; }
    .status--buka { background: #dcfce7; color: #166534; } .status--tutup { background: #f1f4f9; color: #4b5563; }
    .panel { margin-top: 18px; background: #fff; border: 1px solid var(--line); border-radius: 16px; padding: 22px; }
    .bar { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 10px; }
    .bar h2 { font-size: 16px; font-weight: 800; color: var(--navy); }
    .bar form { display: inline; }
    .table-wrap { margin-top: 14px; overflow-x: auto; border: 1px solid var(--line); border-radius: 14px; }
    table { width: 100%; min-width: 860px; border-collapse: collapse; font-size: 13.5px; }
    th { background: #f6f8fb; text-align: left; padding: 12px 14px; font-size: 12px; font-weight: 700; letter-spacing: .03em; text-transform: uppercase; color: #374151; white-space: nowrap; }
    td { padding: 12px 14px; border-top: 1px solid var(--line); vertical-align: middle; }
    .nim { font-weight: 700; font-variant-numeric: tabular-nums; }
    .judul { min-width: 220px; font-style: italic; }
    .huruf { display: inline-grid; place-items: center; width: 24px; height: 24px; margin-left: 6px; border-radius: 7px; background: #e3eefc; color: var(--primary); font-size: 12px; font-weight: 800; }
    .btn--sm { height: 32px; padding: 0 12px; font-size: 12.5px; }
    .empty { padding: 36px 16px; text-align: center; color: var(--muted); }
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

    <div class="sum">
        <div class="stat"><div class="stat__lbl">Status pendaftaran</div>
            <div class="stat__val"><span class="status status--{{ $periode->dibuka ? 'buka' : 'tutup' }}">{{ $periode->dibuka ? 'Dibuka' : 'Ditutup' }}</span></div>
            <div class="stat__sub">{{ $periode->dibuka ? 'Mahasiswa lulus bisa mendaftar' : 'Tidak menerima pendaftaran baru' }}</div></div>
        <div class="stat"><div class="stat__lbl">Mahasiswa terdaftar</div><div class="stat__val">{{ $mahasiswa->count() }}</div>
            <div class="chips">@foreach ($perProdi as $prodi => $n) <span class="chip">{{ $prodi }} · {{ $n }}</span> @endforeach</div></div>
        <div class="stat"><div class="stat__lbl">Rata-rata nilai pendadaran</div>
            <div class="stat__val">{{ $rataRata !== null ? J::formatNilai($rataRata) : '-' }}</div>
            <div class="stat__sub">{{ $rataRata !== null ? 'Huruf ' . J::huruf($rataRata) : 'Belum ada data' }}</div></div>
    </div>

    <section class="panel">
        <div class="bar">
            <h2>Daftar mahasiswa</h2>
            <form method="POST" action="{{ route('admin.periode.status', $periode) }}">
                @csrf @method('PATCH')
                <button class="btn {{ $periode->dibuka ? 'btn--ghost' : 'btn--primary' }}" type="submit">{{ $periode->dibuka ? 'Tutup pendaftaran' : 'Buka pendaftaran' }}</button>
            </form>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>No</th><th>NIM</th><th>Nama</th><th>Prodi</th><th>Judul skripsi</th><th>Nilai</th><th>Tanggal daftar</th><th></th></tr></thead>
                <tbody>
                    @forelse ($mahasiswa as $i => $m)
                        @php $j = $jadwal->get($m->id); @endphp
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td class="nim">{{ $m->username }}</td>
                            <td>{{ $m->name }}</td>
                            <td>{{ $m->program_studi ?? '-' }}</td>
                            <td class="judul">{{ $j?->judul ?? '-' }}</td>
                            <td style="white-space:nowrap">{{ J::formatNilai($j?->nilai_akhir) }}@if ($j)<span class="huruf">{{ $j->hurufAkhir() }}</span>@endif</td>
                            <td style="white-space:nowrap">{{ $m->periode_daftar_at?->locale('id')->translatedFormat('d M Y') ?? '-' }}</td>
                            <td><button class="btn btn--ghost btn--sm" type="button" data-lepas data-url="{{ route('admin.yudisium.lepas', $m) }}" data-nama="{{ $m->name }}">Lepas</button></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="empty">Belum ada mahasiswa yang mendaftar ke periode ini.{{ $periode->dibuka ? '' : ' Buka pendaftaran agar mahasiswa yang lulus bisa mendaftar.' }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @include('admin.partials.dialog-lepas')
@endsection
