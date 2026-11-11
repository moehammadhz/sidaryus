@extends('layouts.mahasiswa')

@section('title', 'Upload Dokumen')
@section('crumb', 'Upload Dokumen')
@section('page_title', 'Upload Dokumen')

@push('styles')
<style>
    .alert { padding: 12px 16px; border-radius: 12px; font-size: 14px; font-weight: 600; margin-bottom: 20px; }
    .alert-success { background: #dcfce7; color: #166534; }
    .alert-error { background: #fee2e2; color: #991b1b; }

    /* minmax(0, 1fr): kolom boleh lebih sempit dari isinya, sehingga nama berkas panjang
       dipotong dengan "..." dan tidak mendorong kartu melebihi layar */
    .doc-grid { display: grid; gap: 16px; grid-template-columns: minmax(0, 1fr); }
    .doc-card {
        background: #fff; border: 1px solid var(--line); border-radius: 16px; padding: 20px;
        display: flex; flex-direction: column; gap: 16px; min-width: 0;
    }
    .doc-card > * { min-width: 0; }
    /* Kartu dibagi 3 lajur: kepala (kode, judul, status) / info berkas / tombol.
       Dengan subgrid, tinggi tiap lajur disamakan untuk semua kartu di baris yang sama,
       sehingga info berkas dan tombol Hapus selalu sejajar walau panjang judul berbeda. */
    .doc-info, .doc-aksi { display: flex; flex-direction: column; gap: 12px; }
    .doc-info:empty, .doc-aksi:empty { display: none; }
    @supports (grid-template-rows: subgrid) {
        .doc-grid { row-gap: 16px; }
        .doc-card { display: grid; grid-row: span 3; grid-template-rows: subgrid; grid-template-columns: minmax(0, 1fr); row-gap: 16px; }
        .doc-info:empty, .doc-aksi:empty { display: flex; }
    }
    .doc-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; }
    .doc-head > div:first-child { flex: 1; min-width: 0; }
    .doc-head .status { flex: none; white-space: nowrap; }
    .doc-title { font-size: 16px; font-weight: 700; color: var(--navy); overflow-wrap: anywhere; }
    .doc-kode { display: inline-block; padding: 2px 8px; border-radius: 6px; background: #e3eefc; color: var(--primary-dark); font-size: 11px; font-weight: 700; margin-bottom: 6px; }

    .status { display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 8px; font-size: 13px; font-weight: 600; }
    .status-menunggu { background: #fef3c7; color: #b45309; }
    .status-disetujui { background: #dcfce7; color: #15803d; }
    .status-ditolak { background: #fee2e2; color: #b91c1c; }

    .file-info { min-width: 0; display: flex; align-items: center; gap: 12px; padding: 12px; background: #f6f8fb; border-radius: 10px; font-size: 13px; color: #374151; }
    .file-info__icon { color: var(--primary); flex-shrink: 0; display: flex; }
    .file-info__text { min-width: 0; }
    .file-name { font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .file-meta { color: var(--muted); font-size: 12px; margin-top: 2px; }

    .catatan { padding: 12px; border-left: 3px solid #ef4444; background: #fef2f2; font-size: 13px; color: #991b1b; }
    .catatan strong { display: block; margin-bottom: 4px; }

    .upload-form { display: flex; flex-direction: column; gap: 12px; }
    .upload-form input[type="file"] {
        display: block; width: 100%; max-width: 100%; padding: 8px; border: 1px dashed #cbd5e1; border-radius: 10px;
        font-size: 13px; color: var(--muted); cursor: pointer;
    }
    .upload-form input[type="file"]::file-selector-button {
        background: #f1f5f9; border: 0; border-radius: 6px; padding: 6px 12px; font-weight: 600; color: var(--navy); cursor: pointer; margin-right: 12px;
    }
    .upload-form input[type="file"]:hover { border-color: var(--primary); }
    .error-text { font-size: 12px; color: var(--danger); font-weight: 500; }

    .actions { display: flex; gap: 8px; }

    @media (min-width: 768px) {
        .doc-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (min-width: 1024px) {
        .doc-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    }
</style>
@endpush

@section('content')

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if (session('error'))
    <div class="alert alert-error">{{ session('error') }}</div>
@endif
@if ($errors->any())
    <div class="alert alert-error">Ada kesalahan dalam mengunggah dokumen. Periksa kembali form Anda.</div>
@endif

<div class="doc-grid">
    @foreach ($persyaratan as $p)
        @php
            $dok = $dokumen->get($p->id);
        @endphp
        <div class="doc-card">
            <div class="doc-head">
                <div>
                    <div class="doc-kode">{{ $p->kode }}</div>
                    <div class="doc-title">{{ $p->nama }}</div>
                </div>
                @if ($dok)
                    <div class="status status-{{ $dok->status }}">
                        @if ($dok->status === 'disetujui') <x-icon name="check" :size="16" />
                        @elseif ($dok->status === 'ditolak') <x-icon name="close" :size="16" />
                        @else <x-icon name="clock" :size="16" />
                        @endif
                        {{ ucfirst($dok->status) }}
                    </div>
                @endif
            </div>

            <div class="doc-info">
            @if ($dok)
                <div class="file-info">
                    <div class="file-info__icon"><x-icon name="file" :size="24" /></div>
                    <div class="file-info__text">
                        <div class="file-name" title="{{ $dok->nama_asli }}">{{ $dok->nama_asli }}</div>
                        <div class="file-meta">{{ $dok->ukuranLabel() }} · Diunggah {{ $dok->diunggah_at->format('d/m/Y H:i') }}</div>
                    </div>
                </div>

                @if ($dok->status === 'ditolak' && $dok->catatan)
                    <div class="catatan">
                        <strong>Catatan Penolakan:</strong>
                        {{ $dok->catatan }}
                    </div>
                @endif
            @endif
            </div>

            <div class="doc-aksi">
            @if ($dok)
                @if ($dok->status !== 'disetujui')
                    <div class="actions">
                        <form action="{{ route('mahasiswa.dokumen.destroy', $dok) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus dokumen ini?');" style="flex: 1;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn--danger" style="width: 100%;">Hapus</button>
                        </form>
                    </div>
                @endif
            @endif

            @if (!$dok || $dok->status === 'ditolak')
                <form action="{{ route('mahasiswa.dokumen.store') }}" method="POST" enctype="multipart/form-data" class="upload-form">
                    @csrf
                    <input type="hidden" name="persyaratan_id" value="{{ $p->id }}">
                    <div>
                        <input type="file" name="file" accept=".pdf,application/pdf" required>
                        <div class="file-meta" style="margin-top: 4px;">Format PDF, maksimal 5 MB.</div>
                        @if ((string) old('persyaratan_id') === (string) $p->id)
                            @error('file')
                                <div class="error-text mt-1">{{ $message }}</div>
                            @enderror
                        @endif
                    </div>
                    <button type="submit" class="btn btn--primary" style="width: 100%;">
                        <x-icon name="upload" :size="18" /> {{ $dok ? 'Unggah Ulang' : 'Unggah Dokumen' }}
                    </button>
                </form>
            @endif
            </div>
        </div>
    @endforeach
</div>

@endsection
