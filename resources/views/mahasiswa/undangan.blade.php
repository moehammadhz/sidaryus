@extends('layouts.mahasiswa')

@section('title', 'Undangan Jadwal Ujian')
@section('crumb', 'Undangan Jadwal Ujian')
@section('page_title', 'Undangan Jadwal Ujian')

@push('styles')
<style>
    .kosong { display: flex; gap: 14px; align-items: flex-start; }
    .kosong__icon { width: 44px; height: 44px; border-radius: 12px; display: grid; place-items: center; background: #e3eefc; color: var(--primary); flex: none; }
    .kosong h2 { font-size: 16px; font-weight: 800; color: var(--navy); }
    .kosong p { margin-top: 4px; font-size: 14px; color: var(--muted); line-height: 1.5; }
    .kosong a { color: var(--primary); font-weight: 700; }
    .list { display: flex; flex-direction: column; gap: 12px; }
    .item { display: flex; flex-wrap: wrap; align-items: center; gap: 14px; border: 1px solid var(--line); border-radius: 14px; padding: 16px 18px; background: #fff; }
    .item--baru { border-color: #bfd6f6; background: #f7fbff; }
    .item__icon { width: 46px; height: 46px; border-radius: 12px; display: grid; place-items: center; background: #fee2e2; color: #b91c1c; flex: none; }
    .item__body { flex: 1 1 260px; min-width: 0; }
    .item__title { font-size: 15px; font-weight: 800; color: var(--navy); }
    .item__meta { margin-top: 4px; font-size: 13px; color: var(--muted); }
    .item__actions { display: flex; gap: 8px; flex-wrap: wrap; }
    .dosen { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; margin-top: 16px; }
    .dosen div { padding: 12px 14px; border-radius: 12px; background: #f8fafc; border: 1px solid var(--line); min-width: 0; }
    .dosen small { display: block; font-size: 11.5px; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: .04em; }
    .dosen b { display: block; margin-top: 4px; font-size: 13.5px; color: var(--ink); overflow-wrap: anywhere; }
    .judul-ujian { font-size: 15px; font-weight: 600; color: var(--ink); line-height: 1.5; }
    .waktu-ujian { display: flex; flex-wrap: wrap; gap: 8px 18px; margin-top: 10px; font-size: 13.5px; color: #374151; }
    .waktu-ujian span { display: inline-flex; align-items: center; gap: 6px; }
    .waktu-ujian svg { color: var(--primary); }
    @media (max-width: 760px) { .dosen { grid-template-columns: 1fr; } }
    .tag { display: inline-block; margin-left: 6px; padding: 2px 8px; border-radius: 999px; background: #dcfce7; color: #166534; font-size: 11px; font-weight: 700; vertical-align: middle; }
</style>
@endpush

@section('content')
@php $detail = $undangan->first() ?? ($terakhir && $terakhir->status === 'disetujui' ? $terakhir : null); @endphp
<div class="split">
<div class="split__main">
    @if ($undangan->isEmpty())
        <section class="panel kosong">
            <div class="kosong__icon"><x-icon name="file" :size="22" /></div>
            <div>
                @if (! $terakhir)
                    <h2>Belum ada undangan</h2>
                    <p>Undangan ujian tersedia setelah Anda mengajukan jadwal di menu <a href="{{ route('mahasiswa.jadwal.index') }}">Upload Jadwal Pendadaran</a> dan bukti bayar disetujui.</p>
                @elseif ($terakhir->status === 'pending' && ! $terakhir->bukti_bayar_path)
                    <h2>Bukti pembayaran belum diunggah</h2>
                    <p>Undangan diterbitkan setelah jadwal disetujui, dan jadwal baru bisa disetujui setelah Anda mengunggah bukti pembayaran di menu <a href="{{ route('mahasiswa.pembayaran.index') }}">Upload Bukti Pembayaran</a>.</p>
                @elseif ($terakhir->status === 'pending' && $terakhir->buktiDitolak())
                    <h2>Bukti pembayaran ditolak</h2>
                    <p>Alasan: {{ $terakhir->bukti_bayar_alasan }}. Unggah bukti yang baru di menu <a href="{{ route('mahasiswa.pembayaran.index') }}">Upload Bukti Pembayaran</a>; jadwal tidak perlu diajukan ulang.</p>
                @elseif ($terakhir->status === 'pending')
                    <h2>Menunggu verifikasi</h2>
                    <p>Jadwal dan bukti bayar Anda sedang diperiksa. Undangan diterbitkan setelah disetujui.</p>
                @elseif ($terakhir->status === 'ditolak')
                    <h2>Pengajuan ditolak</h2>
                    <p>{{ $terakhir->alasan_tolak ?: 'Periksa kembali data jadwal dan bukti bayar Anda.' }} Perbaiki jadwal Anda di menu <a href="{{ route('mahasiswa.jadwal.index') }}">Upload Jadwal Pendadaran</a>.</p>
                @else
                    <h2>Undangan sedang disiapkan</h2>
                    <p>Jadwal Anda pada {{ $terakhir->hariIndo() }}, {{ $terakhir->tanggalIndo() }} pukul {{ $terakhir->jamWib() }} sudah disetujui. Bagian pengajaran sedang menyiapkan undangan resminya.</p>
                @endif
            </div>
        </section>
    @else
        <section class="panel">
        <h2 class="panel__title">Daftar undangan <small>{{ $undangan->count() }} berkas</small></h2>
        <div class="list">
            @foreach ($undangan as $i => $u)
                <div @class(['item', 'item--baru' => $i === 0])>
                    <div class="item__icon"><x-icon name="file" :size="22" /></div>
                    <div class="item__body">
                        <div class="item__title">Undangan Ujian Pendadaran @if ($i === 0) <span class="tag">Terbaru</span> @endif</div>
                        <div class="item__meta">{{ $u->hariIndo() }}, {{ $u->tanggalIndo() }} · {{ $u->jamWib() }} · diterbitkan {{ $u->undangan_at?->locale('id')->translatedFormat('d F Y') }}</div>
                    </div>
                    <div class="item__actions">
                        <a class="btn btn--ghost" href="{{ route('mahasiswa.undangan.unduh', $u) }}" target="_blank" rel="noopener">Lihat</a>
                        <a class="btn btn--primary" href="{{ route('mahasiswa.undangan.unduh', ['jadwal' => $u, 'unduh' => 1]) }}"><x-icon name="arrow-down" :size="16" /> Unduh PDF</a>
                    </div>
                </div>
            @endforeach
        </div>
        </section>
    @endif

    @if ($detail)
        <section class="panel">
            <h2 class="panel__title">Detail ujian</h2>
            <p class="judul-ujian">{{ $detail->judul }}</p>
            <div class="waktu-ujian">
                <span><x-icon name="calendar" :size="16" /> {{ $detail->hariIndo() }}, {{ $detail->tanggalIndo() }}</span>
                <span><x-icon name="clock" :size="16" /> {{ $detail->jamWib() }}</span>
            </div>
            <div class="dosen">
                <div><small>Pembimbing Utama</small><b>{{ $detail->pembimbing_1 }}</b></div>
                <div><small>Pembimbing Pendamping</small><b>{{ $detail->pembimbing_2 ?? '-' }}</b></div>
                <div><small>Penguji</small><b>{{ $detail->penguji }}</b></div>
            </div>
        </section>
    @endif
</div>

<aside class="split__side">
    <x-alur-jadwal :jadwal="$terakhir" />

    <section class="panel panel--side">
        <h2 class="panel__title">Tentang undangan</h2>
        <ul class="ket">
            <li>Undangan resmi diterbitkan bagian pengajaran setelah jadwal dan bukti pembayaran disetujui.</li>
            <li>Unduh dan simpan berkas PDF-nya sebelum hari ujian.</li>
            <li>Jika mengikuti ujian ulang, undangan baru akan muncul paling atas dengan tanda Terbaru.</li>
        </ul>
    </section>
</aside>
</div>
@endsection
