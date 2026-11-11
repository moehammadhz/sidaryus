@extends('layouts.admin')

@section('title', 'Periode Yudisium')
@section('crumb', 'Periode Yudisium')
@section('page_title', 'Periode Yudisium')

@section('actions')
    <a class="btn btn--ghost" href="{{ route('admin.yudisium.index') }}">Data Yudisium</a>
@endsection

@push('styles')
<style>
    .panel { background: #fff; border: 1px solid var(--line); border-radius: 16px; padding: 24px; }
    .panel h2 { font-size: 18px; font-weight: 700; color: var(--navy); }
    .hint { margin-top: 4px; font-size: 13px; color: var(--muted); }
    .alert { margin-bottom: 16px; padding: 12px 16px; border-radius: 12px; background: #dcfce7; color: #166534; font-size: 14px; font-weight: 600; }
    .alert--error { background: #fee2e2; color: #991b1b; }

    .add { margin-top: 20px; display: grid; grid-template-columns: 1fr auto; gap: 12px 16px; align-items: start; }
    .field label { display: block; margin-bottom: 6px; font-size: 13px; font-weight: 600; color: #1f2937; }
    .input {
        width: 100%; height: 46px; padding: 0 14px; border: 1.5px solid #d5dbe5; border-radius: 12px; background: #f6f8fb;
        font: 500 14px var(--font); color: var(--ink); outline: 0;
    }
    .input:focus { border-color: var(--primary); background: #fff; box-shadow: 0 0 0 3px rgba(43,114,213,.15); }
    .input.is-invalid { border-color: var(--danger); }
    .field-error { margin-top: 6px; font-size: 12.5px; color: var(--danger); }
    .add .btn { height: 46px; margin-top: 25px; padding: 0 22px; }

    .list { margin-top: 24px; border: 1px solid var(--line); border-radius: 14px; overflow: hidden; }
    .row { display: grid; grid-template-columns: 50px minmax(0, 1fr) 110px 130px 250px; gap: 12px; align-items: center; padding: 12px 16px; border-top: 1px solid var(--line); font-size: 14px; }
    .row:first-child { border-top: 0; }
    .thead { background: #f6f8fb; font-size: 13px; font-weight: 700; color: #374151; min-height: 46px; }
    .aksi { display: flex; justify-content: flex-end; gap: 4px; }
    .icon-btn { width: 34px; height: 34px; display: grid; place-items: center; border: 0; border-radius: 8px; background: transparent; cursor: pointer; }
    .icon-btn--edit { color: var(--primary); }
    .icon-btn--edit:hover { background: #e3eefc; }
    .icon-btn--del { color: var(--danger); }
    .icon-btn--del:hover { background: #fee2e2; }
    .status { display: inline-block; padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 700; }
    .status--buka { background: #dcfce7; color: #166534; }
    .status--tutup { background: #f1f4f9; color: #4b5563; }
    .aksi form { display: inline; }
    .btn--sm { height: 34px; padding: 0 12px; font-size: 12.5px; }
    .nama-link { color: var(--navy); font-weight: 700; text-decoration: none; }
    .nama-link:hover { color: var(--primary); text-decoration: underline; }
    .check { display: flex; align-items: center; gap: 8px; margin-top: 10px; font-size: 13.5px; color: #374151; }
    .empty { padding: 36px 16px; text-align: center; color: var(--muted); font-size: 14px; }

    dialog { border: 0; border-radius: 18px; padding: 0; width: min(440px, calc(100vw - 32px)); box-shadow: 0 24px 60px rgba(10,37,99,.28); }
    dialog::backdrop { background: rgba(10,37,99,.45); }
    dialog form { padding: 24px; display: grid; gap: 14px; }
    dialog h3 { font-size: 18px; font-weight: 700; color: var(--navy); }
    dialog p { font-size: 14px; line-height: 1.5; color: #374151; }
    .dlg-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 6px; }

    @media (max-width: 720px) {
        .panel { padding: 18px; }
        .add { grid-template-columns: 1fr; }
        .add .btn { margin-top: 0; width: 100%; }
        .thead { display: none; }
        .row { grid-template-columns: 1fr; gap: 8px; }
        .row .c-no { display: none; }
        .aksi { justify-content: flex-start; flex-wrap: wrap; }
    }
</style>
@endpush

@section('content')
    @if (session('status'))
        <div class="alert" role="status">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert--error" role="alert">{{ session('error') }}</div>
    @endif

    <div id="page-data" hidden
         data-edit-id="{{ session('edit_id') }}"
         data-old-nama="{{ old('nama') }}"></div>

    <section class="panel">
        <h2>Daftar Periode Yudisium</h2>
        <p class="hint">Periode yang <b>dibuka</b> tampil di dashboard mahasiswa yang sudah lulus pendadaran, dan mereka bisa mendaftar sendiri. Tutup periode untuk menghentikan pendaftaran.</p>

        <form class="add" method="POST" action="{{ route('admin.periode.store') }}" novalidate>
            @csrf
            <div class="field">
                <label for="nama">Nama periode</label>
                <input class="input @error('nama') is-invalid @enderror" id="nama" name="nama" type="text" maxlength="100"
                       placeholder="Contoh: Periode II Yudisium April 2026" autocomplete="off"
                       value="{{ session('edit_id') ? '' : old('nama') }}" required>
                @error('nama') <p class="field-error" role="alert">{{ $message }}</p> @enderror
                <label class="check" for="dibuka"><input type="checkbox" id="dibuka" name="dibuka" value="1" @checked(old('dibuka'))> Langsung buka untuk pendaftaran</label>
            </div>
            <button class="btn btn--primary" type="submit"><x-icon name="plus" :size="16" /> Tambah</button>
        </form>

        <div class="list">
            <div class="row thead" aria-hidden="true">
                <div class="c-no">No</div><div>Nama Periode</div><div>Status</div><div class="c-jumlah">Terdaftar</div><div style="text-align:right">Aksi</div>
            </div>

            @forelse ($daftar as $i => $p)
                <div class="row">
                    <div class="c-no">{{ $i + 1 }}</div>
                    <div><a class="nama-link" href="{{ route('admin.periode.show', $p) }}">{{ $p->nama }}</a></div>
                    <div><span class="status status--{{ $p->dibuka ? 'buka' : 'tutup' }}">{{ $p->dibuka ? 'Dibuka' : 'Ditutup' }}</span></div>
                    <div class="c-jumlah">{{ $p->mahasiswa_count }} mahasiswa</div>
                    <div class="aksi">
                        <a class="btn btn--ghost btn--sm" href="{{ route('admin.periode.show', $p) }}">Lihat mahasiswa</a>
                        <form method="POST" action="{{ route('admin.periode.status', $p) }}">
                            @csrf @method('PATCH')
                            <button class="btn btn--sm {{ $p->dibuka ? 'btn--ghost' : 'btn--primary' }}" type="submit">{{ $p->dibuka ? 'Tutup' : 'Buka' }}</button>
                        </form>
                        <button type="button" class="icon-btn icon-btn--edit" data-edit
                                data-id="{{ $p->id }}"
                                data-url="{{ route('admin.periode.update', $p) }}"
                                data-nama="{{ $p->nama }}"
                                aria-label="Edit {{ $p->nama }}"><x-icon name="edit" /></button>
                        <button type="button" class="icon-btn icon-btn--del" data-delete
                                data-url="{{ route('admin.periode.destroy', $p) }}"
                                data-label="{{ $p->nama }}"
                                aria-label="Hapus {{ $p->nama }}"><x-icon name="trash" /></button>
                    </div>
                </div>
            @empty
                <p class="empty">Belum ada periode. Tambahkan lewat form di atas.</p>
            @endforelse
        </div>
    </section>

    <dialog id="dlg-edit" aria-labelledby="edit-title">
        <form method="POST" id="form-edit" novalidate>
            @csrf
            @method('PUT')
            <h3 id="edit-title">Edit periode</h3>
            <div class="field">
                <label for="e-nama">Nama periode</label>
                <input class="input @if ($errors->edit->has('nama')) is-invalid @endif" id="e-nama" name="nama" type="text" maxlength="100" autocomplete="off" required>
                @if ($errors->edit->has('nama')) <p class="field-error" role="alert">{{ $errors->edit->first('nama') }}</p> @endif
            </div>
            <div class="dlg-actions">
                <button type="button" class="btn btn--ghost" data-close>Batal</button>
                <button type="submit" class="btn btn--primary">Simpan</button>
            </div>
        </form>
    </dialog>

    <dialog id="dlg-del" aria-labelledby="del-title">
        <form method="POST" id="form-del">
            @csrf
            @method('DELETE')
            <h3 id="del-title">Hapus periode?</h3>
            <p>Periode <strong id="del-label"></strong> akan dihapus. Periode yang masih berisi mahasiswa terdaftar tidak bisa dihapus.</p>
            <div class="dlg-actions">
                <button type="button" class="btn btn--ghost" data-close>Batal</button>
                <button type="submit" class="btn btn--danger">Hapus</button>
            </div>
        </form>
    </dialog>
@endsection

@push('scripts')
<script>
(function () {
    var data = document.getElementById('page-data').dataset;
    var dlgEdit = document.getElementById('dlg-edit');
    var dlgDel = document.getElementById('dlg-del');

    function openEdit(btn, nama) {
        document.getElementById('form-edit').action = btn.dataset.url;
        document.getElementById('e-nama').value = nama !== null ? nama : btn.dataset.nama;
        dlgEdit.showModal();
    }

    document.addEventListener('click', function (e) {
        var edit = e.target.closest('[data-edit]');
        if (edit) { openEdit(edit, null); return; }

        var del = e.target.closest('[data-delete]');
        if (del) {
            document.getElementById('form-del').action = del.dataset.url;
            document.getElementById('del-label').textContent = del.dataset.label;
            dlgDel.showModal();
            return;
        }

        [dlgEdit, dlgDel].forEach(function (dlg) {
            if (e.target === dlg || (dlg.open && e.target.closest('[data-close]') && dlg.contains(e.target))) dlg.close();
        });
    });

    /* Buka kembali dialog edit jika validasi gagal */
    if (data.editId) {
        var btn = document.querySelector('[data-edit][data-id="' + data.editId + '"]');
        if (btn) openEdit(btn, data.oldNama);
    }
})();
</script>
@endpush