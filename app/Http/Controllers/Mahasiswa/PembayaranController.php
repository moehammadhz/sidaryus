<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Models\JadwalPendadaran;
use App\Models\RiwayatBuktiBayar;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Bukti pembayaran ujian pendadaran, terpisah dari pengajuan jadwal.
 * Jadwal tetap bisa diajukan tanpa bukti bayar, tetapi baru bisa disetujui
 * (dan undangan diterbitkan) setelah bukti bayar diunggah.
 */
class PembayaranController extends Controller
{
    public function index(Request $request): View
    {
        $jadwal = $request->user()->jadwalTerakhir();

        return view('mahasiswa.pembayaran', [
            'jadwal'   => $jadwal?->load('riwayatBuktiBayar'),
            'bisaUbah' => $this->bisaUbah($jadwal),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $jadwal = $request->user()->jadwalTerakhir();

        if (! $jadwal) {
            return back()->with('error', 'Ajukan jadwal pendadaran terlebih dahulu, lalu unggah bukti pembayarannya.');
        }
        if (! $this->bisaUbah($jadwal)) {
            return back()->with('error', $jadwal->status === JadwalPendadaran::DISETUJUI && $jadwal->hasil === JadwalPendadaran::TIDAK_LULUS
                ? 'Ajukan jadwal ujian ulang terlebih dahulu, lalu unggah bukti pembayaran ujian ulang.'
                : ($jadwal->buktiDisetujui()
                    ? 'Bukti pembayaran sudah disetujui sehingga tidak bisa diganti.'
                    : 'Bukti pembayaran tidak bisa diubah karena jadwal sudah disetujui.'));
        }

        $maksMb = JadwalPendadaran::BUKTI_BAYAR_MAKS_KB / 1024;
        $request->validate([
            'bukti_bayar' => ['required', 'file',
                'mimes:' . implode(',', JadwalPendadaran::BUKTI_BAYAR_MIMES),
                'mimetypes:application/pdf,image/jpeg,image/png',
                'max:' . JadwalPendadaran::BUKTI_BAYAR_MAKS_KB],
        ], [
            'bukti_bayar.required'  => 'Pilih berkas bukti pembayaran.',
            'bukti_bayar.file'      => 'Unggahan bukti pembayaran tidak valid.',
            'bukti_bayar.mimes'     => 'Bukti pembayaran harus berupa PDF, JPG, atau PNG.',
            'bukti_bayar.mimetypes' => 'Bukti pembayaran harus berupa PDF, JPG, atau PNG.',
            'bukti_bayar.max'       => "Ukuran bukti pembayaran maksimal {$maksMb} MB.",
            'bukti_bayar.uploaded'  => "Bukti pembayaran gagal diunggah. Pastikan ukurannya tidak lebih dari {$maksMb} MB.",
        ]);

        $file = $request->file('bukti_bayar');
        $path = $file->store("bukti-bayar/{$jadwal->user_id}", 'local');

        if (! $path) {
            return back()->with('error', 'Bukti pembayaran gagal disimpan. Silakan coba lagi.');
        }

        $lama = $jadwal->bukti_bayar_path;
        $pernahDitolak = $jadwal->buktiDitolak();
        $jadwal->update([
            'bukti_bayar_path'   => $path,
            'bukti_bayar_nama'   => Str::limit($file->getClientOriginalName(), 255, ''),
            'bukti_bayar_mime'   => $file->getMimeType(),
            'bukti_bayar_status' => JadwalPendadaran::BAYAR_MENUNGGU,
            'bukti_bayar_alasan' => null,
        ]);

        // Berkas lama dihapus hanya jika belum pernah diperiksa dan tidak dipakai di tempat lain.
        // Bukti yang ditolak tetap disimpan sebagai riwayat.
        if ($lama && ! JadwalPendadaran::where('bukti_bayar_path', $lama)->exists()
            && ! RiwayatBuktiBayar::where('path', $lama)->exists()) {
            Storage::disk('local')->delete($lama);
        }

        $pesan = $pernahDitolak ? 'Bukti pembayaran baru diunggah.' : ($lama ? 'Bukti pembayaran diganti.' : 'Bukti pembayaran diunggah.');

        return back()->with('success', $jadwal->status === JadwalPendadaran::PENDING
            ? "{$pesan} Bukti Anda kini menunggu pemeriksaan bagian pengajaran."
            : "{$pesan} Jadwal Anda masih ditolak; perbaiki jadwalnya di menu Upload Jadwal Pendadaran.");
    }

    /** Bukti bayar bisa diunggah/diganti selama belum disetujui dan jadwal belum disetujui */
    private function bisaUbah(?JadwalPendadaran $jadwal): bool
    {
        return $jadwal !== null && ! $jadwal->buktiDisetujui()
            && in_array($jadwal->status, [JadwalPendadaran::PENDING, JadwalPendadaran::DITOLAK], true);
    }
}
