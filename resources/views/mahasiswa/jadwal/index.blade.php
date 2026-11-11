@extends('layouts.mahasiswa')

@section('title', 'Upload Jadwal Pendadaran')
@section('crumb', 'Upload Jadwal')
@section('page_title', 'Jadwal Pendadaran')

@push('styles')
<style>
    .alert { padding: 12px 16px; border-radius: 12px; font-size: 14px; font-weight: 600; margin-bottom: 20px; }
    .alert-success { background: #dcfce7; color: #166534; }
    .alert-error { background: #fee2e2; color: #991b1b; }
    .alert-warning { background: #fef3c7; color: #b45309; }

    .form-grid { display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap: 0 18px; }
    .form-grid > .form-group { grid-column: span 3; }
    .form-grid > .form-group--full { grid-column: 1 / -1; }
    .form-grid > .form-group--third { grid-column: span 2; }
    .form-group { margin-bottom: 18px; min-width: 0; }
    .form-actions { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; padding-top: 18px; margin-top: 2px; border-top: 1px solid var(--line); }
    .form-actions .hint { margin: 0; }
    .form-label { display: block; font-size: 13px; font-weight: 700; color: var(--navy); margin-bottom: 6px; }
    .form-input { 
        width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 10px; 
        font-family: inherit; font-size: 14px; color: var(--ink); background: #fff;
    }
    .form-input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(43, 114, 213, 0.1); }
    .form-input[readonly] { background: #f8fafc; color: var(--muted); cursor: not-allowed; }
    .error-text { font-size: 12px; color: var(--danger); font-weight: 500; margin-top: 4px; display: block; }
    
    .status-badge { display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 8px; font-size: 13px; font-weight: 600; margin-bottom: 20px; }
    .status-pending { background: #fef3c7; color: #b45309; }
    .status-disetujui { background: #dcfce7; color: #15803d; }
    .status-ditolak { background: #fee2e2; color: #b91c1c; }
    .status-lulus { background: #dcfce7; color: #15803d; }
    .status-tidak_lulus { background: #fee2e2; color: #b91c1c; }
    .alert-info { background: #e3eefc; color: var(--navy); font-weight: 500; }
    .alert a { color: inherit; font-weight: 700; }
    .alert .alasan { display: block; margin-top: 4px; font-weight: 500; }
    .file-now { display: flex; align-items: center; gap: 10px; padding: 10px 12px; margin-bottom: 8px; border: 1px solid var(--line); border-radius: 10px; background: #f8fafc; font-size: 13px; }
    .file-now span { flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-weight: 600; }
    .file-now a { color: var(--primary); font-weight: 700; text-decoration: none; }
    .hint { font-size: 12px; color: var(--muted); display: block; margin-top: 6px; }
    .hasil { display: flex; flex-wrap: wrap; gap: 10px 24px; align-items: center; padding: 14px 16px; margin-bottom: 20px; border-radius: 12px; background: #f6f8fb; }
    .hasil b { font-size: 24px; color: var(--navy); font-variant-numeric: tabular-nums; }
    .huruf { display: inline-grid; place-items: center; width: 30px; height: 30px; border-radius: 8px; background: #e3eefc; color: var(--primary); font-weight: 800; }
    .riwayat table { width: 100%; min-width: 480px; border-collapse: collapse; font-size: 13.5px; }
    .riwayat th, .riwayat td { padding: 10px 12px; border-bottom: 1px solid var(--line); text-align: left; vertical-align: top; }
    .riwayat th { font-size: 11.5px; color: var(--muted); text-transform: uppercase; letter-spacing: .04em; background: #f8fafc; }
    .riwayat tr:last-child td { border-bottom: 0; }
    .card-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin-bottom: 18px; }
    .card-head .status-badge { margin: 0; }
    .card-head h2 { font-size: 16px; font-weight: 800; color: var(--navy); }
    @media (max-width: 900px) {
        .form-grid > .form-group, .form-grid > .form-group--third { grid-column: 1 / -1; }
        .form-grid > .form-group--waktu { grid-column: span 3; }
    }
    @media (max-width: 560px) { .form-grid > .form-group--waktu { grid-column: 1 / -1; } }
</style>
@endpush

@section('content')
@php
    use App\Models\JadwalPendadaran as J;
    $ujianUlang = $jadwal && $jadwal->hasil === J::TIDAK_LULUS;
    $disetujui  = $jadwal && $jadwal->status === J::DISETUJUI && ! $ujianUlang;
    $terkunci   = ! $disetujui && $belumLengkap->isNotEmpty();
    $readonly   = $disetujui || $terkunci;
    $pending    = $jadwal && $jadwal->status === J::PENDING;
    // Untuk ujian ulang: judul dan dosen diisi dari pengajuan sebelumnya, tanggal dan jam dikosongkan
    $isi = $disetujui || $pending || ($jadwal && $jadwal->status === J::DITOLAK) || $ujianUlang ? $jadwal : null;
    $isiWaktu = $ujianUlang ? null : $isi;
    $statusLabel = $jadwal ? ($jadwal->hasil ? $jadwal->labelHasil() : ['pending' => 'Menunggu verifikasi', 'disetujui' => 'Disetujui', 'ditolak' => 'Ditolak'][$jadwal->status]) : null;
    $statusKelas = $jadwal ? ($jadwal->hasil ?: $jadwal->status) : null;
@endphp

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if (session('error'))
    <div class="alert alert-error">{{ session('error') }}</div>
@endif

<div class="split">
<div class="split__main">
<section class="panel">
    <div class="card-head">
        <h2>{{ $ujianUlang ? 'Pengajuan ujian ulang' : 'Data pengajuan jadwal' }}</h2>
        @if ($jadwal)
            <div class="status-badge status-{{ $statusKelas }}">
                @if (in_array($statusKelas, ['disetujui', 'lulus'], true)) <x-icon name="check" :size="16" />
                @elseif (in_array($statusKelas, ['ditolak', 'tidak_lulus'], true)) <x-icon name="close" :size="16" />
                @else <x-icon name="clock" :size="16" />
                @endif
                Status: {{ $statusLabel }}
            </div>
        @endif
    </div>
    @if ($jadwal)

        @if ($jadwal->hasil)
            <div class="hasil">
                <span>Nilai akhir <b>{{ J::formatNilai($jadwal->nilai_akhir) }}</b></span>
                <span class="huruf">{{ $jadwal->hurufAkhir() }}</span>
                <span>{{ $jadwal->labelHasil() }} · ujian {{ $jadwal->tanggalIndo() }}</span>
            </div>
        @endif

        @if ($jadwal->status === J::DITOLAK)
            <div class="alert alert-error">
                Pengajuan jadwal Anda ditolak. Perbaiki data di bawah lalu klik <b>Kirim Perbaikan</b>; pengajuan Anda tetap sama, tidak perlu membuat yang baru.
                @if ($jadwal->alasan_tolak) <span class="alasan">Alasan: {{ $jadwal->alasan_tolak }}</span> @endif
            </div>
        @elseif ($pending)
            <div class="alert alert-warning">
                @if ($jadwal->buktiDitolak())
                    <b>Bukti pembayaran ditolak</b> ({{ $jadwal->bukti_bayar_alasan }}). Jadwal tidak perlu diajukan ulang; cukup unggah bukti yang baru di menu
                    <a href="{{ route('mahasiswa.pembayaran.index') }}">Upload Bukti Pembayaran</a>.
                @elseif ($jadwal->buktiDisetujui())
                    Bukti pembayaran sudah disetujui. Jadwal Anda sedang diverifikasi; Anda masih dapat mengubah data jadwal.
                @elseif ($jadwal->bukti_bayar_path)
                    Bukti pembayaran sedang diperiksa. Setelah disetujui, jadwal Anda diverifikasi. Anda masih dapat mengubah data jadwal.
                @else
                    Jadwal sudah diajukan, tetapi <b>bukti pembayaran belum diunggah</b>. Jadwal baru bisa disetujui setelah Anda mengunggahnya di menu
                    <a href="{{ route('mahasiswa.pembayaran.index') }}">Upload Bukti Pembayaran</a>.
                @endif
            </div>
        @elseif ($ujianUlang)
            <div class="alert alert-error">
                Anda belum lulus pada ujian ini. Ajukan jadwal ujian ulang di bawah, lalu unggah bukti pembayaran ujian ulang di menu Upload Bukti Pembayaran. Dokumen persyaratan tidak perlu diunggah lagi.
            </div>
        @elseif ($jadwal->lulus())
            <div class="alert alert-success">
                Selamat, Anda lulus ujian pendadaran. Daftarkan diri ke periode yudisium yang dibuka melalui <a href="{{ route('mahasiswa.dashboard') }}">Dashboard</a>.
            </div>
        @elseif ($disetujui)
            <div class="alert alert-info">
                @if ($jadwal->undangan_path)
                    Jadwal disetujui. Undangan ujian sudah tersedia di menu <a href="{{ route('mahasiswa.undangan.index') }}">Undangan Jadwal Ujian</a>.
                @else
                    Jadwal disetujui. Undangan ujian sedang disiapkan oleh bagian pengajaran.
                @endif
            </div>
        @endif
    @else
        <p style="font-size: 14px; color: var(--muted); margin-bottom: 20px; line-height: 1.5;">Silakan isi formulir pengajuan jadwal ujian pendadaran. Setelah itu unggah bukti pembayaran di menu Upload Bukti Pembayaran.</p>
    @endif

    @if ($terkunci)
        <div class="alert alert-warning">
            Jadwal baru bisa diajukan setelah semua dokumen persyaratan disetujui. Dokumen yang belum disetujui:
            <ul style="margin: 8px 0 8px 18px; font-weight: 500;">
                @foreach ($belumLengkap as $syarat)
                    <li>{{ $syarat->nama }}</li>
                @endforeach
            </ul>
            <a href="{{ route('mahasiswa.dokumen.index') }}" style="color: inherit;">Buka halaman Upload Dokumen &rarr;</a>
        </div>
    @endif

    <form action="{{ route('mahasiswa.jadwal.store') }}" method="POST">
        @csrf

        <div class="form-grid">
        <div class="form-group form-group--full">
            <label class="form-label" for="judul">Judul Tugas Akhir / Tesis</label>
            <textarea class="form-input" id="judul" name="judul" rows="3" required {{ $readonly ? 'readonly' : '' }}>{{ old('judul', $isi?->judul) }}</textarea>
            @error('judul') <span class="error-text">{{ $message }}</span> @enderror
        </div>

        <div class="form-group">
            <label class="form-label" for="pembimbing_1">Pembimbing Utama</label>
            <input type="text" class="form-input" id="pembimbing_1" name="pembimbing_1" list="dosen-list" value="{{ old('pembimbing_1', $isi?->pembimbing_1) }}" required autocomplete="off" {{ $readonly ? 'readonly' : '' }}>
            @error('pembimbing_1') <span class="error-text">{{ $message }}</span> @enderror
        </div>

        <div class="form-group">
            <label class="form-label" for="pembimbing_2">Pembimbing Pendamping</label>
            <input type="text" class="form-input" id="pembimbing_2" name="pembimbing_2" list="dosen-list" value="{{ old('pembimbing_2', $isi?->pembimbing_2) }}" required autocomplete="off" {{ $readonly ? 'readonly' : '' }}>
            @error('pembimbing_2') <span class="error-text">{{ $message }}</span> @enderror
        </div>

        <div class="form-group form-group--third">
            <label class="form-label" for="penguji">Penguji</label>
            <input type="text" class="form-input" id="penguji" name="penguji" list="dosen-list" value="{{ old('penguji', $isi?->penguji) }}" required autocomplete="off" {{ $readonly ? 'readonly' : '' }}>
            @error('penguji') <span class="error-text">{{ $message }}</span> @enderror
        </div>

            <div class="form-group form-group--third form-group--waktu">
                <label class="form-label" for="tanggal_ujian">Tanggal Ujian</label>
                <input type="date" class="form-input" id="tanggal_ujian" name="tanggal_ujian" value="{{ old('tanggal_ujian', $isiWaktu?->tanggal_ujian?->format('Y-m-d')) }}" required min="{{ now()->addDay()->format('Y-m-d') }}" {{ $readonly ? 'readonly' : '' }}>
                @error('tanggal_ujian') <span class="error-text">{{ $message }}</span> @enderror
            </div>

            <div class="form-group form-group--third form-group--waktu">
                <label class="form-label" for="jam_ujian">Jam Ujian (WIB)</label>
                <input type="time" class="form-input" id="jam_ujian" name="jam_ujian" value="{{ old('jam_ujian', $isiWaktu ? substr($isiWaktu->jam_ujian, 0, 5) : '') }}" required min="{{ J::JAM_MULAI_PALING_AWAL }}" max="{{ J::JAM_MULAI_PALING_AKHIR }}" {{ $readonly ? 'readonly' : '' }}>
                @error('jam_ujian') <span class="error-text">{{ $message }}</span> @enderror
                <span class="hint">Senin–Jumat, mulai {{ J::JAM_MULAI_PALING_AWAL }}–{{ J::JAM_MULAI_PALING_AKHIR }} WIB</span>
            </div>
        </div>

        @if (! $readonly)
            <datalist id="dosen-list">
                @foreach ($dosen as $d)
                    <option value="{{ $d->nama }}">{{ $d->nidn ? 'NIDN: '.$d->nidn : '' }}</option>
                @endforeach
            </datalist>

            <div class="form-actions">
                <button type="submit" class="btn btn--primary">
                    <x-icon name="send" :size="18" />
                    {{ $jadwal && $jadwal->status === J::DITOLAK ? 'Kirim Perbaikan' : ($pending ? 'Perbarui Jadwal' : ($ujianUlang ? 'Ajukan Ujian Ulang' : 'Ajukan Jadwal')) }}
                </button>
                <span class="hint">Nama dosen dipilih dari daftar yang muncul saat mengetik.</span>
            </div>
        @endif
    </form>
</section>

@if ($jadwal && $jadwal->riwayatPenolakan->isNotEmpty())
    <section class="panel riwayat">
        <h2 class="panel__title">Riwayat penolakan jadwal <small>{{ $jadwal->riwayatPenolakan->count() }} kali</small></h2>
        <div class="tabel-wrap tabel-tumpuk"><table>
            <thead><tr><th>Ditolak</th><th>Jadwal yang diajukan</th><th>Alasan</th></tr></thead>
            <tbody>
                @foreach ($jadwal->riwayatPenolakan as $rp)
                    <tr>
                        <td data-label="Ditolak">{{ $rp->ditolak_at->locale('id')->translatedFormat('d M Y H:i') }}</td>
                        <td data-label="Jadwal">{{ $rp->waktu() }}</td>
                        <td data-label="Alasan">{{ $rp->alasan ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table></div>
    </section>
@endif

@if ($riwayat->count() > 1)
    <section class="panel riwayat">
        <h2 class="panel__title">Riwayat ujian</h2>
        <div class="tabel-wrap tabel-tumpuk"><table>
            <thead><tr><th>Tanggal ujian</th><th>Status jadwal</th><th>Hasil ujian</th></tr></thead>
            <tbody>
                @foreach ($riwayat as $r)
                    <tr>
                        <td data-label="Tanggal">{{ $r->tanggalIndo() }}</td>
                        <td data-label="Status">{{ ucfirst($r->status) }}@if ($r->status === J::DITOLAK && $r->alasan_tolak)<br><small style="color:var(--muted)">{{ $r->alasan_tolak }}</small>@endif</td>
                        <td data-label="Hasil ujian">{{ $r->hasil ? $r->labelHasil() . ' (' . J::formatNilai($r->nilai_akhir) . ')' : ($r->status === J::DISETUJUI ? 'Belum ujian' : '-') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table></div>
    </section>
@endif

</div>

<aside class="split__side">
    <x-alur-jadwal :jadwal="$jadwal" />

    <section class="panel panel--side">
        <h2 class="panel__title">Ketentuan jadwal</h2>
        <ul class="ket">
            <li>Diajukan setelah semua dokumen persyaratan disetujui.</li>
            <li>Ujian hari Senin–Jumat, paling cepat besok, jam mulai {{ J::JAM_MULAI_PALING_AWAL }}–{{ J::JAM_MULAI_PALING_AKHIR }} WIB.</li>
            <li>Pembimbing Utama, Pembimbing Pendamping, dan Penguji harus dosen yang berbeda.</li>
            <li>Dosen yang sama tidak bisa dijadwalkan dengan selisih kurang dari {{ J::DURASI_MENIT / 60 }} jam.</li>
            <li>Data masih bisa diubah selama belum disetujui. Jika ditolak, perbaiki di formulir ini.</li>
        </ul>
    </section>
</aside>
</div>
@endsection
