@extends('layouts.admin')

@section('title', 'Manajemen Akun Petugas')
@section('crumb', 'Data Petugas')
@section('page_title', 'Manajemen Akun Petugas')

@section('actions')
    <a class="btn btn--ghost" href="{{ route('admin.dashboard') }}">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5M11 6l-6 6 6 6"/></svg>
        Kembali
    </a>
@endsection

@push('styles')
<style>
    .card { background: #fff; border: 1px solid var(--line); border-radius: 16px; padding: 24px; }
    .card + .card { margin-top: 16px; }
    .card__title { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
    .card__title h2 { flex: 1; font-size: 16px; font-weight: 700; color: #374151; padding-left: 12px; border-left: 4px solid #5db3e8; line-height: 1.2; }
    .card__title--plain h2 { padding-left: 0; border-left: 0; }
    .count { padding: 4px 12px; border-radius: 999px; background: #f1f4f9; font-size: 12.5px; font-weight: 700; color: #374151; }

    .alert { margin-bottom: 16px; padding: 12px 16px; border-radius: 12px; background: #dcfce7; color: #166534; font-size: 14px; font-weight: 600; }

    .add { margin-top: 20px; display: grid; grid-template-columns: 1fr 1fr; gap: 16px 20px; align-items: start; }
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
    .add__submit { grid-column: 1 / -1; display: flex; justify-content: flex-end; }
    .add__submit .btn { height: 46px; padding: 0 22px; }

    .table-wrap { margin-top: 18px; overflow-x: auto; border: 1px solid var(--line); border-radius: 14px; }
    table { width: 100%; min-width: 620px; border-collapse: collapse; font-size: 14px; }
    thead th { height: 46px; padding: 0 16px; background: #f6f8fb; text-align: left; font-size: 12px; font-weight: 700; letter-spacing: .03em; text-transform: uppercase; color: #374151; }
    tbody td { padding: 14px 16px; border-top: 1px solid var(--line); vertical-align: middle; color: #1f2937; }
    tbody tr:hover td { background: #fafcff; }
    .col-no { width: 64px; color: #9aa1ad; }
    .col-aksi { width: 200px; text-align: right; }
    .username { font-weight: 700; }
    .nama-sub { margin-top: 2px; font-size: 12.5px; color: var(--muted); font-weight: 400; }
    .pill { display: inline-block; padding: 4px 12px; border-radius: 8px; background: #e3eefc; color: #1d4ed8; font-size: 12.5px; font-weight: 700; }
    .aksi { display: flex; justify-content: flex-end; gap: 8px; }
    .btn--edit { background: #e8f3ff; color: var(--primary); height: 36px; padding: 0 14px; }
    .btn--edit:hover { background: #d3e8ff; }
    .btn--del { background: #fef2f2; color: var(--danger); height: 36px; padding: 0 14px; }
    .btn--del:hover { background: #fee2e2; }
    .empty { padding: 40px 16px; text-align: center; color: var(--muted); }

    dialog { border: 0; border-radius: 18px; padding: 0; width: min(480px, calc(100vw - 32px)); box-shadow: 0 24px 60px rgba(10,37,99,.28); }
    dialog::backdrop { background: rgba(10,37,99,.45); }
    dialog form { padding: 24px; display: grid; gap: 14px; max-height: calc(100vh - 32px); overflow-y: auto; }
    dialog h3 { font-size: 18px; font-weight: 700; color: var(--navy); }
    dialog p { font-size: 14px; line-height: 1.5; color: #374151; }
    .field small { display: block; margin-top: 6px; font-size: 12px; color: var(--muted); }
    .dlg-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 4px; }

    @media (max-width: 720px) {
        .card { padding: 18px; }
        .add { grid-template-columns: 1fr; }
        .add__submit .btn { width: 100%; }
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
         data-old-name="{{ old('name') }}"
         data-old-username="{{ old('username') }}"
         data-old-role="{{ old('role') }}"></div>

    @if (session('status'))
        <div class="alert" role="status">{{ session('status') }}</div>
    @endif

    {{-- Tambah akun --}}
    <section class="card">
        <div class="card__title"><h2>Tambah Akun Baru</h2></div>

        <form class="add" method="POST" action="{{ route('admin.petugas.store') }}" novalidate>
            @csrf

            <div class="field">
                <label for="name">Nama</label>
                <input class="input @error('name') is-invalid @enderror" id="name" name="name" type="text"
                       maxlength="100" placeholder="Contoh: Staf Pengajaran FT" autocomplete="off"
                       value="{{ $pakaiOld ? old('name') : '' }}" required>
                @error('name') <p class="field-error" role="alert">{{ $message }}</p> @enderror
            </div>

            <div class="field">
                <label for="role">Peran</label>
                <select class="select @error('role') is-invalid @enderror" id="role" name="role" required>
                    <option value="">Pilih peran</option>
                    @foreach ($peran as $nilai => $label)
                        <option value="{{ $nilai }}" @selected($pakaiOld && old('role') === $nilai)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('role') <p class="field-error" role="alert">{{ $message }}</p> @enderror
            </div>

            <div class="field">
                <label for="username">Username</label>
                <input class="input @error('username') is-invalid @enderror" id="username" name="username" type="text"
                       maxlength="50" placeholder="Contoh: petugas_ft" autocomplete="off"
                       value="{{ $pakaiOld ? old('username') : '' }}" required>
                @error('username') <p class="field-error" role="alert">{{ $message }}</p> @enderror
            </div>

            <div class="field">
                <label for="password">Password</label>
                <input class="input @error('password') is-invalid @enderror" id="password" name="password" type="password"
                       minlength="8" placeholder="Min. 8 karakter" autocomplete="new-password" required>
                @error('password') <p class="field-error" role="alert">{{ $message }}</p> @enderror
            </div>

            <div class="add__submit">
                <button class="btn btn--primary" type="submit"><x-icon name="plus" :size="16" /> Simpan Petugas</button>
            </div>
        </form>
    </section>

    {{-- Daftar --}}
    <section class="card">
        <div class="card__title card__title--plain">
            <h2>Daftar Petugas Aktif</h2>
            <span class="count">{{ $daftar->count() }} petugas</span>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th scope="col" class="col-no">No</th>
                        <th scope="col">Username</th>
                        <th scope="col">Peran</th>
                        <th scope="col" class="col-aksi">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($daftar as $i => $p)
                        <tr>
                            <td class="col-no">{{ $i + 1 }}</td>
                            <td>
                                <div class="username">{{ $p->username }}</div>
                                <div class="nama-sub">{{ $p->name }}</div>
                            </td>
                            <td><span class="pill">{{ $p->role->label() }}</span></td>
                            <td>
                                <div class="aksi">
                                    <button type="button" class="btn btn--edit" data-edit
                                            data-id="{{ $p->id }}"
                                            data-url="{{ route('admin.petugas.update', $p) }}"
                                            data-name="{{ $p->name }}"
                                            data-username="{{ $p->username }}"
                                            data-role="{{ $p->role->value }}"
                                            aria-label="Edit {{ $p->username }}"><x-icon name="edit" :size="16" /> Edit</button>
                                    <button type="button" class="btn btn--del" data-delete
                                            data-url="{{ route('admin.petugas.destroy', $p) }}"
                                            data-label="{{ $p->username }}"
                                            aria-label="Hapus {{ $p->username }}"><x-icon name="trash" :size="16" /> Hapus</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="empty">Belum ada data petugas.</td></tr>
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
            <h3 id="edit-title">Edit akun petugas</h3>

            <div class="field">
                <label for="e-name">Nama</label>
                <input class="input @if ($errors->edit->has('name')) is-invalid @endif" id="e-name" name="name" type="text" maxlength="100" autocomplete="off" required>
                @if ($errors->edit->has('name')) <p class="field-error" role="alert">{{ $errors->edit->first('name') }}</p> @endif
            </div>

            <div class="field">
                <label for="e-username">Username</label>
                <input class="input @if ($errors->edit->has('username')) is-invalid @endif" id="e-username" name="username" type="text" maxlength="50" autocomplete="off" required>
                @if ($errors->edit->has('username')) <p class="field-error" role="alert">{{ $errors->edit->first('username') }}</p> @endif
            </div>

            <div class="field">
                <label for="e-role">Peran</label>
                <select class="select @if ($errors->edit->has('role')) is-invalid @endif" id="e-role" name="role" required>
                    <option value="">Pilih peran</option>
                    @foreach ($peran as $nilai => $label)
                        <option value="{{ $nilai }}">{{ $label }}</option>
                    @endforeach
                </select>
                @if ($errors->edit->has('role')) <p class="field-error" role="alert">{{ $errors->edit->first('role') }}</p> @endif
            </div>

            <div class="field">
                <label for="e-password">Password baru</label>
                <input class="input @if ($errors->edit->has('password')) is-invalid @endif" id="e-password" name="password" type="password" minlength="8" autocomplete="new-password" placeholder="Kosongkan jika tidak diubah">
                @if ($errors->edit->has('password'))
                    <p class="field-error" role="alert">{{ $errors->edit->first('password') }}</p>
                @else
                    <small>Isi hanya jika ingin mengganti password (minimal 8 karakter).</small>
                @endif
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
            <h3 id="del-title">Hapus akun petugas?</h3>
            <p>Akun <strong id="del-label"></strong> akan dihapus dan tidak bisa login lagi. Tindakan ini tidak bisa dibatalkan.</p>
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

    function openEdit(btn, v) {
        document.getElementById('form-edit').action = btn.dataset.url;
        document.getElementById('e-name').value = v.name;
        document.getElementById('e-username').value = v.username;
        document.getElementById('e-role').value = v.role;
        document.getElementById('e-password').value = '';
        dlgEdit.showModal();
    }

    document.addEventListener('click', function (e) {
        var edit = e.target.closest('[data-edit]');
        if (edit) {
            openEdit(edit, { name: edit.dataset.name, username: edit.dataset.username, role: edit.dataset.role });
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
        if (btn) openEdit(btn, { name: data.oldName, username: data.oldUsername, role: data.oldRole });
    }
})();
</script>
@endpush