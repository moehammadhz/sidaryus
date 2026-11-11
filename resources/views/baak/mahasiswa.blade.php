@extends('layouts.admin')

@php use App\Models\JadwalPendadaran as J; @endphp

@section('title', 'Kelengkapan Mahasiswa')
@section('crumb', 'Kelengkapan Mahasiswa')
@section('page_title', 'Kelengkapan Mahasiswa')

@push('styles')
<style>
    .note { margin-bottom: 16px; padding: 12px 16px; border-radius: 12px; background: #e3eefc; color: var(--navy); font-size: 13.5px; }
    .panel { background: #fff; border: 1px solid var(--line); border-radius: 16px; padding: 22px; }
    .filter { display: flex; flex-wrap: wrap; gap: 8px; }
    .select, .search { height: 40px; padding: 0 12px; border: 1.5px solid #d5dbe5; border-radius: 10px; background: #f6f8fb; font: 500 13.5px var(--font); color: var(--ink); }
    .search { min-width: 240px; flex: 1 1 240px; max-width: 360px; }
    .table-wrap { margin-top: 16px; overflow-x: auto; border: 1px solid var(--line); border-radius: 14px; }
    table { width: 100%; min-width: 900px; border-collapse: collapse; font-size: 13.5px; }
    th { background: #f6f8fb; text-align: left; padding: 12px 14px; font-size: 12px; font-weight: 700; letter-spacing: .03em; text-transform: uppercase; color: #374151; white-space: nowrap; }
    td { padding: 12px 14px; border-top: 1px solid var(--line); vertical-align: middle; }
    .nim { font-weight: 700; }
    .prog { display: flex; align-items: center; gap: 8px; white-space: nowrap; font-weight: 700; font-variant-numeric: tabular-nums; }
    .prog__bar { width: 70px; height: 6px; border-radius: 99px; background: #eef1f6; overflow: hidden; }
    .prog__bar i { display: block; height: 100%; background: var(--primary); }
    .prog--full .prog__bar i { background: #16a34a; }
    .pill { display: inline-block; padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 700; white-space: nowrap; }
    .pill--ok { background: #dcfce7; color: #166534; } .pill--bad { background: #fee2e2; color: #991b1b; }
    .pill--warn { background: #fef3c7; color: #92400e; } .pill--mute { background: #f1f4f9; color: #4b5563; } .pill--info { background: #e0f2fe; color: #0369a1; }
    .aksi { display: flex; gap: 6px; white-space: nowrap; }
    .btn--sm { height: 32px; padding: 0 12px; font-size: 12.5px; }
    .empty { padding: 36px 16px; text-align: center; color: var(--muted); }
    .footer { margin-top: 14px; }
</style>
@endpush

@section('content')
    <div class="note">Halaman ini hanya untuk melihat. Buka detail untuk memeriksa setiap berkas, atau unduh PDF data kelengkapan setiap mahasiswa.</div>

    <section class="panel">
        <form class="filter" method="GET" action="{{ route('baak.mahasiswa.index') }}" role="search">
            <input class="search" type="search" name="q" value="{{ $q }}" placeholder="Cari nama atau NIM" aria-label="Pencarian">
            <select class="select" name="prodi" aria-label="Program studi" onchange="this.form.submit()">
                <option value="">Semua prodi</option>
                @foreach (\App\Models\User::PROGRAM_STUDI as $p) <option value="{{ $p }}" @selected($prodi === $p)>{{ $p }}</option> @endforeach
            </select>
            <button class="btn btn--primary" type="submit">Cari</button>
            @if ($q !== '' || $prodi !== '') <a class="btn btn--ghost" href="{{ route('baak.mahasiswa.index') }}">Reset</a> @endif
        </form>

        <div class="table-wrap">
            <table>
                <thead><tr><th>No</th><th>NIM</th><th>Nama</th><th>Prodi</th><th>Dokumen</th><th>Jadwal</th><th>Hasil</th><th>Yudisium</th><th></th></tr></thead>
                <tbody>
                    @forelse ($daftar as $i => $m)
                        @php
                            $j = $jadwal->get($m->id);
                            $persen = $total ? min(100, round($m->disetujui_count / $total * 100)) : 0;
                        @endphp
                        <tr>
                            <td>{{ $daftar->firstItem() + $i }}</td>
                            <td class="nim">{{ $m->username }}</td>
                            <td>{{ $m->name }}</td>
                            <td>{{ $m->program_studi ?? '-' }}</td>
                            <td><span @class(['prog', 'prog--full' => $total && $m->disetujui_count >= $total])><span class="prog__bar"><i style="width: {{ $persen }}%"></i></span>{{ $m->disetujui_count }}/{{ $total }}</span></td>
                            <td>
                                @if (! $j) <span class="pill pill--mute">Belum diajukan</span>
                                @else <span class="pill pill--{{ ['pending' => 'warn', 'disetujui' => 'ok', 'ditolak' => 'bad'][$j->status] ?? 'mute' }}">{{ ucfirst($j->status) }}</span>
                                @endif
                            </td>
                            <td>
                                @if ($j?->hasil) <span class="pill pill--{{ $j->lulus() ? 'ok' : 'bad' }}">{{ $j->labelHasil() }} · {{ J::formatNilai($j->nilai_akhir) }}</span>
                                @else <span class="pill pill--mute">-</span>
                                @endif
                            </td>
                            <td>{!! $m->periode ? '<span class="pill pill--info">' . e($m->periode->nama) . '</span>' : '<span class="pill pill--mute">-</span>' !!}</td>
                            <td class="aksi">
                                <a class="btn btn--ghost btn--sm" href="{{ route('baak.mahasiswa.show', $m) }}">Detail</a>
                                <a class="btn btn--primary btn--sm" href="{{ route('baak.mahasiswa.pdf', $m) }}"><x-icon name="arrow-down" :size="14" /> PDF</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="empty">Tidak ada mahasiswa yang cocok.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($daftar->hasPages()) <div class="footer">{{ $daftar->links('pagination::simple-default') }}</div> @endif
    </section>
@endsection
