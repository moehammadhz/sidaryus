@extends('layouts.admin')

@section('title', 'Data Dosen')
@section('crumb', 'Data Dosen')
@section('page_title', 'Data Dosen')

@push('styles')
<style>
    .card { background: #fff; border: 1px solid var(--line); border-radius: 16px; padding: 24px; }
    .card + .card { margin-top: 16px; }
    .card__head { display: flex; align-items: center; gap: 12px; padding-bottom: 16px; border-bottom: 1px solid var(--line); }
    .card__icon { width: 38px; height: 38px; border-radius: 10px; display: grid; place-items: center; background: #e3eefc; color: var(--primary); flex: none; }
    .card__head h2 { flex: 1; font-size: 18px; font-weight: 700; color: var(--navy); }
    .count { padding: 4px 12px; border-radius: 999px; background: #f1f4f9; font-size: 12.5px; font-weight: 700; color: #374151; }

    .alert { margin-bottom: 16px; padding: 12px 16px; border-radius: 12px; background: #dcfce7; color: #166534; font-size: 14px; font-weight: 600; }

    /* Form tambah */
    .add { margin-top: 20px; display: grid; grid-template-columns: 1fr 1.2fr 1fr auto; gap: 12px 16px; align-items: start; }
    .field { min-width: 0; }
    .field label { display: block; margin-bottom: 6px; font-size: 13px; font-weight: 600; color: #1f2937; }
    .input, .select {
        width: 100%; height: 46px; padding: 0 14px;
        border: 1.5px solid #d5dbe5; border-radius: 12px; background: #f6f8fb;
        font: 500 14px var(--font); color: var(--ink); outline: 0;
    }
    .select { cursor: pointer; }
    .input:focus, .select:focus { border-color: var(--primary); background: #fff; box-shadow: 0 0 0 3px rgba(43,114,213,.15); }
    .input.is-invalid, .select.is-invalid { border-color: var(--danger); }
    .field-error { margin-top: 6px; font-size: 12.5px; color: var(--danger); }
    .add .btn { height: 46px; margin-top: 25px; padding: 0 22px; }

    /* Tabel */
    .table-wrap { margin-top: 20px; overflow-x: auto; border: 1px solid var(--line); border-radius: 14px; }
    table { width: 100%; min-width: 640px; border-collapse: collapse; font-size: 14px; }
    thead th { height: 46px; padding: 0 16px; background: #f6f8fb; text-align: left; font-size: 12px; font-weight: 700; letter-spacing: .03em; text-transform: uppercase; color: #374151; }
    tbody td { padding: 14px 16px; border-top: 1px solid var(--line); vertical-align: middle; color: #1f2937; }
    tbody tr:hover td { background: #fafcff; }
    .col-no { width: 64px; color: #9aa1ad; }
    .col-aksi { width: 120px; text-align: right; }
    .nidn { font: 600 13.5px ui-monospace, Consolas, monospace; color: var(--primary); }
    .nama { font-weight: 700; }
    .pill { display: inline-block; padding: 4px 12px; border-radius: 8px; background: #f1f4f9; font-size: 12.5px; font-weight: 600; color: #374151; }
    .muted { color: #9aa1ad; }
    .aksi { display: flex; justify-content: flex-end; gap: 6px; }
    .icon-btn { width: 36px; height: 36px; display: grid; place-items: center; border: 0; border-radius: 10px; cursor: pointer; }
    .icon-btn--edit { background: #e3eefc; color: var(--primary); }
    .icon-btn--edit:hover { background: #cfe0fa; }
    .icon-btn--del { background: #fee2e2; color: var(--danger); }
    .icon-btn--del:hover { background: #fecaca; }
    .empty { padding: 40px 16px; text-align: center; color: var(--muted); }

    /* Pop-up */
    dialog { border: 0; border-radius: 18px; padding: 0; width: min(460px, calc(100vw - 32px)); box-shadow: 0 24px 60px rgba(10,37,99,.28); }
    dialog::backdrop { background: rgba(10,37,99,.45); }
    dialog form { padding: 24px; display: grid; gap: 14px; }
    dialog h3 { font-size: 18px; font-weight: 700; color: var(--navy); }
    dialog p { font-size: 14px; line-height: 1.5; color: #374151; }
    .dlg-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 6px; }

    @media (max-width: 960px) {
        .add { grid-template-columns: 1fr 1fr; }
        .add .btn { margin-top: 0; grid-column: span 2; width: 100%; }
    }
    @media (max-width: 600px) {
        .card { padding: 18px; }
        .add { grid-template-columns: 1fr; }
        .add .btn { grid-column: auto; }
    }
</style>
@endpush

@section('content')
@php
    $pakaiOld = ! session('edit_id');
@endphp

    {{-- Data untuk skrip halaman ini, dibaca lewat atribut data --}}
    <div id="page-data" hidden
         data-edit-id="{{ session('edit_id') }}"
         data-old-nidn="{{ old('nidn') }}"
         data-old-nama="{{ old('nama') }}"
         data-old-prodi="{{ old('program_studi') }}"></div>

    @if (session('status'))
        <div class="alert" role="status">{{ session('status') }}</div>
    @endif

    {{-- Tambah data --}}
    <section class="card">
        <div class="card__head">
            <div class="card__icon"><x-icon name="userplus" :size="20" /></div>
            <h2>Tambah Data Dosen</h2>
        </div>

        <form class="add" method="POST" action="{{ route('admin.dosen.store') }}" novalidate>
            @csrf

            <div class="field">
                <label for="nidn">NIDN</label>
                <input class="input @error('nidn') is-invalid @enderror" id="nidn" name="nidn" type="text"
                       inputmode="numeric" maxlength="20" placeholder="Masukkan NIDN (boleh kosong)" autocomplete="off"
                       value="{{ $pakaiOld ? old('nidn') : '' }}">
                @error('nidn') <p class="field-error" role="alert">{{ $message }}</p> @enderror
            </div>

            <div class="field">
                <label for="nama">Nama Dosen</label>
                <input class="input @error('nama') is-invalid @enderror" id="nama" name="nama" type="text"
                       maxlength="120" placeholder="Nama Lengkap" autocomplete="off"
                       value="{{ $pakaiOld ? old('nama') : '' }}" required>
                @error('nama') <p class="field-error" role="alert">{{ $message }}</p> @enderror
            </div>

            <div class="field">
                <label for="program_studi">Program Studi</label>
                <select class="select @error('program_studi') is-invalid @enderror" id="program_studi" name="program_studi" required>
                    <option value="">Pilih Prodi</option>
                    @foreach (\App\Models\User::PROGRAM_STUDI as $p)
                        <option value="{{ $p }}" @selected($pakaiOld && old('program_studi') === $p)>{{ $p }}</option>
                    @endforeach
                </select>
                @error('program_studi') <p class="field-error" role="alert">{{ $message }}</p> @enderror
            </div>

            <button class="btn btn--primary" type="submit"><x-icon name="check" :size="16" /> Simpan Data</button>
        </form>
    </section>

    {{-- Daftar --}}
    <section class="card">
        <div class="card__head">
            <div class="card__icon"><x-icon name="users" :size="20" /></div>
            <h2>Daftar Dosen Teknik</h2>
            <span class="count">{{ $daftar->count() }} dosen</span>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th scope="col" class="col-no">No</th>
                        <th scope="col">NIDN</th>
                        <th scope="col">Nama Lengkap</th>
                        <th scope="col">Program Studi</th>
                        <th scope="col" class="col-aksi">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($daftar as $i => $d)
                        <tr>
                            <td class="col-no">{{ $i + 1 }}</td>
                            <td>
                                @if ($d->nidn)
                                    <span class="nidn">{{ $d->nidn }}</span>
                                @else
                                    <span class="muted">-</span>
                                @endif
                            </td>
                            <td class="nama">{{ $d->nama }}</td>
                            <td><span class="pill">{{ $d->program_studi }}</span></td>
                            <td>
                                <div class="aksi">
                                    <button type="button" class="icon-btn icon-btn--edit" data-edit
                                            data-id="{{ $d->id }}"
                                            data-url="{{ route('admin.dosen.update', $d) }}"
                                            data-nidn="{{ $d->nidn }}"
                                            data-nama="{{ $d->nama }}"
                                            data-prodi="{{ $d->program_studi }}"
                                            aria-label="Edit {{ $d->nama }}" title="Edit"><x-icon name="edit" /></button>
                                    <button type="button" class="icon-btn icon-btn--del" data-delete
                                            data-url="{{ route('admin.dosen.destroy', $d) }}"
                                            data-label="{{ $d->nama }}"
                                            aria-label="Hapus {{ $d->nama }}" title="Hapus"><x-icon name="trash" /></button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="empty">Belum ada data dosen. Tambahkan lewat form di atas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    {{-- Pop-up edit --}}
    <dialog id="dlg-edit" aria-labelledby="edit-title">
        <form method="POST" id="form-edit" novalidate>
            @csrf
            @method('PUT')
            <h3 id="edit-title">Edit data dosen</h3>

            <div class="field">
                <label for="e-nidn">NIDN</label>
                <input class="input @if ($errors->edit->has('nidn')) is-invalid @endif" id="e-nidn" name="nidn" type="text"
                       inputmode="numeric" maxlength="20" placeholder="Boleh kosong" autocomplete="off">
                @if ($errors->edit->has('nidn')) <p class="field-error" role="alert">{{ $errors->edit->first('nidn') }}</p> @endif
            </div>

            <div class="field">
                <label for="e-nama">Nama Dosen</label>
                <input class="input @if ($errors->edit->has('nama')) is-invalid @endif" id="e-nama" name="nama" type="text"
                       maxlength="120" autocomplete="off" required>
                @if ($errors->edit->has('nama')) <p class="field-error" role="alert">{{ $errors->edit->first('nama') }}</p> @endif
            </div>

            <div class="field">
                <label for="e-prodi">Program Studi</label>
                <select class="select @if ($errors->edit->has('program_studi')) is-invalid @endif" id="e-prodi" name="program_studi" required>
                    <option value="">Pilih Prodi</option>
                    @foreach (\App\Models\User::PROGRAM_STUDI as $p)
                        <option value="{{ $p }}">{{ $p }}</option>
                    @endforeach
                </select>
                @if ($errors->edit->has('program_studi')) <p class="field-error" role="alert">{{ $errors->edit->first('program_studi') }}</p> @endif
            </div>

            <div class="dlg-actions">
                <button type="button" class="btn btn--ghost" data-close>Batal</button>
                <button type="submit" class="btn btn--primary">Simpan</button>
            </div>
        </form>
    </dialog>

    {{-- Pop-up hapus --}}
    <dialog id="dlg-del" aria-labelledby="del-title">
        <form method="POST" id="form-del">
            @csrf
            @method('DELETE')
            <h3 id="del-title">Hapus data dosen?</h3>
            <p>Data dosen <strong id="del-label"></strong> akan dihapus. Tindakan ini tidak bisa dibatalkan.</p>
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

    function openEdit(btn, nidn, nama, prodi) {
        document.getElementById('form-edit').action = btn.dataset.url;
        document.getElementById('e-nidn').value = nidn;
        document.getElementById('e-nama').value = nama;
        document.getElementById('e-prodi').value = prodi;
        dlgEdit.showModal();
    }

    document.addEventListener('click', function (e) {
        var edit = e.target.closest('[data-edit]');
        if (edit) {
            openEdit(edit, edit.dataset.nidn, edit.dataset.nama, edit.dataset.prodi);
            return;
        }

        var del = e.target.closest('[data-delete]');
        if (del) {
            document.getElementById('form-del').action = del.dataset.url;
            document.getElementById('del-label').textContent = del.dataset.label;
            dlgDel.showModal();
            return;
        }

        var tutup = e.target.closest('[data-close]');
        if (tutup) {
            tutup.closest('dialog').close();
            return;
        }

        /* Klik di area gelap di luar kotak menutup pop-up */
        if (e.target.tagName === 'DIALOG') {
            e.target.close();
        }
    });

    /* Buka kembali pop-up edit jika validasi gagal, dengan isian terakhir */
    if (data.editId) {
        var btn = document.querySelector('[data-edit][data-id="' + data.editId + '"]');
        if (btn) openEdit(btn, data.oldNidn, data.oldNama, data.oldProdi);
    }
})();
</script>
@endpush