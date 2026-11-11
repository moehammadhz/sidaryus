@extends('layouts.admin')

@php
    use App\Models\JadwalPendadaran as J;
    $m = $mahasiswa;
    $j = $jadwal;
    $labelDok = ['menunggu' => ['Menunggu', 'warn'], 'disetujui' => ['Disetujui', 'ok'], 'ditolak' => ['Ditolak', 'bad']];
@endphp

@section('title', 'Kelengkapan ' . $m->name)
@section('crumb', 'Kelengkapan Mahasiswa')
@section('page_title', $m->name . ' · ' . $m->username)

@section('actions')
    <a class="btn btn--ghost" href="{{ route('baak.mahasiswa.index') }}">Kembali</a>
    <a class="btn btn--primary" href="{{ route('baak.mahasiswa.pdf', $m) }}"><x-icon name="arrow-down" :size="16" /> Unduh PDF</a>
@endsection

@push('styles')
<style>
    .grid { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 18px; align-items: start; }
    .col { display: flex; flex-direction: column; gap: 18px; min-width: 0; }
    .panel { background: #fff; border: 1px solid var(--line); border-radius: 16px; padding: 22px; }
    .panel h2 { font-size: 16px; font-weight: 800; color: var(--navy); display: flex; justify-content: space-between; gap: 10px; flex-wrap: wrap; align-items: center; }
    .dl { margin-top: 12px; display: grid; grid-template-columns: 140px minmax(0, 1fr); gap: 8px 14px; font-size: 14px; }
    .dl dt { color: var(--muted); } .dl dd { margin: 0; font-weight: 600; }
    .pill { display: inline-block; padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 700; white-space: nowrap; }
    .pill--ok { background: #dcfce7; color: #166534; } .pill--bad { background: #fee2e2; color: #991b1b; }
    .pill--warn { background: #fef3c7; color: #92400e; } .pill--mute { background: #f1f4f9; color: #4b5563; } .pill--info { background: #e0f2fe; color: #0369a1; }
    .docs { margin-top: 12px; display: flex; flex-direction: column; gap: 8px; }
    .doc { display: flex; flex-wrap: wrap; align-items: center; gap: 8px 12px; padding: 10px 12px; border: 1px solid var(--line); border-radius: 12px; }
    .doc__name { flex: 1 1 220px; min-width: 0; font-size: 13.5px; font-weight: 600; }
    .doc__name small { display: block; font-size: 12px; color: var(--muted); font-weight: 500; }
    .btn--sm { height: 30px; padding: 0 10px; font-size: 12px; }
    .nilai { margin-top: 12px; width: 100%; border-collapse: collapse; font-size: 13.5px; }
    .nilai td { padding: 8px 6px; border-bottom: 1px solid var(--line); }
    .nilai tr:last-child td { font-weight: 800; border-bottom: 0; }
    @media (max-width: 960px) { .grid { grid-template-columns: 1fr; } }
    @media (max-width: 520px) { .dl { grid-template-columns: 1fr; gap: 2px; } .panel { padding: 16px; } }
</style>
@endpush

@section('content')
<div class="grid">
    <div class="col">
        <section class="panel">
            <h2>Data mahasiswa</h2>
            <dl class="dl">
                <dt>Nama</dt><dd>{{ $m->name }}</dd>
                <dt>NIM</dt><dd>{{ $m->username }}</dd>
                <dt>Program studi</dt><dd>{{ $m->program_studi ?? '-' }}</dd>
                <dt>Angkatan</dt><dd>{{ $m->angkatan ?? '-' }}</dd>
                <dt>Kelas</dt><dd>{{ $m->kelas ?? '-' }}</dd>
                <dt>No. WhatsApp</dt><dd>{{ \App\Support\NomorWa::tampil($m->no_wa) }}</dd>
            </dl>
        </section>

        <section class="panel">
            <h2>Dokumen persyaratan <span class="pill pill--{{ $lengkap ? 'ok' : 'warn' }}">{{ $disetujui }}/{{ $total }} disetujui</span></h2>
            <div class="docs">
                @foreach ($persyaratan as $p)
                    @php $d = $dokumen->get($p->id); @endphp
                    <div class="doc">
                        <div class="doc__name">{{ $p->kode }} · {{ $p->nama }}
                            @if ($d) <small>Diunggah {{ $d->diunggah_at?->format('d/m/Y H:i') }}{{ $d->catatan ? ' · Catatan: ' . $d->catatan : '' }}</small> @endif
                        </div>
                        @if ($d)
                            <span class="pill pill--{{ $labelDok[$d->status][1] ?? 'mute' }}">{{ $labelDok[$d->status][0] ?? $d->status }}</span>
                            <a class="btn btn--ghost btn--sm" href="{{ route('admin.dokumen.berkas', $d) }}" target="_blank" rel="noopener">Buka</a>
                        @else
                            <span class="pill pill--mute">Belum diunggah</span>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>
    </div>

    <div class="col">
        <section class="panel">
            <h2>Jadwal pendadaran
                @if ($j) <span class="pill pill--{{ ['pending' => 'warn', 'disetujui' => 'ok', 'ditolak' => 'bad'][$j->status] ?? 'mute' }}">{{ ucfirst($j->status) }}</span> @endif
            </h2>
            @if (! $j)
                <p class="dl" style="display:block;color:var(--muted)">Belum mengajukan jadwal.</p>
            @else
                <dl class="dl">
                    <dt>Judul</dt><dd style="font-style:italic">{{ $j->judul }}</dd>
                    <dt>Pembimbing Utama</dt><dd>{{ $j->pembimbing_1 }}</dd>
                    <dt>Pembimbing Pendamping</dt><dd>{{ $j->pembimbing_2 ?? '-' }}</dd>
                    <dt>Penguji</dt><dd>{{ $j->penguji }}</dd>
                    <dt>Waktu</dt><dd>{{ $j->hariIndo() }}, {{ $j->tanggalIndo() }} · {{ $j->jamWib() }}</dd>
                    <dt>Bukti bayar</dt><dd>@if ($j->bukti_bayar_path)<a href="{{ route('admin.jadwal.bukti-bayar', $j) }}" target="_blank" rel="noopener">Buka bukti bayar</a>@else - @endif</dd>
                    <dt>Undangan</dt><dd>@if ($j->undangan_path)<a href="{{ route('admin.jadwal.undangan', $j) }}" target="_blank" rel="noopener">Buka undangan</a>@else Belum diterbitkan @endif</dd>
                </dl>
            @endif
        </section>

        <section class="panel">
            <h2>Nilai & yudisium
                @if ($j?->hasil) <span class="pill pill--{{ $j->lulus() ? 'ok' : 'bad' }}">{{ $j->labelHasil() }}</span> @endif
            </h2>
            @if ($j?->hasil)
                <table class="nilai">
                    <tr><td>Pembimbing Utama</td><td>{{ J::formatNilai($j->nilai_pembimbing_1) }}</td></tr>
                    @if ($j->pembimbing_2) <tr><td>Pembimbing Pendamping</td><td>{{ J::formatNilai($j->nilai_pembimbing_2) }}</td></tr> @endif
                    <tr><td>Penguji</td><td>{{ J::formatNilai($j->nilai_penguji) }}</td></tr>
                    <tr><td>Nilai akhir</td><td>{{ J::formatNilai($j->nilai_akhir) }} ({{ $j->hurufAkhir() }})</td></tr>
                </table>
            @else
                <p style="margin-top:10px;color:var(--muted);font-size:14px">Nilai belum diinput.</p>
            @endif
            <dl class="dl">
                <dt>Periode yudisium</dt><dd>{{ $m->periode?->nama ?? '-' }}</dd>
                <dt>Tanggal daftar</dt><dd>{{ $m->periode_daftar_at?->locale('id')->translatedFormat('d F Y') ?? '-' }}</dd>
            </dl>
        </section>
    </div>
</div>
@endsection
