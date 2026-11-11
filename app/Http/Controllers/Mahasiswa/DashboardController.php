<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Models\DokumenMahasiswa;
use App\Models\JadwalPendadaran;
use App\Models\KontenDashboard;
use App\Models\Periode;
use App\Models\Persyaratan;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $total   = Persyaratan::count();
        $dokumen = $user->dokumen()->get();

        $disetujui = $dokumen->where('status', DokumenMahasiswa::DISETUJUI)->count();
        $menunggu  = $dokumen->where('status', DokumenMahasiswa::MENUNGGU)->count();
        $ditolak   = $dokumen->where('status', DokumenMahasiswa::DITOLAK)->count();

        $jadwal = JadwalPendadaran::where('user_id', $user->id)->latest('id')->first();

        [$labelJadwal, $warnaJadwal] = $jadwal?->hasil ? [$jadwal->labelHasil(), $jadwal->lulus() ? 'green' : 'red'] : match ($jadwal?->status) {
            JadwalPendadaran::PENDING   => ['Menunggu persetujuan', 'amber'],
            JadwalPendadaran::DISETUJUI => ['Disetujui', 'green'],
            JadwalPendadaran::DITOLAK   => ['Ditolak', 'red'],
            default                     => ['Belum diajukan', 'sky'],
        };

        $konten = KontenDashboard::semua();
        // Syarat dokumen diambil dari menu Kelola Persyaratan milik admin
        $konten['syarat'] = Persyaratan::orderBy('urutan')->orderBy('id')->pluck('nama')->all();

        return view('mahasiswa.dashboard', [
            'mahasiswa' => $user->load('periode'),
            'langkah'   => $this->langkah($user, $jadwal, $total, $disetujui),
            'lulus'     => $user->jadwalLulus(),
            'periodeBuka' => Periode::dibuka()->orderBy('id')->get(),
            'konten'    => $konten,
            'ringkasan' => [
                'total'     => $total,
                'disetujui' => $disetujui,
                'menunggu'  => $menunggu,
                'ditolak'   => $ditolak,
                'jadwal'    => [
                    'label' => $labelJadwal,
                    'warna' => $warnaJadwal,
                    'info'  => $jadwal ? $jadwal->tanggalIndo() . ' · ' . $jadwal->jamWib() : 'Ajukan lewat menu Upload Jadwal',
                ],
            ],
        ]);
    }

    /**
     * Tahapan pendadaran sampai yudisium untuk ditampilkan di dashboard.
     *
     * @return array<int, array{judul: string, ket: string, status: string}> status: selesai | sekarang | gagal | nanti
     */
    private function langkah($user, ?JadwalPendadaran $jadwal, int $total, int $disetujui): array
    {
        $l = [];
        $dokumenLengkap = $total === 0 || $disetujui >= $total;
        $l[] = ['judul' => 'Dokumen', 'ket' => "{$disetujui} dari {$total} disetujui", 'status' => $dokumenLengkap ? 'selesai' : 'sekarang'];

        if (! $jadwal) {
            $l[] = ['judul' => 'Jadwal & bukti bayar', 'ket' => 'Belum diajukan', 'status' => $dokumenLengkap ? 'sekarang' : 'nanti'];
        } else {
            $l[] = match ($jadwal->status) {
                JadwalPendadaran::PENDING => ['judul' => 'Jadwal & bukti bayar', 'ket' => ! $jadwal->bukti_bayar_path ? 'Unggah bukti pembayaran' : ($jadwal->buktiDitolak() ? 'Bukti bayar ditolak, unggah ulang' : ($jadwal->buktiDisetujui() ? 'Bukti sah, jadwal sedang diverifikasi' : 'Bukti bayar sedang diperiksa')), 'status' => $jadwal->buktiDitolak() ? 'gagal' : 'sekarang'],
                JadwalPendadaran::DITOLAK => ['judul' => 'Jadwal & bukti bayar', 'ket' => 'Ditolak, ajukan ulang', 'status' => 'gagal'],
                default                   => ['judul' => 'Jadwal & bukti bayar', 'ket' => 'Disetujui', 'status' => 'selesai'],
            };
        }

        $ok = $jadwal && $jadwal->status === JadwalPendadaran::DISETUJUI;
        if (! $ok) {
            $l[] = ['judul' => 'Undangan & ujian', 'ket' => 'Setelah jadwal disetujui', 'status' => 'nanti'];
        } elseif ($jadwal->sudahUjian()) {
            $l[] = ['judul' => 'Undangan & ujian', 'ket' => 'Ujian ' . $jadwal->tanggalIndo(), 'status' => 'selesai'];
        } else {
            $l[] = ['judul' => 'Undangan & ujian', 'ket' => ($jadwal->undangan_path ? 'Undangan tersedia · ' : 'Undangan disiapkan · ') . $jadwal->tanggalIndo(), 'status' => 'sekarang'];
        }

        if (! $ok || ! $jadwal->sudahUjian()) {
            $l[] = ['judul' => 'Hasil ujian', 'ket' => 'Diumumkan setelah ujian', 'status' => 'nanti'];
        } elseif (! $jadwal->hasil) {
            $l[] = ['judul' => 'Hasil ujian', 'ket' => 'Menunggu input nilai', 'status' => 'sekarang'];
        } else {
            $nilai = JadwalPendadaran::formatNilai($jadwal->nilai_akhir) . ' (' . $jadwal->hurufAkhir() . ')';
            $l[] = $jadwal->lulus()
                ? ['judul' => 'Hasil ujian', 'ket' => 'Lulus · ' . $nilai, 'status' => 'selesai']
                : ['judul' => 'Hasil ujian', 'ket' => 'Tidak lulus · ' . $nilai, 'status' => 'gagal'];
        }

        if ($user->periode) {
            $l[] = ['judul' => 'Yudisium', 'ket' => $user->periode->nama, 'status' => 'selesai'];
        } elseif ($jadwal?->lulus()) {
            $l[] = ['judul' => 'Yudisium', 'ket' => 'Daftar ke periode yang dibuka', 'status' => 'sekarang'];
        } elseif ($jadwal?->hasil === JadwalPendadaran::TIDAK_LULUS) {
            $l[] = ['judul' => 'Yudisium', 'ket' => 'Ajukan ujian ulang dulu', 'status' => 'nanti'];
        } else {
            $l[] = ['judul' => 'Yudisium', 'ket' => 'Setelah lulus ujian', 'status' => 'nanti'];
        }

        return $l;
    }
}
