@props(['jadwal' => null])
@php
    use App\Models\JadwalPendadaran as J;

    $belum = auth()->user()->syaratBelumDisetujui();
    $dokOk = $belum->isEmpty();
    $ulang = $jadwal && $jadwal->hasil === J::TIDAK_LULUS; // siklus baru untuk ujian ulang
    $aktif = $jadwal && ! $ulang ? $jadwal : null;
    $ok    = $aktif && $aktif->status === J::DISETUJUI;

    // [judul, keterangan, status: selesai|sekarang|gagal|nanti, tautan]
    $langkah = [
        ['Dokumen persyaratan', $dokOk ? 'Semua dokumen disetujui' : $belum->count() . ' dokumen belum disetujui',
            $dokOk ? 'selesai' : 'sekarang', route('mahasiswa.dokumen.index')],

        match (true) {
            ! $aktif => ['Pengajuan jadwal', $ulang ? 'Ajukan jadwal ujian ulang' : 'Belum diajukan', $dokOk ? 'sekarang' : 'nanti', route('mahasiswa.jadwal.index')],
            $aktif->status === J::DITOLAK => ['Pengajuan jadwal', 'Ditolak, kirim perbaikan', 'gagal', route('mahasiswa.jadwal.index')],
            default => ['Pengajuan jadwal', 'Diajukan ' . $aktif->created_at?->locale('id')->translatedFormat('d M Y'), 'selesai', route('mahasiswa.jadwal.index')],
        },

        match (true) {
            ! $aktif => ['Bukti pembayaran', 'Setelah jadwal diajukan', 'nanti', null],
            $ok || $aktif->buktiDisetujui() => ['Bukti pembayaran', 'Disetujui', 'selesai', route('mahasiswa.pembayaran.index')],
            ! $aktif->bukti_bayar_path => ['Bukti pembayaran', 'Belum diunggah', 'sekarang', route('mahasiswa.pembayaran.index')],
            $aktif->buktiDitolak() => ['Bukti pembayaran', 'Ditolak, unggah ulang', 'gagal', route('mahasiswa.pembayaran.index')],
            default => ['Bukti pembayaran', 'Sedang diperiksa', 'sekarang', route('mahasiswa.pembayaran.index')],
        },

        match (true) {
            $ok => ['Verifikasi jadwal', 'Disetujui ' . $aktif->diverifikasi_at?->locale('id')->translatedFormat('d M Y'), 'selesai', null],
            $aktif && $aktif->status === J::PENDING && $aktif->buktiDisetujui() => ['Verifikasi jadwal', 'Sedang diverifikasi', 'sekarang', null],
            default => ['Verifikasi jadwal', 'Setelah bukti bayar disetujui', 'nanti', null],
        },

        match (true) {
            $ok && $aktif->undangan_path => ['Undangan ujian', 'Tersedia untuk diunduh', 'selesai', route('mahasiswa.undangan.index')],
            $ok => ['Undangan ujian', 'Sedang disiapkan pengajaran', 'sekarang', route('mahasiswa.undangan.index')],
            default => ['Undangan ujian', 'Setelah jadwal disetujui', 'nanti', null],
        },
    ];
@endphp

<section class="panel panel--side">
    <h2 class="panel__title">Alur pengajuan @if ($ulang) <small>Ujian ulang</small> @endif</h2>
    <ol class="alur">
        @foreach ($langkah as $i => [$judul, $ket, $status, $tautan])
            <li class="alur__item alur__item--{{ $status }}" @if ($status === 'sekarang') aria-current="step" @endif>
                <span class="alur__dot">
                    @if ($status === 'selesai') <x-icon name="check" :size="14" />
                    @elseif ($status === 'gagal') <x-icon name="close" :size="14" />
                    @else {{ $i + 1 }}
                    @endif
                </span>
                <span class="alur__body">
                    @if ($tautan && $status !== 'nanti' && url()->current() !== $tautan)
                        <a class="alur__judul" href="{{ $tautan }}">{{ $judul }}</a>
                    @else
                        <span class="alur__judul">{{ $judul }}</span>
                    @endif
                    <span class="alur__ket">{{ $ket }}</span>
                </span>
            </li>
        @endforeach
    </ol>
</section>

@once
@push('styles')
<style>
    .alur { list-style: none; display: flex; flex-direction: column; }
    .alur__item { position: relative; display: flex; gap: 12px; padding-bottom: 18px; }
    .alur__item:last-child { padding-bottom: 0; }
    .alur__item:not(:last-child)::after { content: ''; position: absolute; left: 13px; top: 30px; bottom: 2px; width: 2px; background: var(--line); }
    .alur__item--selesai:not(:last-child)::after { background: #86efac; }
    .alur__dot { flex: none; width: 28px; height: 28px; border-radius: 50%; display: grid; place-items: center;
        font-size: 12px; font-weight: 800; background: #f1f5f9; color: #94a3b8; border: 2px solid #e2e8f0; }
    .alur__item--selesai .alur__dot { background: #16a34a; border-color: #16a34a; color: #fff; }
    .alur__item--sekarang .alur__dot { background: #fff; border-color: var(--primary); color: var(--primary); box-shadow: 0 0 0 4px rgba(43,114,213,.12); }
    .alur__item--gagal .alur__dot { background: #dc2626; border-color: #dc2626; color: #fff; }
    .alur__body { display: flex; flex-direction: column; gap: 2px; padding-top: 4px; min-width: 0; }
    .alur__judul { font-size: 13.5px; font-weight: 700; color: var(--navy); text-decoration: none; }
    a.alur__judul:hover { color: var(--primary); text-decoration: underline; }
    .alur__item--nanti .alur__judul { color: #94a3b8; }
    .alur__ket { font-size: 12.5px; color: var(--muted); }
    .alur__item--gagal .alur__ket { color: #b91c1c; font-weight: 600; }
    .alur__item--sekarang .alur__ket { color: var(--primary-dark); font-weight: 600; }
</style>
@endpush
@endonce
