@extends('layouts.admin')

@section('title', 'Kelola Persyaratan')
@section('crumb', 'Kelola Persyaratan')
@section('page_title', 'Kelola Persyaratan')

@push('styles')
<style>
    .panel { background: #fff; border: 1px solid var(--line); border-radius: 16px; padding: 24px; }
    .panel h2 { font-size: 18px; font-weight: 700; color: var(--navy); }
    .hint { margin-top: 4px; font-size: 13px; color: var(--muted); display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
    .hint svg { color: #9aa1ad; }

    .alert { margin-bottom: 16px; padding: 12px 16px; border-radius: 12px; background: #dcfce7; color: #166534; font-size: 14px; font-weight: 600; }

    /* Form tambah */
    .add { margin-top: 20px; display: grid; grid-template-columns: 180px 1fr auto; gap: 12px 16px; align-items: start; }
    .field label { display: block; margin-bottom: 6px; font-size: 13px; font-weight: 600; color: #1f2937; }
    .input {
        width: 100%; height: 46px; padding: 0 14px;
        border: 1.5px solid #d5dbe5; border-radius: 12px; background: #f6f8fb;
        font: 500 14px var(--font); color: var(--ink); outline: 0;
    }
    .input:focus { border-color: var(--primary); background: #fff; box-shadow: 0 0 0 3px rgba(43,114,213,.15); }
    .input.is-invalid { border-color: var(--danger); }
    .field-error { margin-top: 6px; font-size: 12.5px; color: var(--danger); }
    .add .btn { height: 46px; margin-top: 25px; padding: 0 22px; }

    /* Daftar */
    .list { margin-top: 24px; border: 1px solid var(--line); border-radius: 14px; overflow: hidden; }
    .grid { display: grid; grid-template-columns: 76px 130px 1fr 168px; align-items: center; gap: 12px; padding: 0 16px; }
    .thead { height: 46px; background: #f6f8fb; font-size: 13px; font-weight: 700; color: #374151; }
    .row { min-height: 64px; padding-top: 10px; padding-bottom: 10px; border-top: 1px solid var(--line); background: #fff; font-size: 14px; }
    .row.is-dragging { opacity: .45; background: #eef4fd; }
    .c-no { display: flex; align-items: center; gap: 10px; }
    .handle { display: flex; color: #b6bcc7; cursor: grab; padding: 4px; margin: -4px; border-radius: 6px; touch-action: none; }
    .handle:hover { color: var(--primary); background: #eef4fd; }
    .handle:active { cursor: grabbing; }
    .no { font-weight: 600; color: #374151; min-width: 18px; }
    .kode { display: inline-block; padding: 3px 10px; border-radius: 8px; background: #e3eefc; color: #1d4ed8; font-size: 12.5px; font-weight: 700; }
    .c-nama { line-height: 1.45; color: #1f2937; }
    .c-aksi { display: flex; justify-content: flex-end; gap: 4px; }
    .icon-btn {
        width: 34px; height: 34px; display: grid; place-items: center;
        border: 0; border-radius: 8px; background: transparent; color: #6b7280; cursor: pointer;
    }
    .icon-btn:hover { background: #f1f4f9; color: var(--navy); }
    .icon-btn[disabled] { opacity: .3; cursor: default; background: transparent; }
    .icon-btn--edit { color: var(--primary); }
    .icon-btn--edit:hover { background: #e3eefc; color: var(--primary-dark); }
    .icon-btn--del { color: var(--danger); }
    .icon-btn--del:hover { background: #fee2e2; color: #b91c1c; }
    .empty { padding: 36px 16px; text-align: center; color: var(--muted); font-size: 14px; }

    /* Dialog */
    dialog { border: 0; border-radius: 18px; padding: 0; width: min(440px, calc(100vw - 32px)); box-shadow: 0 24px 60px rgba(10,37,99,.28); }
    dialog::backdrop { background: rgba(10,37,99,.45); }
    dialog form { padding: 24px; display: grid; gap: 14px; }
    dialog h3 { font-size: 18px; font-weight: 700; color: var(--navy); }
    dialog p { font-size: 14px; line-height: 1.5; color: #374151; }
    .dlg-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 6px; }

    /* Notifikasi kecil */
    .toast {
        position: fixed; right: 24px; bottom: 24px; z-index: 80; max-width: 320px;
        padding: 12px 16px; border-radius: 12px; background: #0a2563; color: #fff;
        font-size: 13.5px; font-weight: 600; box-shadow: 0 12px 32px rgba(10,37,99,.3);
        opacity: 0; transform: translateY(8px); pointer-events: none; transition: opacity .2s, transform .2s;
    }
    .toast.is-show { opacity: 1; transform: none; }
    .toast.is-error { background: var(--danger); }

    @media (max-width: 720px) {
        .panel { padding: 18px; }
        .add { grid-template-columns: 1fr; }
        .add .btn { margin-top: 0; width: 100%; }
        .thead { display: none; }
        .grid {
            grid-template-columns: 56px 1fr;
            grid-template-areas: "no kode" "no nama" ". aksi";
            gap: 6px 10px;
        }
        .c-no { grid-area: no; }
        .c-kode { grid-area: kode; }
        .c-nama { grid-area: nama; }
        .c-aksi { grid-area: aksi; }
    }
</style>
@endpush

@section('content')
    @if (session('status'))
        <div class="alert" role="status">{{ session('status') }}</div>
    @endif

    {{-- Data untuk skrip halaman ini, dibaca lewat atribut data --}}
    <div id="page-data" hidden
         data-token="{{ csrf_token() }}"
         data-reorder-url="{{ route('admin.persyaratan.reorder') }}"
         data-edit-id="{{ session('edit_id') }}"
         data-old-kode="{{ old('kode') }}"
         data-old-nama="{{ old('nama') }}"></div>

    <section class="panel">
        <h2>Daftar Persyaratan Dokumen</h2>
        <p class="hint">
            Seret baris pada ikon <x-icon name="grip" :size="16" /> atau pakai tombol panah untuk mengatur urutan.
        </p>

        {{-- Form tambah --}}
        <form class="add" method="POST" action="{{ route('admin.persyaratan.store') }}" novalidate>
            @csrf
            <div class="field">
                <label for="kode">Kode</label>
                <input class="input @error('kode') is-invalid @enderror" id="kode" name="kode" type="text"
                       maxlength="20" placeholder="Contoh: KTM" autocomplete="off"
                       value="{{ session('edit_id') ? '' : old('kode') }}" required>
                @error('kode') <p class="field-error" role="alert">{{ $message }}</p> @enderror
            </div>
            <div class="field">
                <label for="nama">Nama dokumen</label>
                <input class="input @error('nama') is-invalid @enderror" id="nama" name="nama" type="text"
                       maxlength="255" placeholder="Contoh: Kartu Tanda Mahasiswa" autocomplete="off"
                       value="{{ session('edit_id') ? '' : old('nama') }}" required>
                @error('nama') <p class="field-error" role="alert">{{ $message }}</p> @enderror
            </div>
            <button class="btn btn--primary" type="submit"><x-icon name="plus" :size="16" /> Tambah</button>
        </form>

        {{-- Daftar --}}
        <div class="list">
            <div class="grid thead" aria-hidden="true">
                <div>No</div><div>Kode</div><div>Nama Dokumen</div><div style="text-align:right">Aksi</div>
            </div>

            <div id="rows">
                @foreach ($daftar as $i => $p)
                    <div class="grid row" data-id="{{ $p->id }}">
                        <div class="c-no">
                            <span class="handle" title="Seret untuk mengatur urutan"><x-icon name="grip" /></span>
                            <span class="no">{{ $i + 1 }}</span>
                        </div>
                        <div class="c-kode"><span class="kode">{{ $p->kode }}</span></div>
                        <div class="c-nama">{{ $p->nama }}</div>
                        <div class="c-aksi">
                            <button type="button" class="icon-btn" data-move="-1" aria-label="Naikkan urutan"><x-icon name="arrow-up" :size="16" /></button>
                            <button type="button" class="icon-btn" data-move="1" aria-label="Turunkan urutan"><x-icon name="arrow-down" :size="16" /></button>
                            <button type="button" class="icon-btn icon-btn--edit" data-edit
                                    data-url="{{ route('admin.persyaratan.update', $p) }}"
                                    data-kode="{{ $p->kode }}" data-nama="{{ $p->nama }}"
                                    aria-label="Edit {{ $p->kode }}"><x-icon name="edit" /></button>
                            <button type="button" class="icon-btn icon-btn--del" data-delete
                                    data-url="{{ route('admin.persyaratan.destroy', $p) }}"
                                    data-label="{{ $p->kode }} - {{ $p->nama }}"
                                    data-jumlah="{{ $p->dokumen_count }}"
                                    aria-label="Hapus {{ $p->kode }}"><x-icon name="trash" /></button>
                        </div>
                    </div>
                @endforeach
            </div>

            @if ($daftar->isEmpty())
                <p class="empty">Belum ada persyaratan. Tambahkan lewat form di atas.</p>
            @endif
        </div>
    </section>

    {{-- Dialog edit --}}
    <dialog id="dlg-edit" aria-labelledby="edit-title">
        <form method="POST" id="form-edit" novalidate>
            @csrf
            @method('PUT')
            <h3 id="edit-title">Edit persyaratan</h3>
            <div class="field">
                <label for="e-kode">Kode</label>
                <input class="input @if ($errors->edit->has('kode')) is-invalid @endif" id="e-kode" name="kode" type="text" maxlength="20" autocomplete="off" required>
                @if ($errors->edit->has('kode')) <p class="field-error" role="alert">{{ $errors->edit->first('kode') }}</p> @endif
            </div>
            <div class="field">
                <label for="e-nama">Nama dokumen</label>
                <input class="input @if ($errors->edit->has('nama')) is-invalid @endif" id="e-nama" name="nama" type="text" maxlength="255" autocomplete="off" required>
                @if ($errors->edit->has('nama')) <p class="field-error" role="alert">{{ $errors->edit->first('nama') }}</p> @endif
            </div>
            <div class="dlg-actions">
                <button type="button" class="btn btn--ghost" data-close>Batal</button>
                <button type="submit" class="btn btn--primary">Simpan</button>
            </div>
        </form>
    </dialog>

    {{-- Dialog hapus --}}
    <dialog id="dlg-del" aria-labelledby="del-title">
        <form method="POST" id="form-del">
            @csrf
            @method('DELETE')
            <h3 id="del-title">Hapus persyaratan?</h3>
            <p>Persyaratan <strong id="del-label"></strong> akan dihapus dari daftar. Tindakan ini tidak bisa dibatalkan.</p>
            <p id="del-jumlah" style="color: #b91c1c; font-weight: 600;" hidden></p>
            <div class="dlg-actions">
                <button type="button" class="btn btn--ghost" data-close>Batal</button>
                <button type="submit" class="btn btn--danger">Hapus</button>
            </div>
        </form>
    </dialog>

    <div class="toast" id="toast" role="status" aria-live="polite"></div>
@endsection

@push('scripts')
<script>
(function () {
    var data = document.getElementById('page-data').dataset;
    var token = data.token;
    var reorderUrl = data.reorderUrl;
    var reopenId = data.editId;

    var list = document.getElementById('rows');
    var toastEl = document.getElementById('toast');
    var dlgEdit = document.getElementById('dlg-edit');
    var dlgDel = document.getElementById('dlg-del');
    var dragging = null, saveTimer = null, toastTimer = null;

    function rows() { return Array.prototype.slice.call(list.querySelectorAll('.row')); }

    function refresh() {
        var all = rows();
        all.forEach(function (row, i) {
            row.querySelector('.no').textContent = i + 1;
            row.querySelector('[data-move="-1"]').disabled = (i === 0);
            row.querySelector('[data-move="1"]').disabled = (i === all.length - 1);
        });
    }

    function toast(text, isError) {
        toastEl.textContent = text;
        toastEl.classList.toggle('is-error', !!isError);
        toastEl.classList.add('is-show');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(function () { toastEl.classList.remove('is-show'); }, 2200);
    }

    function save() {
        clearTimeout(saveTimer);
        saveTimer = setTimeout(function () {
            var ids = rows().map(function (r) { return Number(r.dataset.id); });
            fetch(reorderUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
                body: JSON.stringify({ ids: ids })
            }).then(function (res) {
                if (!res.ok) throw new Error('gagal');
                toast('Urutan disimpan.');
            }).catch(function () {
                toast('Gagal menyimpan urutan. Muat ulang halaman.', true);
            });
        }, 400);
    }

    /* Drag and drop (desktop) */
    rows().forEach(function (row) {
        var handle = row.querySelector('.handle');
        handle.addEventListener('pointerdown', function () { row.draggable = true; });
        row.addEventListener('dragstart', function (e) {
            dragging = row;
            row.classList.add('is-dragging');
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', row.dataset.id);
        });
        row.addEventListener('dragover', function (e) {
            if (!dragging || dragging === row) return;
            e.preventDefault();
            var box = row.getBoundingClientRect();
            var after = e.clientY > box.top + box.height / 2;
            list.insertBefore(dragging, after ? row.nextSibling : row);
        });
        row.addEventListener('dragend', function () {
            row.draggable = false;
            row.classList.remove('is-dragging');
            dragging = null;
            refresh();
            save();
        });
    });

    function openEdit(btn, kode, nama) {
        document.getElementById('form-edit').action = btn.dataset.url;
        document.getElementById('e-kode').value = kode !== null ? kode : btn.dataset.kode;
        document.getElementById('e-nama').value = nama !== null ? nama : btn.dataset.nama;
        dlgEdit.showModal();
    }

    /* Tombol aksi (panah, edit, hapus) lewat delegasi */
    list.addEventListener('click', function (e) {
        var move = e.target.closest('[data-move]');
        if (move && !move.disabled) {
            var row = move.closest('.row');
            if (Number(move.dataset.move) < 0 && row.previousElementSibling) {
                list.insertBefore(row, row.previousElementSibling);
            } else if (Number(move.dataset.move) > 0 && row.nextElementSibling) {
                list.insertBefore(row.nextElementSibling, row);
            }
            refresh();
            save();
            return;
        }

        var edit = e.target.closest('[data-edit]');
        if (edit) { openEdit(edit, null, null); return; }

        var del = e.target.closest('[data-delete]');
        if (del) {
            document.getElementById('form-del').action = del.dataset.url;
            document.getElementById('del-label').textContent = del.dataset.label;
            var jumlah = Number(del.dataset.jumlah || 0);
            var info = document.getElementById('del-jumlah');
            info.hidden = jumlah === 0;
            info.textContent = jumlah + ' berkas yang sudah diunggah mahasiswa untuk persyaratan ini juga akan ikut terhapus.';
            dlgDel.showModal();
        }
    });

    /* Tutup dialog: tombol Batal dan klik di luar kotak */
    [dlgEdit, dlgDel].forEach(function (dlg) {
        dlg.addEventListener('click', function (e) {
            if (e.target === dlg || e.target.closest('[data-close]')) dlg.close();
        });
    });

    /* Buka kembali dialog edit jika validasi gagal */
    if (reopenId) {
        var btn = document.querySelector('.row[data-id="' + reopenId + '"] [data-edit]');
        if (btn) openEdit(btn, data.oldKode, data.oldNama);
    }

    refresh();
})();
</script>
@endpush