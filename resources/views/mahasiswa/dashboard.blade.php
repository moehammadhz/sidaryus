@extends('layouts.mahasiswa')

@section('title', 'Dashboard')
@section('crumb', 'Dashboard')
@section('page_title', 'Dashboard')

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
    .banner h2 { font-size: 26px; font-weight: 700; letter-spacing: -.02em; }
    .chips { margin-top: 10px; display: flex; gap: 8px; flex-wrap: wrap; }
    .chip { padding: 5px 12px; border-radius: 999px; background: rgba(255,255,255,.2); font-size: 12.5px; font-weight: 600; }

    .alert { margin-top: 16px; padding: 12px 16px; border-radius: 12px; background: #fee2e2; color: #991b1b; font-size: 14px; font-weight: 600; }

    .stats { margin-top: 20px; display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; }
    .stat { background: #fff; border: 1px solid var(--line); border-radius: 16px; padding: 18px 20px; display: flex; align-items: center; gap: 16px; min-width: 0; }
    .stat__icon { width: 46px; height: 46px; border-radius: 12px; display: grid; place-items: center; flex: none; }
    .stat__num { font-size: 26px; font-weight: 800; letter-spacing: -.02em; line-height: 1; }
    .stat__num--text { font-size: 17px; line-height: 1.2; letter-spacing: -.01em; }
    .stat__label { margin-top: 4px; font-size: 12.5px; color: var(--muted); }
    .tone-blue  { background: #e3eefc; color: #2b72d5; }
    .tone-amber { background: #fef3c7; color: #b45309; }
    .tone-sky   { background: #e0f2fe; color: #0369a1; }
    .tone-green { background: #dcfce7; color: #15803d; }
    .tone-red   { background: #fee2e2; color: #b91c1c; }

    .flash { margin-top: 16px; padding: 12px 16px; border-radius: 12px; font-size: 14px; font-weight: 600; background: #dcfce7; color: #166534; }
    .flash--error { background: #fee2e2; color: #991b1b; }
    .panel { margin-top: 20px; background: #fff; border: 1px solid var(--line); border-radius: 16px; padding: 22px 24px; }
    .panel__head { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 8px; margin-bottom: 16px; }
    .panel__head h3 { font-size: 17px; font-weight: 700; color: var(--navy); }
    .steps { list-style: none; display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); }
    .step { position: relative; padding-right: 10px; display: flex; flex-direction: column; gap: 6px; }
    .step::before { content: ""; position: absolute; top: 15px; left: 32px; right: 0; height: 2px; background: var(--line); }
    .step:last-child::before { display: none; }
    .step__dot { position: relative; z-index: 1; width: 32px; height: 32px; border-radius: 50%; display: grid; place-items: center; font-size: 13px; font-weight: 800; background: #f1f4f9; color: #6b7280; border: 2px solid var(--line); }
    .step--selesai .step__dot { background: #16a34a; border-color: #16a34a; color: #fff; }
    .step--sekarang .step__dot { background: var(--primary); border-color: var(--primary); color: #fff; box-shadow: 0 0 0 4px #e3eefc; }
    .step--gagal .step__dot { background: #dc2626; border-color: #dc2626; color: #fff; }
    .step__judul { font-size: 13.5px; font-weight: 700; color: var(--navy); }
    .step__ket { font-size: 12.5px; color: var(--muted); line-height: 1.4; }
    .yud { display: flex; flex-wrap: wrap; gap: 12px 18px; align-items: center; }
    .yud__nama { font-size: 18px; font-weight: 800; color: var(--navy); }
    .yud__meta { font-size: 13px; color: var(--muted); }
    .yud form { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; }
    .yud select { height: 42px; min-width: 260px; max-width: 100%; padding: 0 12px; border: 1.5px solid #d5dbe5; border-radius: 10px; background: #f6f8fb; font: 500 14px var(--font); }
    .muted-text { font-size: 14px; color: var(--muted); line-height: 1.5; }
    .sr-only { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap; }
    .pill-ok { display: inline-block; padding: 4px 10px; border-radius: 999px; background: #dcfce7; color: #166534; font-size: 12px; font-weight: 700; }
    @media (max-width: 900px) {
        .steps { grid-template-columns: 1fr; gap: 14px; }
        .step { flex-direction: row; align-items: center; gap: 12px; padding: 0; }
        .step::before { display: none; }
    }

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

    dialog.dlg-detail { border: 0; border-radius: 18px; padding: 0; width: min(560px, calc(100vw - 32px)); box-shadow: 0 24px 60px rgba(10,37,99,.28); }
    dialog.dlg-detail::backdrop { background: rgba(10,37,99,.45); }
    dialog.dlg-detail[open] { display: flex; flex-direction: column; }
    .dlg-detail__head { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 18px 24px; border-bottom: 1px solid var(--line); }
    .dlg-detail__head h3 { font-size: 18px; font-weight: 800; letter-spacing: -.01em; color: var(--navy); }
    .dlg-detail__x { width: 36px; height: 36px; display: grid; place-items: center; border: 0; border-radius: 10px; background: transparent; color: #6b7280; cursor: pointer; flex: none; }
    .dlg-detail__x:hover { background: #f1f4f9; color: var(--ink); }
    .dlg-detail__body { padding: 4px 24px 24px; overflow-y: auto; }

    @media (max-width: 1200px) { .stats { grid-template-columns: repeat(2, 1fr); } .cards { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 1000px) { .banner { padding: 24px; } }
    @media (max-width: 700px)  { .stats, .cards { grid-template-columns: 1fr; } .card__body { max-height: 360px; } .banner h2 { font-size: 22px; } }
</style>
@endpush

@section('content')
@php
    $r = $ringkasan;
    $kartu = [
        ['Dokumen terverifikasi', $r['disetujui'] . ' / ' . $r['total'], 'file',     'green', false, ''],
        ['Menunggu verifikasi',  (string) $r['menunggu'],               'clipboard', 'amber', false, ''],
        ['Perlu diperbaiki',     (string) $r['ditolak'],                'info',      'red',   false, ''],
        ['Jadwal pendadaran',    $r['jadwal']['label'],                 'calendar',  $r['jadwal']['warna'], true, $r['jadwal']['info']],
    ];
@endphp

<section class="banner">
    <div>
        <h2>Selamat datang, {{ $mahasiswa->name }}!</h2>
        <div class="chips">
            <span class="chip">NIM {{ $mahasiswa->username }}</span>
            @if ($mahasiswa->program_studi) <span class="chip">{{ $mahasiswa->program_studi }}</span> @endif
            @if ($mahasiswa->angkatan) <span class="chip">Angkatan {{ $mahasiswa->angkatan }}</span> @endif
            @if ($mahasiswa->kelas) <span class="chip">Kelas {{ $mahasiswa->kelas }}</span> @endif
            @if ($mahasiswa->periode) <span class="chip">{{ $mahasiswa->periode->nama }}</span> @endif
        </div>
    </div>
</section>

@if (session('status')) <div class="flash" role="status">{{ session('status') }}</div> @endif
@if (session('error')) <div class="flash flash--error" role="alert">{{ session('error') }}</div> @endif

<section class="panel" aria-labelledby="judul-status">
    <div class="panel__head"><h3 id="judul-status">Status Pendadaran</h3></div>
    <ol class="steps">
        @foreach ($langkah as $i => $l)
            <li class="step step--{{ $l['status'] }}" @if ($l['status'] === 'sekarang') aria-current="step" @endif>
                <span class="step__dot" aria-hidden="true">{{ $l['status'] === 'selesai' ? '✓' : ($l['status'] === 'gagal' ? '!' : $i + 1) }}</span>
                <div><div class="step__judul">{{ $l['judul'] }}</div><div class="step__ket">{{ $l['ket'] }}</div></div>
            </li>
        @endforeach
    </ol>
</section>

<section class="panel" aria-labelledby="judul-yud">
    <div class="panel__head">
        <h3 id="judul-yud">Periode Yudisium</h3>
        @if ($mahasiswa->periode) <span class="pill-ok">Terdaftar</span> @endif
    </div>
    @if ($mahasiswa->periode)
        <div class="yud">
            <div>
                <div class="yud__nama">{{ $mahasiswa->periode->nama }}</div>
                <div class="yud__meta">Anda terdaftar sejak {{ $mahasiswa->periode_daftar_at?->locale('id')->translatedFormat('d F Y') }}.
                    Hubungi bagian pengajaran jika periode perlu diubah.</div>
            </div>
        </div>
    @elseif (! $lulus)
        <p class="muted-text">Pendaftaran periode yudisium tersedia setelah Anda dinyatakan lulus ujian pendadaran.
            @if ($periodeBuka->isNotEmpty()) Periode yang sedang dibuka: {{ $periodeBuka->pluck('nama')->join(', ') }}. @endif</p>
    @elseif ($periodeBuka->isEmpty())
        <p class="muted-text">Anda sudah lulus ujian pendadaran. Belum ada periode yudisium yang dibuka; periode akan muncul di sini begitu dibuka oleh bagian pengajaran.</p>
    @else
        <div class="yud">
            <p class="muted-text" style="flex-basis:100%">Selamat, Anda lulus ujian pendadaran dengan nilai {{ \App\Models\JadwalPendadaran::formatNilai($lulus->nilai_akhir) }} ({{ $lulus->hurufAkhir() }}). Pilih periode yudisium yang ingin Anda ikuti. Pastikan pilihan Anda benar; setelah mendaftar, perubahan hanya bisa dilakukan oleh bagian pengajaran.</p>
            <form method="POST" action="{{ route('mahasiswa.periode.daftar') }}">
                @csrf
                <label class="sr-only" for="periode_id">Periode yudisium</label>
                <select id="periode_id" name="periode_id" required>
                    @foreach ($periodeBuka as $p) <option value="{{ $p->id }}">{{ $p->nama }}</option> @endforeach
                </select>
                <button class="btn btn--primary" type="submit">Daftar</button>
            </form>
        </div>
    @endif
</section>

@if ($r['ditolak'] > 0)
    <div class="alert" role="alert">
        Ada {{ $r['ditolak'] }} dokumen yang ditolak. Perbaiki lalu unggah ulang agar proses pendadaran bisa dilanjutkan.
    </div>
@endif

<section class="stats" aria-label="Ringkasan">
    @foreach ($kartu as [$label, $nilai, $ikon, $warna, $teks, $info])
        <div class="stat">
            <div class="stat__icon tone-{{ $warna }}"><x-icon :name="$ikon" :size="22" /></div>
            <div>
                <div class="stat__num @if ($teks) stat__num--text @endif">{{ $nilai }}</div>
                <div class="stat__label">{{ $info !== '' ? $info : $label }}</div>
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

    {{-- Syarat Dokumen --}}
    <article class="card card--sky" id="syarat" data-kartu data-judul="Syarat Dokumen" data-warna="sky">
        <div class="card__head">
            <div class="card__icon tone-sky"><x-icon name="clipboard" :size="20" /></div>
            <h3 class="card__title">Syarat Dokumen</h3>
        </div>
        <div class="card__body" data-isi>
            @if (count($konten['syarat']))
                <ul class="items">
                    @foreach ($konten['syarat'] as $poin)
                        <li><x-icon name="file" /><span>{{ $poin }}</span></li>
                    @endforeach
                </ul>
            @else
                <p class="kosong">Belum ada persyaratan yang ditetapkan.</p>
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