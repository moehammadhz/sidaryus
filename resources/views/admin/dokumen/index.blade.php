@extends('layouts.admin')

@section('title', 'Verifikasi Dokumen Mahasiswa')
@section('crumb', 'Dokumen Mahasiswa')
@section('page_title', 'Verifikasi Dokumen Mahasiswa')

@push('styles')
<style>
    .panel { background: #fff; border: 1px solid var(--line); border-radius: 16px; padding: 24px; }
    .alert { margin-bottom: 16px; padding: 12px 16px; border-radius: 12px; background: #dcfce7; color: #166534; font-size: 14px; font-weight: 600; }
    .alert--error { background: #fee2e2; color: #991b1b; }

    .filters { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; padding: 16px; background: #f6f8fb; border: 1px solid var(--line); border-radius: 14px; }
    .search__box {
        display: flex; align-items: center; gap: 8px; height: 42px; flex: 1 1 220px; min-width: 200px;
        padding: 0 12px; border: 1.5px solid #d5dbe5; border-radius: 10px; background: #fff; color: #9aa1ad;
    }
    .search__box:focus-within, .select:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(43,114,213,.15); }
    .search__box input { flex: 1; min-width: 0; border: 0; outline: 0; background: transparent; font: 500 14px var(--font); color: var(--ink); }
    .select {
        height: 42px; padding: 0 12px; border: 1.5px solid #d5dbe5; border-radius: 10px; background: #fff;
        font: 500 14px var(--font); color: var(--ink); outline: 0; cursor: pointer; min-width: 140px;
    }
    .filters .btn, .tools .btn { height: 42px; }

    .tools { margin-top: 14px; display: flex; gap: 10px; flex-wrap: wrap; }
    .btn--excel { background: #15803d; color: #fff; }
    .btn--excel:hover { background: #166534; }

    .table-wrap { margin-top: 18px; overflow-x: auto; border: 1px solid var(--line); border-radius: 14px; }
    table { width: 100%; min-width: 1020px; border-collapse: collapse; font-size: 13.5px; }
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
    .nama { font-weight: 700; }
    .nim { display: inline-block; padding: 2px 8px; border-radius: 6px; background: #f1f4f9; font: 600 12.5px ui-monospace, Consolas, monospace; }
    .pill { display: inline-block; padding: 3px 10px; border-radius: 8px; background: #f1f4f9; font-size: 12px; font-weight: 600; color: #374151; }
    .waktu { color: var(--primary); font-weight: 600; white-space: nowrap; }
    .muted { color: #9aa1ad; }

    /* Bilah progress memakai elemen progress */
    .prog { min-width: 130px; }
    .prog__bar {
        display: block; width: 100%; height: 6px; border: 0; border-radius: 999px; overflow: hidden;
        appearance: none; -webkit-appearance: none; background: #e8edf4; color: var(--primary);
    }
    .prog__bar::-webkit-progress-bar   { background: #e8edf4; border-radius: 999px; }
    .prog__bar::-webkit-progress-value { background: var(--primary); border-radius: 999px; }
    .prog__bar::-moz-progress-bar      { background: var(--primary); border-radius: 999px; }
    .prog--full .prog__bar { color: #16a34a; }
    .prog--full .prog__bar::-webkit-progress-value { background: #16a34a; }
    .prog--full .prog__bar::-moz-progress-bar      { background: #16a34a; }
    .prog__txt { margin-top: 6px; font-size: 12px; font-weight: 700; color: #374151; }

    .icon-btn { width: 38px; height: 38px; display: grid; place-items: center; border: 0; border-radius: 10px; background: var(--primary); color: #fff; cursor: pointer; }
    .icon-btn:hover { background: var(--primary-dark); }
    .empty { padding: 40px 16px; text-align: center; color: var(--muted); }

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

    /* ---------- Pop-up verifikasi ---------- */
    dialog { border: 0; border-radius: 18px; padding: 0; box-shadow: 0 24px 60px rgba(10,37,99,.28); }
    dialog::backdrop { background: rgba(10,37,99,.45); }
    .dlg--verif { width: min(680px, calc(100vw - 32px)); max-height: 86vh; }
    .dlg--verif[open] { display: flex; flex-direction: column; }
    .dlg__head { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 20px 24px; border-bottom: 1px solid var(--line); }
    .dlg__head h3 { font-size: 18px; font-weight: 800; letter-spacing: -.01em; color: var(--ink); }
    .dlg__meta { margin-top: 2px; font-size: 12.5px; color: var(--muted); }
    .dlg__x { width: 36px; height: 36px; display: grid; place-items: center; border: 0; border-radius: 10px; background: transparent; color: #6b7280; cursor: pointer; flex: none; }
    .dlg__x:hover { background: #f1f4f9; color: var(--ink); }
    .dlg__body { padding: 18px 24px; overflow-y: auto; display: grid; gap: 12px; background: #f9fafc; }
    .dlg__foot { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; padding: 16px 24px; border-top: 1px solid var(--line); }
    .dlg__foot form { display: inline; }
    .dlg__link { font-size: 13px; font-weight: 600; color: var(--primary); text-decoration: none; }
    .dlg__link:hover { text-decoration: underline; }
    .btn--ok { background: #16a34a; color: #fff; }
    .btn--ok:hover { background: #15803d; }

    .doc { display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 14px 16px; background: #fff; border: 1px solid var(--line); border-radius: 12px; }
    .doc__main { min-width: 0; }
    .doc__nama { font-size: 14.5px; font-weight: 700; color: var(--ink); line-height: 1.35; }
    .doc__info { margin-top: 2px; font-size: 12px; color: var(--muted); }
    .doc__catatan { margin-top: 8px; padding: 6px 10px; border-radius: 8px; background: #fef2f2; color: #991b1b; font-size: 12.5px; }
    .doc__aksi { display: flex; gap: 8px; flex: none; }
    .doc__aksi form { display: inline; }
    .doc--kosong { background: transparent; border-style: dashed; }
    .doc--kosong .doc__nama { color: #6b7280; font-weight: 600; }

    .badge { display: inline-block; margin-top: 8px; padding: 4px 10px; border-radius: 6px; font-size: 11.5px; font-weight: 800; letter-spacing: .02em; text-transform: uppercase; }
    .badge--menunggu  { background: #fef3c7; color: #92400e; }
    .badge--disetujui { background: #dcfce7; color: #166534; }
    .badge--ditolak   { background: #fee2e2; color: #991b1b; }
    .badge--belum     { background: #f1f4f9; color: #6b7280; }

    .ibtn { width: 40px; height: 40px; display: grid; place-items: center; border: 0; border-radius: 10px; color: #fff; cursor: pointer; text-decoration: none; }
    .ibtn--view { background: #64748b; }
    .ibtn--view:hover { background: #475569; }
    .ibtn--ok { background: #22c55e; }
    .ibtn--ok:hover { background: #16a34a; }
    .ibtn--no { background: #ef4444; }
    .ibtn--no:hover { background: #dc2626; }
    .ibtn:disabled { opacity: .35; cursor: default; }

    /* Pop-up alasan penolakan */
    .dlg--tolak { width: min(460px, calc(100vw - 32px)); }
    .dlg--tolak form { padding: 24px; display: grid; gap: 14px; }
    .dlg--tolak h3 { font-size: 18px; font-weight: 700; color: var(--navy); }
    .dlg--tolak p { font-size: 14px; line-height: 1.5; color: #374151; }
    .dlg--tolak label { font-size: 13px; font-weight: 600; color: #1f2937; }
    .dlg--tolak textarea {
        width: 100%; min-height: 110px; resize: vertical; margin-top: 6px; padding: 12px 14px;
        border: 1.5px solid #d5dbe5; border-radius: 12px; background: #f6f8fb;
        font: 500 14px/1.5 var(--font); color: var(--ink); outline: 0;
    }
    .dlg--tolak textarea:focus { border-color: var(--primary); background: #fff; box-shadow: 0 0 0 3px rgba(43,114,213,.15); }
    .dlg-actions { display: flex; justify-content: flex-end; gap: 10px; }

    @media (max-width: 720px) {
        .panel { padding: 16px; }
        .filters > * { flex: 1 1 100%; }
        .doc { flex-direction: column; align-items: stretch; }
        .doc__aksi { justify-content: flex-end; }
        .dlg__head, .dlg__body, .dlg__foot { padding-left: 16px; padding-right: 16px; }
    }
</style>
@endpush

@section('content')
@php
    $kolom = [
        ['name', 'Mahasiswa'], ['username', 'NIM'], ['program_studi', 'Prodi'], ['angkatan', 'Angkatan'],
        ['kelas', 'Kelas'], ['timestamp', 'Timestamp'], ['progress', 'Progress'],
    ];
    $labelStatus = ['menunggu' => 'Menunggu', 'disetujui' => 'Disetujui', 'ditolak' => 'Ditolak'];
    $adaFilter = collect($f)->filter(fn ($v) => $v !== '')->isNotEmpty();
    $paramEkspor = array_filter($f + ['sort' => $sort, 'dir' => $dir], fn ($v) => $v !== '');
    $halaman = $mahasiswa->currentPage();
    $mulai   = max(1, $halaman - 2);
    $selesai = min($mahasiswa->lastPage(), $halaman + 2);
@endphp

    {{-- Data untuk skrip halaman ini, dibaca lewat atribut data --}}
    <div id="page-data" hidden data-modal="{{ session('modal') }}"></div>

    @if (session('status'))
        <div class="alert" role="status">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert--error" role="alert">{{ $errors->first() }}</div>
    @endif

    <section class="panel">

        <form class="filters" method="GET" action="{{ route('admin.dokumen.index') }}" role="search">
            <input type="hidden" name="sort" value="{{ $sort }}">
            <input type="hidden" name="dir" value="{{ $dir }}">

            <label class="search__box">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
                <input type="search" name="q" value="{{ $f['q'] }}" placeholder="Nama/NIM..." aria-label="Cari nama atau NIM">
            </label>

            <select class="select" name="prodi" aria-label="Program studi">
                <option value="">-- Prodi --</option>
                @foreach (\App\Models\User::PROGRAM_STUDI as $p)
                    <option value="{{ $p }}" @selected($f['prodi'] === $p)>{{ $p }}</option>
                @endforeach
            </select>

            <select class="select" name="angkatan" aria-label="Angkatan">
                <option value="">-- Angkatan --</option>
                @foreach ($angkatan as $a)
                    <option value="{{ $a }}" @selected((string) $f['angkatan'] === (string) $a)>{{ $a }}</option>
                @endforeach
            </select>

            <button class="btn btn--primary" type="submit">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M3 4h18l-7 8v6l-4 2v-8z"/></svg>
                Filter
            </button>
            @if ($adaFilter)
                <a class="btn btn--ghost" href="{{ route('admin.dokumen.index') }}">Reset</a>
            @endif
        </form>

        <div class="tools">
            <a class="btn btn--excel" href="{{ route('admin.dokumen.ekspor', $paramEkspor) }}">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 4v11M7 11l5 5 5-5M5 20h14"/></svg>
                Export Excel
            </a>
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
                        <th scope="col"><span class="th-plain">Aksi</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($mahasiswa as $m)
                        @php
                            $ok = (int) $m->disetujui_count;
                        @endphp
                        <tr>
                            <td class="nama">{{ $m->name }}</td>
                            <td><span class="nim">{{ $m->username }}</span></td>
                            <td>{{ $m->program_studi ?? '-' }}</td>
                            <td>{{ $m->angkatan ?? '-' }}</td>
                            <td>{{ $m->kelas ?? '-' }}</td>
                            <td>
                                @if ($m->dokumen_max_diunggah_at)
                                    <span class="waktu">{{ \Illuminate\Support\Carbon::parse($m->dokumen_max_diunggah_at)->format('d/m/y H:i') }}</span>
                                @else
                                    <span class="muted">-</span>
                                @endif
                            </td>
                            <td>
                                <div class="prog @if ($totalSyarat > 0 && $ok >= $totalSyarat) prog--full @endif">
                                    <progress class="prog__bar" value="{{ $ok }}" max="{{ max($totalSyarat, 1) }}"
                                              aria-label="Progress dokumen {{ $m->name }}"></progress>
                                    <div class="prog__txt">{{ $ok }} / {{ $totalSyarat }}</div>
                                </div>
                            </td>
                            <td>
                                <button type="button" class="icon-btn" data-buka="dlg-mhs-{{ $m->id }}"
                                        aria-label="Verifikasi dokumen {{ $m->name }}" title="Verifikasi dokumen">
                                    <x-icon name="clipboard" :size="18" />
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="empty">
                                {{ $adaFilter ? 'Tidak ada mahasiswa yang cocok dengan filter.' : 'Belum ada mahasiswa terdaftar.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="footer">
            <div>
                @if ($mahasiswa->total() > 0)
                    Data {{ $mahasiswa->firstItem() }} - {{ $mahasiswa->lastItem() }} dari {{ $mahasiswa->total() }}
                @else
                    Tidak ada data
                @endif
            </div>

            @if ($mahasiswa->hasPages())
                <nav class="pager" aria-label="Halaman">
                    @if ($mahasiswa->onFirstPage())
                        <span class="is-disabled" aria-hidden="true">&lt;&lt;</span>
                    @else
                        <a href="{{ $mahasiswa->previousPageUrl() }}" aria-label="Halaman sebelumnya">&lt;&lt;</a>
                    @endif

                    @for ($h = $mulai; $h <= $selesai; $h++)
                        @if ($h === $halaman)
                            <span class="is-current" aria-current="page">{{ $h }}</span>
                        @else
                            <a href="{{ $mahasiswa->url($h) }}">{{ $h }}</a>
                        @endif
                    @endfor

                    @if ($mahasiswa->hasMorePages())
                        <a href="{{ $mahasiswa->nextPageUrl() }}" aria-label="Halaman berikutnya">&gt;&gt;</a>
                    @else
                        <span class="is-disabled" aria-hidden="true">&gt;&gt;</span>
                    @endif
                </nav>
            @endif
        </div>
    </section>

    {{-- Satu pop-up verifikasi per mahasiswa --}}
    @foreach ($mahasiswa as $m)
        @php
            $berkas   = $m->dokumen->keyBy('persyaratan_id');
            $menunggu = $m->dokumen->where('status', 'menunggu')->count();
        @endphp
        <dialog class="dlg--verif" id="dlg-mhs-{{ $m->id }}" aria-labelledby="judul-mhs-{{ $m->id }}">
            <div class="dlg__head">
                <div>
                    <h3 id="judul-mhs-{{ $m->id }}">Verifikasi: {{ $m->name }}</h3>
                    <div class="dlg__meta">NIM {{ $m->username }} · {{ $m->program_studi ?? '-' }}</div>
                </div>
                <button type="button" class="dlg__x" data-close aria-label="Tutup"><x-icon name="close" :size="20" /></button>
            </div>

            <div class="dlg__body">
                @forelse ($syarat as $s)
                    @php
                        $d = $berkas->get($s->id);
                    @endphp
                    @if ($d)
                        <div class="doc">
                            <div class="doc__main">
                                <div class="doc__nama">{{ $s->nama }}</div>
                                <div class="doc__info">{{ $d->nama_asli }} · {{ $d->ukuranLabel() }} · diunggah {{ $d->diunggah_at->format('d/m/y H:i') }}</div>
                                <span class="badge badge--{{ $d->status }}">
                                    {{ $labelStatus[$d->status] ?? $d->status }}@if ($d->status !== 'menunggu' && $d->verifikator) (Oleh: {{ $d->verifikator->username }})@endif
                                </span>
                                @if ($d->status === 'ditolak' && $d->catatan)
                                    <div class="doc__catatan">Alasan: {{ $d->catatan }}</div>
                                @endif
                            </div>
                            <div class="doc__aksi">
                                <a class="ibtn ibtn--view" href="{{ route('admin.dokumen.berkas', $d) }}" target="_blank" rel="noopener"
                                   title="Lihat berkas" aria-label="Lihat berkas {{ $s->kode }}"><x-icon name="eye" :size="19" /></a>

                                <form method="POST" action="{{ route('admin.dokumen.setuju', $d) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button class="ibtn ibtn--ok" type="submit" title="Setujui" aria-label="Setujui {{ $s->kode }}"
                                            @disabled($d->status === 'disetujui')><x-icon name="tick" :size="20" /></button>
                                </form>

                                <button class="ibtn ibtn--no" type="button" data-tolak
                                        data-url="{{ route('admin.dokumen.tolak', $d) }}"
                                        data-label="{{ $s->kode }} - {{ $s->nama }}"
                                        title="Tolak" aria-label="Tolak {{ $s->kode }}"
                                        @disabled($d->status === 'ditolak')><x-icon name="close" :size="20" /></button>
                            </div>
                        </div>
                    @else
                        <div class="doc doc--kosong">
                            <div class="doc__main">
                                <div class="doc__nama">{{ $s->nama }}</div>
                                <span class="badge badge--belum">Belum diunggah</span>
                            </div>
                        </div>
                    @endif
                @empty
                    <p class="muted">Belum ada persyaratan. Tambahkan lewat menu Kelola Persyaratan.</p>
                @endforelse
            </div>

            <div class="dlg__foot">
                <a class="dlg__link" href="{{ route('admin.dokumen.show', $m) }}">Buka halaman lengkap</a>
                <div>
                    @if ($menunggu > 0)
                        <form method="POST" action="{{ route('admin.dokumen.setuju-semua', $m) }}">
                            @csrf
                            <button class="btn btn--ok" type="submit"><x-icon name="check" :size="16" /> Setujui semua yang menunggu ({{ $menunggu }})</button>
                        </form>
                    @endif
                    <button type="button" class="btn btn--ghost" data-close>Tutup</button>
                </div>
            </div>
        </dialog>
    @endforeach

    {{-- Pop-up alasan penolakan, dipakai bersama --}}
    <dialog class="dlg--tolak" id="dlg-tolak" aria-labelledby="tolak-title">
        <form method="POST" id="form-tolak">
            @csrf
            @method('PATCH')
            <h3 id="tolak-title">Tolak dokumen</h3>
            <p>Dokumen <strong id="tolak-label"></strong> akan ditolak. Alasan ini akan terlihat oleh mahasiswa.</p>
            <div>
                <label for="tolak-catatan">Alasan penolakan</label>
                <textarea id="tolak-catatan" name="catatan" maxlength="255" required placeholder="Contoh: Berkas buram, mohon unggah ulang."></textarea>
            </div>
            <div class="dlg-actions">
                <button type="button" class="btn btn--ghost" data-close>Batal</button>
                <button type="submit" class="btn btn--danger">Tolak dokumen</button>
            </div>
        </form>
    </dialog>
@endsection

@push('scripts')
<script>
(function () {
    var data = document.getElementById('page-data').dataset;
    var tolak = document.getElementById('dlg-tolak');
    var form = document.getElementById('form-tolak');
    var label = document.getElementById('tolak-label');
    var area = document.getElementById('tolak-catatan');

    /* Filter langsung berjalan begitu pilihan dropdown diganti */
    document.querySelectorAll('.filters select').forEach(function (pilihan) {
        pilihan.addEventListener('change', function () {
            pilihan.form.submit();
        });
    });

    document.addEventListener('click', function (e) {
        var buka = e.target.closest('[data-buka]');
        if (buka) {
            document.getElementById(buka.dataset.buka).showModal();
            return;
        }

        var tombolTolak = e.target.closest('[data-tolak]');
        if (tombolTolak && !tombolTolak.disabled) {
            form.action = tombolTolak.dataset.url;
            label.textContent = tombolTolak.dataset.label;
            area.value = '';
            tolak.showModal();
            area.focus();
            return;
        }

        var tutup = e.target.closest('[data-close]');
        if (tutup) {
            tutup.closest('dialog').close();
            return;
        }

        /* Klik di area gelap di luar kotak menutup pop-up teratas */
        if (e.target.tagName === 'DIALOG') {
            e.target.close();
        }
    });

    /* Buka kembali pop-up mahasiswa yang baru diverifikasi */
    if (data.modal) {
        var dlg = document.getElementById('dlg-mhs-' + data.modal);
        if (dlg) dlg.showModal();
    }
})();
</script>
@endpush