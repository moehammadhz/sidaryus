@extends('layouts.admin')

@section('title', 'Verifikasi Dokumen ' . $mahasiswa->name)
@section('crumb', 'Dokumen Mahasiswa')
@section('page_title', 'Verifikasi Dokumen')

@section('actions')
    <a class="btn btn--ghost" href="{{ route('admin.dokumen.index') }}">Kembali ke daftar</a>
@endsection

@push('styles')
<style>
    .alert { margin-bottom: 16px; padding: 12px 16px; border-radius: 12px; background: #dcfce7; color: #166534; font-size: 14px; font-weight: 600; }
    .alert--error { background: #fee2e2; color: #991b1b; }
    .panel { background: #fff; border: 1px solid var(--line); border-radius: 16px; padding: 24px; }

    .profile-card { display: flex; align-items: center; gap: 20px; flex-wrap: wrap; }
    .avatar { width: 56px; height: 56px; border-radius: 50%; background: var(--primary); color: #fff; display: grid; place-items: center; font-size: 22px; font-weight: 800; flex: none; }
    .profile-card__info { flex: 1 1 280px; min-width: 0; }
    .profile-card h2 { font-size: 20px; font-weight: 800; letter-spacing: -.02em; color: var(--navy); }
    .meta { margin-top: 6px; display: flex; gap: 8px; flex-wrap: wrap; font-size: 13px; color: #374151; }
    .meta span { padding: 3px 10px; border-radius: 8px; background: #f1f4f9; font-weight: 600; }

    /* Bilah progress: elemen progress, tanpa Blade di dalam atribut style */
    .prog { flex: 0 0 240px; }
    .prog__bar {
        display: block; width: 100%; height: 8px; border: 0; border-radius: 999px; overflow: hidden;
        appearance: none; -webkit-appearance: none; background: #e8edf4; color: var(--primary);
    }
    .prog__bar::-webkit-progress-bar   { background: #e8edf4; border-radius: 999px; }
    .prog__bar::-webkit-progress-value { background: var(--primary); border-radius: 999px; }
    .prog__bar::-moz-progress-bar      { background: var(--primary); border-radius: 999px; }
    .prog--full .prog__bar { color: #16a34a; }
    .prog--full .prog__bar::-webkit-progress-value { background: #16a34a; }
    .prog--full .prog__bar::-moz-progress-bar      { background: #16a34a; }
    .prog__txt { margin-top: 8px; font-size: 13px; font-weight: 700; color: #374151; }

    .section-gap { margin-top: 16px; }
    .bar { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
    .bar h3 { font-size: 17px; font-weight: 700; color: var(--navy); }
    .btn--ok { background: #16a34a; color: #fff; }
    .btn--ok:hover { background: #15803d; }

    .table-wrap { margin-top: 14px; overflow-x: auto; border: 1px solid var(--line); border-radius: 14px; }
    table { width: 100%; min-width: 860px; border-collapse: collapse; font-size: 13.5px; }
    thead th { height: 46px; padding: 0 14px; background: #f6f8fb; text-align: left; font-size: 12px; font-weight: 700; letter-spacing: .03em; text-transform: uppercase; color: #374151; }
    tbody td { padding: 14px; border-top: 1px solid var(--line); vertical-align: top; color: #1f2937; line-height: 1.45; }
    .col-no { width: 56px; }
    .col-kode { width: 110px; }
    .col-status { width: 130px; }
    .col-aksi { width: 190px; }
    .kode { display: inline-block; padding: 3px 10px; border-radius: 8px; background: #e3eefc; color: #1d4ed8; font-size: 12.5px; font-weight: 700; }
    .berkas__nama { font-weight: 600; word-break: break-all; }
    .berkas__info { margin-top: 2px; font-size: 12.5px; color: var(--muted); }
    .berkas a { display: inline-block; margin-top: 6px; font-size: 13px; font-weight: 600; color: var(--primary); text-decoration: none; }
    .berkas a:hover { text-decoration: underline; }
    .catatan { margin-top: 8px; padding: 8px 10px; border-radius: 8px; background: #fef2f2; color: #991b1b; font-size: 12.5px; }
    .belum { color: #9aa1ad; }
    .kosong { padding: 36px; text-align: center; color: var(--muted); }

    .badge { display: inline-block; padding: 4px 10px; border-radius: 999px; font-size: 11px; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; white-space: nowrap; }
    .badge--menunggu  { background: #fef3c7; color: #92400e; }
    .badge--disetujui { background: #dcfce7; color: #166534; }
    .badge--ditolak   { background: #fee2e2; color: #991b1b; }
    .badge--belum     { background: #f1f4f9; color: #6b7280; }

    .aksi { display: flex; gap: 8px; flex-wrap: wrap; }
    .aksi form { display: inline; }
    .aksi .btn { height: 34px; padding: 0 14px; font-size: 12.5px; }

    dialog { border: 0; border-radius: 18px; padding: 0; width: min(460px, calc(100vw - 32px)); box-shadow: 0 24px 60px rgba(10,37,99,.28); }
    dialog::backdrop { background: rgba(10,37,99,.45); }
    dialog form { padding: 24px; display: grid; gap: 14px; }
    dialog h3 { font-size: 18px; font-weight: 700; color: var(--navy); }
    dialog p { font-size: 14px; line-height: 1.5; color: #374151; }
    dialog label { font-size: 13px; font-weight: 600; color: #1f2937; }
    dialog textarea {
        width: 100%; min-height: 110px; resize: vertical; margin-top: 6px; padding: 12px 14px;
        border: 1.5px solid #d5dbe5; border-radius: 12px; background: #f6f8fb;
        font: 500 14px/1.5 var(--font); color: var(--ink); outline: 0;
    }
    dialog textarea:focus { border-color: var(--primary); background: #fff; box-shadow: 0 0 0 3px rgba(43,114,213,.15); }
    .dlg-actions { display: flex; justify-content: flex-end; gap: 10px; }
</style>
@endpush

@section('content')
@php
    $total = $syarat->count();
    $labelStatus = ['menunggu' => 'Menunggu', 'disetujui' => 'Disetujui', 'ditolak' => 'Ditolak'];
@endphp

    @if (session('status'))
        <div class="alert" role="status">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert--error" role="alert">{{ $errors->first() }}</div>
    @endif

    <section class="panel profile-card">
        <div class="avatar">{{ mb_strtoupper(mb_substr($mahasiswa->name, 0, 1)) }}</div>
        <div class="profile-card__info">
            <h2>{{ $mahasiswa->name }}</h2>
            <div class="meta">
                <span>NIM {{ $mahasiswa->username }}</span>
                <span>{{ $mahasiswa->program_studi ?? '-' }}</span>
                <span>Angkatan {{ $mahasiswa->angkatan ?? '-' }}</span>
                <span>Kelas {{ $mahasiswa->kelas ?? '-' }}</span>
            </div>
        </div>
        <div class="prog @if ($total > 0 && $disetujui >= $total) prog--full @endif">
            <progress class="prog__bar" value="{{ $disetujui }}" max="{{ max($total, 1) }}"
                      aria-label="Progress verifikasi dokumen"></progress>
            <div class="prog__txt">{{ $disetujui }} / {{ $total }} dokumen terverifikasi</div>
        </div>
    </section>

    <section class="panel section-gap">
        <div class="bar">
            <h3>Daftar Dokumen</h3>
            @if ($menunggu > 0)
                <form method="POST" action="{{ route('admin.dokumen.setuju-semua', $mahasiswa) }}"
                      onsubmit="return confirm('Setujui semua dokumen yang menunggu ({{ $menunggu }} dokumen)?')">
                    @csrf
                    <button class="btn btn--ok" type="submit"><x-icon name="check" :size="16" /> Setujui semua yang menunggu ({{ $menunggu }})</button>
                </form>
            @endif
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th scope="col" class="col-no">No</th>
                        <th scope="col" class="col-kode">Kode</th>
                        <th scope="col">Dokumen</th>
                        <th scope="col">Berkas</th>
                        <th scope="col" class="col-status">Status</th>
                        <th scope="col" class="col-aksi">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($syarat as $i => $s)
                        @php($d = $dokumen->get($s->id))
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td><span class="kode">{{ $s->kode }}</span></td>
                            <td>{{ $s->nama }}</td>
                            <td class="berkas">
                                @if ($d)
                                    <div class="berkas__nama">{{ $d->nama_asli }}</div>
                                    <div class="berkas__info">{{ $d->ukuranLabel() }} · diunggah {{ $d->diunggah_at->format('d/m/y H:i') }}</div>
                                    <a href="{{ route('admin.dokumen.berkas', $d) }}" target="_blank" rel="noopener">Lihat berkas</a>
                                    @if ($d->status === 'ditolak' && $d->catatan)
                                        <div class="catatan">Alasan: {{ $d->catatan }}</div>
                                    @endif
                                @else
                                    <span class="belum">Belum diunggah</span>
                                @endif
                            </td>
                            <td>
                                @if ($d)
                                    <span class="badge badge--{{ $d->status }}">{{ $labelStatus[$d->status] ?? $d->status }}</span>
                                @else
                                    <span class="badge badge--belum">Belum ada</span>
                                @endif
                            </td>
                            <td>
                                @if ($d)
                                    <div class="aksi">
                                        @if ($d->status !== 'disetujui')
                                            <form method="POST" action="{{ route('admin.dokumen.setuju', $d) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button class="btn btn--primary" type="submit">Setujui</button>
                                            </form>
                                        @endif
                                        @if ($d->status !== 'ditolak')
                                            <button class="btn btn--danger" type="button" data-tolak
                                                    data-url="{{ route('admin.dokumen.tolak', $d) }}"
                                                    data-label="{{ $s->kode }} - {{ $s->nama }}">Tolak</button>
                                        @endif
                                    </div>
                                @else
                                    <span class="belum">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="kosong">Belum ada persyaratan. Tambahkan lewat menu Kelola Persyaratan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <dialog id="dlg-tolak" aria-labelledby="tolak-title">
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
    var dlg = document.getElementById('dlg-tolak');
    var form = document.getElementById('form-tolak');
    var label = document.getElementById('tolak-label');
    var area = document.getElementById('tolak-catatan');

    document.addEventListener('click', function (e) {
        var open = e.target.closest('[data-tolak]');
        if (open) {
            form.action = open.dataset.url;
            label.textContent = open.dataset.label;
            area.value = '';
            dlg.showModal();
            area.focus();
            return;
        }
        if (e.target === dlg || e.target.closest('[data-close]')) {
            dlg.close();
        }
    });
})();
</script>
@endpush