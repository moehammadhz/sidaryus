@extends('layouts.admin')

@section('title', 'Dashboard Petugas')
@section('crumb', 'Dashboard')
@section('page_title', 'Dashboard Petugas')

@push('styles')
<style>
    .banner {
        display: flex; align-items: center; justify-content: space-between; gap: 20px;
        min-height: 112px; padding: 20px 32px; border-radius: 20px; color: #fff;
        background:
            radial-gradient(ellipse 60% 120% at 0% 0%, rgba(217,232,239,.7) 0%, rgba(217,232,239,0) 100%),
            radial-gradient(ellipse 50% 100% at 100% 100%, #0b1f55 0%, rgba(11,31,85,0) 100%),
            linear-gradient(100deg, #5db3e8 0%, #2a82d6 45%, #0c3f9c 100%);
    }
    .banner__date { font-size: 13px; font-weight: 500; opacity: .9; }
    .banner h2 { margin-top: 4px; font-size: 26px; font-weight: 700; letter-spacing: -.02em; }
    .banner p { margin-top: 4px; font-size: 13px; opacity: .9; }
    .banner__actions { display: flex; gap: 10px; flex-wrap: wrap; }
    .banner .btn { height: 42px; padding: 0 18px; font-weight: 700; }
    .banner .btn--white { background: #fff; color: var(--navy); }
    .banner .btn--outline { background: transparent; color: #fff; border: 1.5px solid rgba(255,255,255,.7); }

    /* Jumlah kartu mengikuti hak akses; lebar menyesuaikan tanpa ruang kosong */
    .stats { margin-top: 20px; display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 16px; }
    .stat {
        display: flex; align-items: center; gap: 16px; min-width: 0;
        background: #fff; border: 1px solid var(--line); border-radius: 16px; padding: 18px 20px;
        text-decoration: none; color: inherit; transition: border-color .15s, box-shadow .15s;
    }
    .stat:hover { border-color: #c9d8f0; box-shadow: 0 6px 18px rgba(10,37,99,.06); }
    .stat__icon { width: 46px; height: 46px; border-radius: 12px; display: grid; place-items: center; flex: none; }
    .stat__num { font-size: 26px; font-weight: 800; letter-spacing: -.02em; line-height: 1; }
    .stat__label { margin-top: 4px; font-size: 12.5px; color: var(--muted); }

    .tone-amber { background: #fef3c7; color: #b45309; }
    .tone-sky   { background: #e0f2fe; color: #0369a1; }
    .tone-green { background: #dcfce7; color: #15803d; }

    .kosong {
        margin-top: 20px; padding: 40px 24px; text-align: center;
        background: #fff; border: 1px dashed #c9d3e3; border-radius: 16px; color: var(--muted); font-size: 14px; line-height: 1.6;
    }
    .kosong strong { display: block; margin-bottom: 4px; font-size: 16px; color: var(--navy); }

    @media (max-width: 1000px) { .banner { flex-direction: column; align-items: flex-start; padding: 24px; } }
    @media (max-width: 600px)  { .banner h2 { font-size: 22px; } }
</style>
@endpush

@section('content')
@php
    // Kartu hanya dibuat untuk halaman yang boleh dibuka petugas ini
    // [label, angka, ikon, warna, tujuan]
    $kartu = [];

    if ($bisaDokumen) {
        $kartu[] = ['Dokumen menunggu verifikasi', $stats['dokumen_menunggu'], 'file', 'amber', route('admin.dokumen.index')];
    }

    if ($bisaJadwal) {
        $kartu[] = ['Jadwal menunggu persetujuan', $stats['jadwal_pending'],   'clock',    'sky',   route('admin.jadwal.index')];
        $kartu[] = ['Jadwal pendadaran bulan ini', $stats['jadwal_bulan_ini'], 'calendar', 'green', route('admin.jadwal.index')];
    }
@endphp

<section class="banner">
    <div>
        <div class="banner__date">{{ $tanggal }}</div>
        <h2>Selamat datang, {{ request()->user()->name }}</h2>
        <p>Sistem Informasi Pendadaran dan Yudisium · Fakultas Teknik Universitas Janabadra</p>
    </div>

    @if ($bisaDokumen || $bisaJadwal)
        <div class="banner__actions">
            @if ($bisaDokumen)
                <a class="btn btn--white" href="{{ route('admin.dokumen.index') }}">Verifikasi Dokumen</a>
            @endif
            @if ($bisaJadwal)
                <a class="btn btn--outline" href="{{ route('admin.jadwal.index') }}">Atur Jadwal</a>
            @endif
        </div>
    @endif
</section>

@if (count($kartu) > 0)
    <section class="stats" aria-label="Ringkasan">
        @foreach ($kartu as [$label, $angka, $ikon, $warna, $tujuan])
            <a class="stat" href="{{ $tujuan }}">
                <div class="stat__icon tone-{{ $warna }}"><x-icon :name="$ikon" :size="22" /></div>
                <div>
                    <div class="stat__num">{{ number_format($angka, 0, ',', '.') }}</div>
                    <div class="stat__label">{{ $label }}</div>
                </div>
            </a>
        @endforeach
    </section>
@else
    <div class="kosong">
        <strong>Belum ada fitur yang bisa Anda akses</strong>
        Hubungi admin jika Anda merasa seharusnya memiliki akses ke menu tertentu.
    </div>
@endif
@endsection