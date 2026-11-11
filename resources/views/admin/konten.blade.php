@extends('layouts.admin')

@section('title', 'Edit Konten')
@section('crumb', 'Edit Konten')
@section('page_title', 'Edit Konten Dashboard')

@push('styles')
<style>
    .alert { margin-bottom: 16px; padding: 12px 16px; border-radius: 12px; background: #dcfce7; color: #166534; font-size: 14px; font-weight: 600; }
    .alert--error { background: #fee2e2; color: #991b1b; }
    .editor { display: grid; gap: 16px; max-width: 880px; }
    .editor__card { background: #fff; border: 1px solid var(--line); border-radius: 16px; padding: 20px 22px; scroll-margin-top: 16px; }
    .editor__card h3 { font-size: 17px; font-weight: 700; color: var(--navy); }
    .editor__hint { margin-top: 4px; font-size: 13px; color: var(--muted); }
    .editor textarea {
        margin-top: 12px; width: 100%; min-height: 180px; resize: vertical;
        padding: 12px 14px; border: 1.5px solid #d5dbe5; border-radius: 12px; background: #f6f8fb;
        font: 500 14px/1.6 var(--font); color: var(--ink); outline: 0;
    }
    .editor textarea:focus { border-color: var(--primary); background: #fff; box-shadow: 0 0 0 3px rgba(43,114,213,.15); }
    .editor .invalid { border-color: #c62828; }
    .editor__error { margin-top: 6px; font-size: 12.5px; color: #c62828; }
    .editor__actions { display: flex; gap: 12px; }
</style>
@endpush

@section('content')
    @if (session('status'))
        <div class="alert" role="status">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert--error" role="alert">Ada isian yang belum benar. Periksa kembali di bawah.</div>
    @endif

    <form class="editor" method="POST" action="{{ route('admin.konten.update') }}">
        @csrf
        @method('PUT')

        @foreach (\App\Models\KontenDashboard::JUDUL as $kunci => $judul)
            <section class="editor__card" id="{{ $kunci }}">
                <h3>{{ $judul }}</h3>
                <p class="editor__hint">Tulis satu poin per baris. Urutan baris menjadi urutan tampilan.</p>
                <textarea name="{{ $kunci }}" class="@error($kunci) invalid @enderror"
                          aria-label="Isi {{ $judul }}">{{ old($kunci, implode("\n", $konten[$kunci])) }}</textarea>
                @error($kunci)
                    <p class="editor__error">{{ $message }}</p>
                @enderror
            </section>
        @endforeach

        <div class="editor__actions">
            <button class="btn btn--primary" type="submit">Simpan perubahan</button>
            <a class="btn btn--ghost" href="{{ route('admin.dashboard') }}">Kembali ke dashboard</a>
        </div>
    </form>
@endsection