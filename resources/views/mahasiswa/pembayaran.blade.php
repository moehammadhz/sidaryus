@extends('layouts.mahasiswa')

@php use App\Models\JadwalPendadaran as J; @endphp

@section('title', 'Upload Bukti Pembayaran')
@section('crumb', 'Upload Bukti Pembayaran')
@section('page_title', 'Bukti Pembayaran')

@push('styles')
<style>
    .alert { padding: 12px 16px; border-radius: 12px; font-size: 14px; font-weight: 600; margin-bottom: 18px; line-height: 1.5; }
    .alert a { color: inherit; font-weight: 700; }
    .alert-success { background: #dcfce7; color: #166534; }
    .alert-error { background: #fee2e2; color: #991b1b; }
    .alert-warning { background: #fef3c7; color: #92400e; font-weight: 500; }
    .alert-info { background: #e3eefc; color: var(--navy); font-weight: 500; }
    .pill { display: inline-block; padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 700; }
    .pill--ok { background: #dcfce7; color: #166534; } .pill--warn { background: #fef3c7; color: #92400e; } .pill--bad { background: #fee2e2; color: #991b1b; }

    .file-now { display: flex; align-items: center; gap: 14px; padding: 14px 16px; margin-bottom: 18px; border: 1px solid var(--line); border-radius: 12px; background: #f8fafc; }
    .file-now__icon { width: 42px; height: 42px; border-radius: 10px; display: grid; place-items: center; background: #e3eefc; color: var(--primary); flex: none; }
    .file-now__body { flex: 1; min-width: 0; }
    .file-now__name { font-size: 14px; font-weight: 700; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .file-now__meta { font-size: 12px; color: var(--muted); margin-top: 2px; }

    .unggah { display: flex; flex-direction: column; gap: 10px; }
    .form-label { font-size: 13px; font-weight: 700; color: var(--navy); }
    .drop { display: flex; flex-direction: column; align-items: center; gap: 8px; padding: 26px 18px; border: 2px dashed #cbd5e1; border-radius: 14px; background: #f8fafc; text-align: center; cursor: pointer; transition: border-color .15s, background .15s; }
    .drop:hover, .drop:focus-within { border-color: var(--primary); background: #f3f8ff; }
    .drop__icon { width: 44px; height: 44px; border-radius: 50%; display: grid; place-items: center; background: #e3eefc; color: var(--primary); }
    .drop__teks { font-size: 14px; font-weight: 700; color: var(--navy); }
    .drop__sub { font-size: 12.5px; color: var(--muted); }
    .drop input[type=file] { max-width: 100%; font-size: 13px; color: var(--muted); }
    .drop input[type=file]::file-selector-button { background: #fff; border: 1px solid var(--line); border-radius: 8px; padding: 7px 12px; font-weight: 600; color: var(--navy); cursor: pointer; margin-right: 10px; font-family: inherit; }
    .error-text { font-size: 12px; color: var(--danger); font-weight: 500; }
    .unggah .btn { align-self: flex-start; }

    .riwayat table { width: 100%; min-width: 480px; border-collapse: collapse; font-size: 13.5px; }
    .riwayat th, .riwayat td { padding: 10px 12px; border-bottom: 1px solid var(--line); text-align: left; vertical-align: top; }
    .riwayat th { font-size: 11.5px; color: var(--muted); text-transform: uppercase; letter-spacing: .04em; background: #f8fafc; }
    .riwayat tr:last-child td { border-bottom: 0; }

    .kosong { display: flex; gap: 14px; align-items: flex-start; }
    .kosong__icon { width: 44px; height: 44px; border-radius: 12px; display: grid; place-items: center; background: #e3eefc; color: var(--primary); flex: none; }
    .kosong h2 { font-size: 16px; font-weight: 800; color: var(--navy); }
    .kosong p { margin-top: 4px; font-size: 14px; color: var(--muted); line-height: 1.5; }
    .kosong a { color: var(--primary); font-weight: 700; }
</style>
@endpush

@section('content')
    @if (session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    @if (session('error')) <div class="alert alert-error">{{ session('error') }}</div> @endif

<div class="split">
<div class="split__main">
    @if (! $jadwal)
        <section class="panel kosong">
            <div class="kosong__icon"><x-icon name="calendar" :size="22" /></div>
            <div>
                <h2>Ajukan jadwal ujian terlebih dahulu</h2>
                <p>Bukti pembayaran diunggah setelah Anda mengajukan jadwal di menu <a href="{{ route('mahasiswa.jadwal.index') }}">Upload Jadwal Pendadaran</a>.</p>
            </div>
        </section>
    @else
        <section class="panel">
            <h2 class="panel__title">Pembayaran ujian pendadaran
                @if ($jadwal->status === J::DISETUJUI || $jadwal->buktiDisetujui()) <span class="pill pill--ok">Sudah diverifikasi</span>
                @elseif ($jadwal->buktiDitolak()) <span class="pill pill--bad">Ditolak, unggah ulang</span>
                @elseif ($jadwal->bukti_bayar_path) <span class="pill pill--warn">Menunggu verifikasi</span>
                @else <span class="pill pill--bad">Belum diunggah</span>
                @endif
            </h2>

            @if ($jadwal->status === J::DISETUJUI && $jadwal->hasil === J::TIDAK_LULUS)
                <div class="alert alert-warning">Untuk ujian ulang, ajukan jadwal baru di menu <a href="{{ route('mahasiswa.jadwal.index') }}">Upload Jadwal Pendadaran</a>, lalu unggah bukti pembayaran ujian ulang di sini.</div>
            @elseif ($jadwal->status === J::DITOLAK)
                <div class="alert alert-warning">Jadwal Anda ditolak. Perbaiki jadwalnya di menu <a href="{{ route('mahasiswa.jadwal.index') }}">Upload Jadwal Pendadaran</a>; bukti pembayaran ini tetap dipakai. Ganti bukti di bawah hanya jika memang perlu.</div>
            @elseif ($jadwal->buktiDitolak())
                <div class="alert alert-error" style="font-weight:500">
                    <b>Bukti pembayaran ditolak.</b> Alasan: {{ $jadwal->bukti_bayar_alasan }}<br>
                    Unggah bukti yang benar di bawah. Jadwal Anda tidak perlu diajukan ulang.
                </div>
            @elseif (! $jadwal->bukti_bayar_path)
                <div class="alert alert-warning">Jadwal Anda baru bisa disetujui dan undangan ujian diterbitkan setelah bukti pembayaran diunggah dan disetujui.</div>
            @elseif (! $jadwal->buktiDisetujui() && $jadwal->status !== J::DISETUJUI)
                <div class="alert alert-info">Bukti pembayaran Anda sedang diperiksa bagian pengajaran. Anda masih bisa menggantinya sebelum disetujui.</div>
            @endif

            @if ($jadwal->bukti_bayar_path && ! $jadwal->buktiDitolak())
                <div class="file-now">
                    <div class="file-now__icon"><x-icon name="file" :size="20" /></div>
                    <div class="file-now__body">
                        <div class="file-now__name" title="{{ $jadwal->bukti_bayar_nama }}">{{ $jadwal->bukti_bayar_nama }}</div>
                        <div class="file-now__meta">Bukti pembayaran saat ini</div>
                    </div>
                    <a class="btn btn--ghost" href="{{ route('mahasiswa.jadwal.bukti-bayar', $jadwal) }}" target="_blank" rel="noopener"><x-icon name="eye" :size="16" /> Lihat</a>
                </div>
            @endif

            @if ($bisaUbah)
                <form class="unggah" method="POST" action="{{ route('mahasiswa.pembayaran.store') }}" enctype="multipart/form-data" novalidate>
                    @csrf
                    <label class="form-label" for="bukti_bayar">{{ $jadwal->buktiDitolak() ? 'Bukti pembayaran baru' : ($jadwal->bukti_bayar_path ? 'Ganti bukti pembayaran' : 'Bukti pembayaran') }}</label>
                    <label class="drop">
                        <span class="drop__icon"><x-icon name="upload" :size="20" /></span>
                        <span class="drop__teks">Pilih berkas bukti pembayaran</span>
                        <span class="drop__sub">Foto struk, tangkapan layar transfer, atau PDF · maksimal 5 MB</span>
                        <input type="file" id="bukti_bayar" name="bukti_bayar" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" required>
                    </label>
                    @error('bukti_bayar') <span class="error-text">{{ $message }}</span> @enderror
                    <button type="submit" class="btn btn--primary"><x-icon name="upload" :size="18" /> {{ $jadwal->buktiDitolak() ? 'Unggah bukti baru' : ($jadwal->bukti_bayar_path ? 'Ganti bukti' : 'Unggah bukti') }}</button>
                </form>
            @endif
        </section>

        @if ($jadwal->riwayatBuktiBayar->isNotEmpty())
            <section class="panel riwayat">
                <h2 class="panel__title">Riwayat penolakan bukti bayar <small>{{ $jadwal->riwayatBuktiBayar->count() }} kali</small></h2>
                <div class="tabel-wrap tabel-tumpuk"><table>
                    <thead><tr><th>Ditolak</th><th>Berkas</th><th>Alasan</th></tr></thead>
                    <tbody>
                        @foreach ($jadwal->riwayatBuktiBayar as $r)
                            <tr><td data-label="Ditolak" style="white-space:nowrap">{{ $r->ditolak_at->locale('id')->translatedFormat('d M Y H:i') }}</td><td data-label="Berkas" style="overflow-wrap:anywhere">{{ $r->nama }}</td><td data-label="Alasan">{{ $r->alasan }}</td></tr>
                        @endforeach
                    </tbody>
                </table></div>
            </section>
        @endif
    @endif
</div>

<aside class="split__side">
    @if ($jadwal)
        <section class="panel panel--side">
            <h2 class="panel__title">Jadwal ujian</h2>
            <dl class="info-list">
                <dt>Waktu</dt><dd>{{ $jadwal->hariIndo() }}, {{ $jadwal->tanggalIndo() }} · {{ $jadwal->jamWib() }}</dd>
                <dt>Status</dt><dd>{{ $jadwal->hasil ? $jadwal->labelHasil() : ['pending' => 'Menunggu verifikasi', 'disetujui' => 'Disetujui', 'ditolak' => 'Ditolak'][$jadwal->status] }}</dd>
                @if ($jadwal->status === J::DITOLAK && $jadwal->alasan_tolak) <dt>Alasan ditolak</dt><dd>{{ $jadwal->alasan_tolak }}</dd> @endif
                <dt>Judul</dt><dd style="font-weight:500">{{ $jadwal->judul }}</dd>
            </dl>
        </section>
    @endif

    <x-alur-jadwal :jadwal="$jadwal" />

    <section class="panel panel--side">
        <h2 class="panel__title">Ketentuan bukti pembayaran</h2>
        <ul class="ket">
            <li>Format PDF, JPG, atau PNG dengan ukuran maksimal 5 MB.</li>
            <li>Pastikan nominal, tanggal, dan nama pengirim terbaca jelas.</li>
            <li>Bukti diperiksa bagian pengajaran. Setelah disetujui, bukti tidak bisa diganti.</li>
            <li>Jika ditolak, cukup unggah bukti baru. Jadwal tidak perlu diajukan ulang.</li>
        </ul>
    </section>
</aside>
</div>
@endsection
