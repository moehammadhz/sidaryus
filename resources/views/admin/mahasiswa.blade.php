@extends('layouts.admin')

@section('title', 'Data Mahasiswa')
@section('crumb', 'Data Mahasiswa')
@section('page_title', 'Data Mahasiswa')

@push('styles')
<style>
    .card { background: #fff; border: 1px solid var(--line); border-radius: 16px; padding: 24px; }
    .card__head { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
    .card__icon { width: 38px; height: 38px; border-radius: 10px; display: grid; place-items: center; background: #e3eefc; color: var(--primary); flex: none; }
    .card__head h2 { flex: 1; font-size: 18px; font-weight: 700; color: var(--navy); }
    .count { padding: 4px 12px; border-radius: 999px; background: #f1f4f9; font-size: 12.5px; font-weight: 700; color: #374151; }

    .alert { margin-bottom: 16px; padding: 12px 16px; border-radius: 12px; background: #dcfce7; color: #166534; font-size: 14px; font-weight: 600; }

    .search { margin-top: 18px; display: flex; gap: 8px; flex-wrap: wrap; }
    .search__box {
        display: flex; align-items: center; gap: 8px; height: 42px; flex: 1 1 260px; max-width: 420px;
        padding: 0 12px; border: 1.5px solid #d5dbe5; border-radius: 10px; background: #f6f8fb; color: #9aa1ad;
    }
    .search__box:focus-within { border-color: var(--primary); background: #fff; box-shadow: 0 0 0 3px rgba(43,114,213,.15); }
    .search__box input { flex: 1; min-width: 0; border: 0; outline: 0; background: transparent; font: 500 14px var(--font); color: var(--ink); }
    .search .btn { height: 42px; }

    .table-wrap { margin-top: 18px; overflow-x: auto; border: 1px solid var(--line); border-radius: 14px; }
    table { width: 100%; min-width: 620px; border-collapse: collapse; font-size: 14px; }
    thead th { height: 46px; padding: 0 16px; background: #f6f8fb; text-align: left; font-size: 12px; font-weight: 700; letter-spacing: .03em; text-transform: uppercase; color: #374151; }
    tbody td { padding: 14px 16px; border-top: 1px solid var(--line); vertical-align: middle; color: #1f2937; }
    tbody tr:hover td { background: #fafcff; }
    .col-no { width: 64px; color: #9aa1ad; }
    .col-aksi { width: 300px; text-align: right; }
    .person { display: flex; align-items: center; gap: 14px; }
    .avatar { width: 44px; height: 44px; border-radius: 12px; background: #e8f1fb; color: #5db3e8; display: grid; place-items: center; flex: none; }
    .person__nama { font-weight: 700; line-height: 1.3; }
    .person__info { margin-top: 2px; font-size: 12.5px; color: var(--muted); }
    .aksi { display: flex; justify-content: flex-end; gap: 8px; }
    .btn--edit { background: #e8f3ff; color: var(--primary); height: 36px; padding: 0 14px; }
    .btn--edit:hover { background: #d3e8ff; }
    .btn--del { background: #fef2f2; color: var(--danger); height: 36px; padding: 0 14px; }
    .btn--del:hover { background: #fee2e2; }
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

    /* Pop-up */
    dialog { border: 0; border-radius: 18px; padding: 0; width: min(520px, calc(100vw - 32px)); box-shadow: 0 24px 60px rgba(10,37,99,.28); }
    dialog::backdrop { background: rgba(10,37,99,.45); }
    dialog form { padding: 24px; display: grid; gap: 14px; max-height: calc(100vh - 32px); overflow-y: auto; }
    dialog h3 { font-size: 18px; font-weight: 700; color: var(--navy); }
    dialog p { font-size: 14px; line-height: 1.5; color: #374151; }
    .grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
    .field { min-width: 0; }
    .field label { display: block; margin-bottom: 6px; font-size: 13px; font-weight: 600; color: #1f2937; }
    .field small { display: block; margin-top: 6px; font-size: 12px; color: var(--muted); }
    .input, .select {
        width: 100%; height: 44px; padding: 0 12px;
        border: 1.5px solid #d5dbe5; border-radius: 12px; background: #f6f8fb;
        font: 500 14px var(--font); color: var(--ink); outline: 0;
    }
    .select { cursor: pointer; }
    .input:focus, .select:focus { border-color: var(--primary); background: #fff; box-shadow: 0 0 0 3px rgba(43,114,213,.15); }
    .input.is-invalid, .select.is-invalid { border-color: var(--danger); }
    .field-error { margin-top: 6px; font-size: 12.5px; color: var(--danger); }
    .dlg-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 4px; }

    @media (max-width: 600px) {
        .card { padding: 18px; }
        .grid2 { grid-template-columns: 1fr; }
        .search__box { max-width: none; }
    }
</style>
@endpush

@section('content')
@php
    $halaman = $daftar->currentPage();
    $mulai   = max(1, $halaman - 2);
    $selesai = min($daftar->lastPage(), $halaman + 2);
    $fields  = ['name', 'username', 'program_studi', 'kelas', 'angkatan', 'password'];
@endphp

    {{-- Data untuk skrip halaman ini, dibaca lewat atribut data --}}
    <div id="page-data" hidden
         data-edit-id="{{ session('edit_id') }}"
         data-old-name="{{ old('name') }}"
         data-old-username="{{ old('username') }}"
         data-old-prodi="{{ old('program_studi') }}"
         data-old-kelas="{{ old('kelas') }}"
         data-old-angkatan="{{ old('angkatan') }}"></div>

    @if (session('status'))
        <div class="alert" role="status">{{ session('status') }}</div>
    @endif

    <section class="card">
        <div class="card__head">
            <div class="card__icon"><x-icon name="checklist" :size="20" /></div>
            <h2>Daftar Mahasiswa Aktif</h2>
            <span class="count">{{ $total }} mahasiswa</span>
        </div>

        <form class="search" method="GET" action="{{ route('admin.mahasiswa.index') }}" role="search">
            <label class="search__box">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
                <input type="search" name="q" value="{{ $q }}" placeholder="Cari nama atau NIM" aria-label="Cari nama atau NIM">
            </label>
            <button class="btn btn--primary" type="submit">Cari</button>
            @if ($q !== '')
                <a class="btn btn--ghost" href="{{ route('admin.mahasiswa.index') }}">Reset</a>
            @endif
        </form>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th scope="col" class="col-no">No</th>
                        <th scope="col">Mahasiswa</th>
                        <th scope="col" class="col-aksi">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($daftar as $i => $m)
                        <tr>
                            <td class="col-no">{{ $daftar->firstItem() + $i }}</td>
                            <td>
                                <div class="person">
                                    <div class="avatar" aria-hidden="true">
                                        <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="8" r="4.2"/><path d="M3.5 21c0-4.4 3.8-7 8.5-7s8.5 2.6 8.5 7z"/></svg>
                                    </div>
                                    <div>
                                        <div class="person__nama" style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                                            <span>{{ $m->name }}</span>
                                            @if ($m->isWaVerified())
                                                <span class="badge" title="Terverifikasi: {{ $m->wa_verified_at?->format('d/m/Y H:i') }}{{ $m->waVerifiedBy ? ' oleh ' . $m->waVerifiedBy->name : '' }}" style="font-size:11px; padding:2px 8px; border-radius:999px; background:#dcfce7; color:#166534; font-weight:600;">✓ WA Terverifikasi</span>
                                            @else
                                                <span class="badge" style="font-size:11px; padding:2px 8px; border-radius:999px; background:#fef3c7; color:#92400e; font-weight:600;">Belum Verifikasi</span>
                                            @endif
                                        </div>
                                        <div class="person__info">
                                            {{ $m->username }}
                                            @if ($m->no_wa) · WA: {{ \App\Support\NomorWa::tampil($m->no_wa) }} @endif
                                            @if ($m->program_studi) · {{ $m->program_studi }} @endif
                                            @if ($m->angkatan) · Angkatan {{ $m->angkatan }} @endif
                                            @if ($m->kelas) · Kelas {{ $m->kelas }} @endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="aksi">
                                    @if (! $m->isWaVerified())
                                        <form method="POST" action="{{ route('admin.mahasiswa.verifikasi-manual', $m) }}" onsubmit="return confirm('Verifikasi manual nomor WhatsApp mahasiswa {{ $m->name }}?')" style="display:inline;">
                                            @csrf
                                            <button type="submit" class="btn" style="background:#ecfdf5; color:#047857; height:36px; padding:0 12px; font-weight:600; border:1px solid #a7f3d0;" title="Verifikasi manual nomor WhatsApp"><x-icon name="checklist" :size="15" /> Verifikasi</button>
                                        </form>
                                    @endif
                                    <button type="button" class="btn btn--edit" data-edit
                                            data-id="{{ $m->id }}"
                                            data-url="{{ route('admin.mahasiswa.update', $m) }}"
                                            data-name="{{ $m->name }}"
                                            data-username="{{ $m->username }}"
                                            data-prodi="{{ $m->program_studi }}"
                                            data-kelas="{{ $m->kelas }}"
                                            data-angkatan="{{ $m->angkatan }}"
                                            aria-label="Edit {{ $m->name }}"><x-icon name="edit" :size="16" /> Edit</button>
                                    <button type="button" class="btn btn--del" data-delete
                                            data-url="{{ route('admin.mahasiswa.destroy', $m) }}"
                                            data-label="{{ $m->name }} ({{ $m->username }})"
                                            aria-label="Hapus {{ $m->name }}"><x-icon name="trash" :size="16" /> Hapus</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="empty">
                                {{ $q !== '' ? 'Tidak ada mahasiswa yang cocok dengan "' . $q . '".' : 'Belum ada mahasiswa terdaftar.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="footer">
            <div>
                @if ($daftar->total() > 0)
                    Data {{ $daftar->firstItem() }} - {{ $daftar->lastItem() }} dari {{ $daftar->total() }}
                @else
                    Tidak ada data
                @endif
            </div>

            @if ($daftar->hasPages())
                <nav class="pager" aria-label="Halaman">
                    @if ($daftar->onFirstPage())
                        <span class="is-disabled" aria-hidden="true">&lt;&lt;</span>
                    @else
                        <a href="{{ $daftar->previousPageUrl() }}" aria-label="Halaman sebelumnya">&lt;&lt;</a>
                    @endif

                    @for ($h = $mulai; $h <= $selesai; $h++)
                        @if ($h === $halaman)
                            <span class="is-current" aria-current="page">{{ $h }}</span>
                        @else
                            <a href="{{ $daftar->url($h) }}">{{ $h }}</a>
                        @endif
                    @endfor

                    @if ($daftar->hasMorePages())
                        <a href="{{ $daftar->nextPageUrl() }}" aria-label="Halaman berikutnya">&gt;&gt;</a>
                    @else
                        <span class="is-disabled" aria-hidden="true">&gt;&gt;</span>
                    @endif
                </nav>
            @endif
        </div>
    </section>

    {{-- Pop-up edit --}}
    <dialog id="dlg-edit" aria-labelledby="edit-title">
        <form method="POST" id="form-edit" novalidate>
            @csrf
            @method('PUT')
            <input type="hidden" name="q" value="{{ $q }}">
            <h3 id="edit-title">Edit data mahasiswa</h3>

            <div class="field">
                <label for="e-name">Nama</label>
                <input class="input @if ($errors->edit->has('name')) is-invalid @endif" id="e-name" name="name" type="text" maxlength="100" autocomplete="off" required>
                @if ($errors->edit->has('name')) <p class="field-error" role="alert">{{ $errors->edit->first('name') }}</p> @endif
            </div>

            <div class="field">
                <label for="e-username">NIM (username)</label>
                <input class="input @if ($errors->edit->has('username')) is-invalid @endif" id="e-username" name="username" type="text" inputmode="numeric" pattern="[0-9]{{ '{' . \App\Models\User::NIM_PANJANG . '}' }}" maxlength="{{ \App\Models\User::NIM_PANJANG }}" autocomplete="off" data-digits required>
                @if ($errors->edit->has('username')) <p class="field-error" role="alert">{{ $errors->edit->first('username') }}</p> @endif
            </div>

            <div class="field">
                <label for="e-prodi">Program studi</label>
                <select class="select @if ($errors->edit->has('program_studi')) is-invalid @endif" id="e-prodi" name="program_studi" required>
                    <option value="">Pilih prodi</option>
                    @foreach (\App\Models\User::PROGRAM_STUDI as $p)
                        <option value="{{ $p }}">{{ $p }}</option>
                    @endforeach
                </select>
                @if ($errors->edit->has('program_studi')) <p class="field-error" role="alert">{{ $errors->edit->first('program_studi') }}</p> @endif
            </div>

            <div class="grid2">
                <div class="field">
                    <label for="e-kelas">Kelas</label>
                    <select class="select @if ($errors->edit->has('kelas')) is-invalid @endif" id="e-kelas" name="kelas" required>
                        <option value="">Pilih kelas</option>
                        @foreach (\App\Models\User::KELAS as $k)
                            <option value="{{ $k }}">{{ $k }}</option>
                        @endforeach
                    </select>
                    @if ($errors->edit->has('kelas')) <p class="field-error" role="alert">{{ $errors->edit->first('kelas') }}</p> @endif
                </div>

                <div class="field">
                    <label for="e-angkatan">Angkatan</label>
                    <select class="select @if ($errors->edit->has('angkatan')) is-invalid @endif" id="e-angkatan" name="angkatan" required>
                        <option value="">Pilih angkatan</option>
                        @foreach (range(\App\Models\User::ANGKATAN_MIN, \App\Models\User::ANGKATAN_MAX) as $a)
                            <option value="{{ $a }}">{{ $a }}</option>
                        @endforeach
                    </select>
                    @if ($errors->edit->has('angkatan')) <p class="field-error" role="alert">{{ $errors->edit->first('angkatan') }}</p> @endif
                </div>
            </div>

            <div class="field">
                <label for="e-password">Password baru</label>
                <input class="input @if ($errors->edit->has('password')) is-invalid @endif" id="e-password" name="password" type="password" minlength="8" autocomplete="new-password" placeholder="Kosongkan jika tidak diubah">
                @if ($errors->edit->has('password'))
                    <p class="field-error" role="alert">{{ $errors->edit->first('password') }}</p>
                @else
                    <small>Isi hanya jika ingin mengganti password mahasiswa ini (minimal 8 karakter).</small>
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
            <input type="hidden" name="q" value="{{ $q }}">
            <h3 id="del-title">Hapus data mahasiswa?</h3>
            <p><strong id="del-label"></strong> akan dihapus beserta seluruh dokumen yang pernah diunggahnya. Tindakan ini tidak bisa dibatalkan.</p>
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

    /* Kolom khusus angka (NIM, angkatan): huruf atau simbol yang diketik/ditempel langsung dibuang */
    document.querySelectorAll('[data-digits]').forEach(function (input) {
        input.addEventListener('input', function () {
            var bersih = input.value.replace(/\D+/g, '');
            if (input.maxLength > 0) bersih = bersih.slice(0, input.maxLength);
            if (bersih !== input.value) input.value = bersih;
        });
    });

    function openEdit(btn, v) {
        document.getElementById('form-edit').action = btn.dataset.url;
        document.getElementById('e-name').value = v.name;
        document.getElementById('e-username').value = v.username;
        document.getElementById('e-prodi').value = v.prodi;
        document.getElementById('e-kelas').value = v.kelas;
        document.getElementById('e-angkatan').value = v.angkatan;
        document.getElementById('e-password').value = '';
        dlgEdit.showModal();
    }

    document.addEventListener('click', function (e) {
        var edit = e.target.closest('[data-edit]');
        if (edit) {
            openEdit(edit, {
                name: edit.dataset.name, username: edit.dataset.username, prodi: edit.dataset.prodi,
                kelas: edit.dataset.kelas, angkatan: edit.dataset.angkatan
            });
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
        if (btn) {
            openEdit(btn, {
                name: data.oldName, username: data.oldUsername, prodi: data.oldProdi,
                kelas: data.oldKelas, angkatan: data.oldAngkatan
            });
        }
    }
})();
</script>
@endpush