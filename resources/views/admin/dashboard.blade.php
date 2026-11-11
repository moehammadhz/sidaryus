@extends('layouts.admin')

@section('title', 'Dashboard Admin')
@section('crumb', 'Dashboard')
@section('page_title', 'Dashboard Admin')


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

    .stats { margin-top: 20px; display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; }
    .stat { background: #fff; border: 1px solid var(--line); border-radius: 16px; padding: 18px 20px; display: flex; align-items: center; gap: 16px; }
    .stat__icon { width: 46px; height: 46px; border-radius: 12px; display: grid; place-items: center; flex: none; }
    .stat__num { font-size: 26px; font-weight: 800; letter-spacing: -.02em; line-height: 1; }
    .stat__label { margin-top: 4px; font-size: 12.5px; color: var(--muted); }
    .tone-blue  { background: #e3eefc; color: #2b72d5; }
    .tone-amber { background: #fef3c7; color: #b45309; }
    .tone-sky   { background: #e0f2fe; color: #0369a1; }
    .tone-green { background: #dcfce7; color: #15803d; }

    /* Tiga kartu konten: tinggi sama, isi dibatasi */
    .cards { margin-top: 20px; display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; align-items: stretch; }
    .card {
        display: flex; flex-direction: column; min-width: 0;
        background: #fff; border: 1px solid var(--line); border-top: 3px solid var(--accent);
        border-radius: 16px; padding: 20px 22px 22px;
    }
    .card--blue  { --accent: #2b72d5; --title: #1d4ed8; }
    .card--sky   { --accent: #5db3e8; --title: #0369a1; }
    .card--green { --accent: #16a34a; --title: #15803d; }
    .card__head { display: flex; align-items: center; gap: 12px; padding-bottom: 14px; border-bottom: 1px solid var(--line); }
    .card__icon { width: 36px; height: 36px; border-radius: 10px; display: grid; place-items: center; flex: none; }
    .card__title { flex: 1; font-size: 17px; font-weight: 700; color: var(--title); }
    .card__edit { font-size: 12.5px; font-weight: 600; color: var(--primary); text-decoration: none; }
    .card__edit:hover { text-decoration: underline; }

    /* Isi kartu: batas tinggi 440px; bagian bawah memudar jika terpotong */
    .card__body { position: relative; max-height: 440px; overflow: hidden; }
    .card__body.is-clipped::after {
        content: ''; position: absolute; left: 0; right: 0; bottom: 0; height: 64px;
        background: linear-gradient(to bottom, rgba(255,255,255,0), #fff);
        pointer-events: none;
    }
    .more {
        margin-top: auto; align-self: flex-start; padding: 14px 0 0;
        display: inline-flex; align-items: center; gap: 6px;
        border: 0; background: transparent; cursor: pointer;
        font: 700 13px var(--font); color: var(--primary);
    }
    .more:hover { color: var(--primary-dark); text-decoration: underline; }
    .more[hidden] { display: none; }

    /* Daftar item: gaya yang sama untuk ketiga kartu */
    .items { margin-top: 16px; list-style: none; display: flex; flex-direction: column; gap: 8px; font-size: 13.5px; line-height: 1.5; color: #374151; }
    .items li {
        display: flex; align-items: center; gap: 12px; padding: 10px 12px;
        background: #f6f8fb; border-left: 3px solid var(--accent); border-radius: 10px;
    }
    .items li svg { flex: none; color: var(--accent); }
    .items .no {
        flex: none; width: 24px; height: 24px; border-radius: 50%;
        background: #e3eefc; color: var(--accent); font-size: 12px; font-weight: 700;
        display: grid; place-items: center;
    }
    .kosong { margin-top: 16px; font-size: 13.5px; color: var(--muted); }

    /* Pop-up isi lengkap: warna aksen dibawa dari kartu asal */
    dialog.dlg-detail { border: 0; border-radius: 18px; padding: 0; width: min(560px, calc(100vw - 32px)); box-shadow: 0 24px 60px rgba(10,37,99,.28); }
    dialog.dlg-detail::backdrop { background: rgba(10,37,99,.45); }
    dialog.dlg-detail[open] { display: flex; flex-direction: column; }
    .dlg-detail__head { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 18px 24px; border-bottom: 1px solid var(--line); }
    .dlg-detail__head h3 { font-size: 18px; font-weight: 800; letter-spacing: -.01em; color: var(--navy); }
    .dlg-detail__x { width: 36px; height: 36px; display: grid; place-items: center; border: 0; border-radius: 10px; background: transparent; color: #6b7280; cursor: pointer; flex: none; }
    .dlg-detail__x:hover { background: #f1f4f9; color: var(--ink); }
    .dlg-detail__body { padding: 4px 24px 24px; overflow-y: auto; }

    @media (max-width: 1200px) { .stats { grid-template-columns: repeat(2, 1fr); } .cards { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 1000px) { .banner { flex-direction: column; align-items: flex-start; padding: 24px; } }
    @media (max-width: 700px)  { .stats, .cards { grid-template-columns: 1fr; } .card__body { max-height: 360px; } }
</style>
@endpush

@section('content')
@php
    $kartu = [
        ['Mahasiswa terdaftar',          $stats['mahasiswa'],        'users',    'blue'],
        ['Dokumen menunggu verifikasi',  $stats['dokumen_menunggu'], 'file',     'amber'],
        ['Jadwal pendadaran bulan ini',  $stats['jadwal_bulan_ini'], 'calendar', 'sky'],
        ['Lulus yudisium',               $stats['lulus'],            'cap',      'green'],
    ];
@endphp

<section class="banner">
    <div>
        <div class="banner__date">{{ $tanggal }}</div>
        <h2>Selamat datang, {{ request()->user()->name }}</h2>
        <p>Sistem Informasi Pendadaran dan Yudisium · Fakultas Teknik Universitas Janabadra</p>
    </div>
    <div class="banner__actions">
        <a class="btn btn--white" href="{{ route('admin.dokumen.index') }}">Verifikasi Dokumen</a>
        <a class="btn btn--outline" href="{{ route('admin.jadwal.index') }}">Atur Jadwal</a>
    </div>
</section>

<section class="stats" aria-label="Ringkasan">
    @foreach ($kartu as [$label, $angka, $ikon, $warna])
        <div class="stat">
            <div class="stat__icon tone-{{ $warna }}"><x-icon :name="$ikon" :size="22" /></div>
            <div>
                <div class="stat__num">{{ number_format($angka, 0, ',', '.') }}</div>
                <div class="stat__label">{{ $label }}</div>
            </div>
        </div>
    @endforeach
</section>

<section class="cards">

    {{-- Panduan Pengisian --}}
    <article class="card card--blue" id="panduan" data-kartu data-judul="Panduan Pengisian" data-warna="blue">
        <div class="card__head">
            <div class="card__icon tone-blue"><x-icon name="info" :size="20" /></div>
            <h3 class="card__title">Panduan Pengisian</h3>
            <a class="card__edit" href="{{ route('admin.konten') }}#panduan">Edit</a>
        </div>
        <div class="card__body" data-isi>
            <ol class="items">
                @foreach ($konten['panduan'] as $i => $poin)
                    <li><span class="no">{{ $i + 1 }}</span><span>{{ $poin }}</span></li>
                @endforeach
            </ol>
        </div>
        <button class="more" type="button" data-selengkapnya hidden>
            Lihat selengkapnya
            <x-icon name="arrow-down" :size="14" />
        </button>
    </article>

    {{-- Syarat Dokumen (dikelola lewat menu Kelola Persyaratan) --}}
    <article class="card card--sky" id="syarat" data-kartu data-judul="Syarat Dokumen" data-warna="sky">
        <div class="card__head">
            <div class="card__icon tone-sky"><x-icon name="clipboard" :size="20" /></div>
            <h3 class="card__title">Syarat Dokumen</h3>
            <a class="card__edit" href="{{ route('admin.persyaratan.index') }}">Kelola</a>
        </div>
        <div class="card__body" data-isi>
            @if (count($konten['syarat']))
                <ul class="items">
                    @foreach ($konten['syarat'] as $poin)
                        <li><x-icon name="file" /><span>{{ $poin }}</span></li>
                    @endforeach
                </ul>
            @else
                <p class="kosong">Belum ada persyaratan. Tambahkan lewat menu Kelola Persyaratan.</p>
            @endif
        </div>
        <button class="more" type="button" data-selengkapnya hidden>
            Lihat selengkapnya
            <x-icon name="arrow-down" :size="14" />
        </button>
    </article>

    {{-- Bebas Teori --}}
    <article class="card card--green" id="bebas_teori" data-kartu data-judul="Bebas Teori" data-warna="green">
        <div class="card__head">
            <div class="card__icon tone-green"><x-icon name="cap" :size="20" /></div>
            <h3 class="card__title">Bebas Teori</h3>
            <a class="card__edit" href="{{ route('admin.konten') }}#bebas_teori">Edit</a>
        </div>
        <div class="card__body" data-isi>
            <ul class="items">
                @foreach ($konten['bebas_teori'] as $poin)
                    <li><x-icon name="check" /><span>{{ $poin }}</span></li>
                @endforeach
            </ul>
        </div>
        <button class="more" type="button" data-selengkapnya hidden>
            Lihat selengkapnya
            <x-icon name="arrow-down" :size="14" />
        </button>
    </article>

</section>

{{-- Pop-up isi lengkap kartu --}}
<dialog class="dlg-detail" id="dlg-detail" aria-labelledby="detail-judul">
    <div class="dlg-detail__head">
        <h3 id="detail-judul"></h3>
        <button type="button" class="dlg-detail__x" data-close aria-label="Tutup"><x-icon name="close" :size="20" /></button>
    </div>
    <div class="dlg-detail__body" id="detail-isi"></div>
</dialog>
@endsection

@push('scripts')
<script>
(function () {
    var kartu = Array.prototype.slice.call(document.querySelectorAll('[data-kartu]'));
    var dlg = document.getElementById('dlg-detail');
    var judul = document.getElementById('detail-judul');
    var isi = document.getElementById('detail-isi');

    var aksen = { blue: '#2b72d5', sky: '#5db3e8', green: '#16a34a' };

    /* Tampilkan "Lihat selengkapnya" hanya pada kartu yang isinya terpotong */
    function periksa() {
        kartu.forEach(function (k) {
            var badan = k.querySelector('[data-isi]');
            var tombol = k.querySelector('[data-selengkapnya]');
            var terpotong = badan.scrollHeight > badan.clientHeight + 1;
            badan.classList.toggle('is-clipped', terpotong);
            tombol.hidden = !terpotong;
        });
    }

    periksa();
    window.addEventListener('load', periksa);
    window.addEventListener('resize', periksa);
    if (document.fonts && document.fonts.ready) {
        document.fonts.ready.then(periksa);
    }

    document.addEventListener('click', function (e) {
        var buka = e.target.closest('[data-selengkapnya]');
        if (buka) {
            var k = buka.closest('[data-kartu]');
            judul.textContent = k.dataset.judul;
            isi.innerHTML = k.querySelector('[data-isi]').innerHTML;
            /* bawa warna aksen kartu asal ke dalam pop-up */
            dlg.style.setProperty('--accent', aksen[k.dataset.warna] || aksen.sky);
            dlg.showModal();
            return;
        }

        if (e.target.closest('[data-close]') || e.target === dlg) {
            dlg.close();
        }
    });
})();
</script>
@endpush