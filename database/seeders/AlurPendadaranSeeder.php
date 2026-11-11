<?php

namespace Database\Seeders;

use App\Models\Periode;
use App\Models\Dosen;
use App\Models\JadwalPendadaran;
use App\Models\User;
use App\Support\PdfSederhana;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * Data awal alur pendadaran:
 * - Citra (21110003): lulus (82), terdaftar di periode yudisium yang dibuka
 * - Dani  (21110004): lulus (80,5), terdaftar di periode yudisium yang dibuka
 * - Eka   (22110005): jadwal menunggu verifikasi, bukti bayar sudah diunggah
 */
class AlurPendadaranSeeder extends Seeder
{
    public function run(): void
    {
        $dosen = Dosen::orderBy('id')->pluck('nama')->values();
        if ($dosen->count() < 3) {
            return;
        }
        $adminId = User::where('username', 'admin')->value('id');

        $citra = User::where('username', '21110003')->first();
        $dani = User::where('username', '21110004')->first();
        $eka = User::where('username', '22110005')->first();

        $periode = Periode::where('dibuka', true)->orderBy('id')->first();

        if ($citra && ! $citra->jadwal()->exists()) {
            $tgl = $this->hariKerjaLalu(6);
            $j = $this->buat($citra, 'Analisis Kuat Tekan Beton dengan Campuran Abu Sekam Padi', $dosen[0], $dosen[1], $dosen[2], $tgl, '09:00', JadwalPendadaran::DISETUJUI, $adminId);
            $this->undangan($j);
            $j->update([
                'nilai_pembimbing_1' => 84, 'nilai_pembimbing_2' => 80, 'nilai_penguji' => 82,
                'nilai_akhir' => 82, 'hasil' => JadwalPendadaran::LULUS, 'hasil_oleh' => $adminId, 'hasil_at' => $tgl->copy()->setTime(15, 0),
            ]);
            $citra->forceFill(['periode_id' => $periode?->id, 'periode_daftar_at' => $periode ? now() : null])->save();
        }

        if ($dani && ! $dani->jadwal()->exists()) {
            $tgl = $this->hariKerjaLalu(3);
            $j = $this->buat($dani, 'Sistem Informasi Pendaftaran Pendadaran Berbasis Web', $dosen[1], $dosen[2], $dosen[0], $tgl, '10:00', JadwalPendadaran::DISETUJUI, $adminId);
            $this->undangan($j);
            $j->update([
                'nilai_pembimbing_1' => 84, 'nilai_pembimbing_2' => 80, 'nilai_penguji' => 77.5,
                'nilai_akhir' => 80.5, 'hasil' => JadwalPendadaran::LULUS, 'hasil_oleh' => $adminId, 'hasil_at' => $tgl->copy()->setTime(15, 0),
            ]);
            $dani->forceFill(['periode_id' => $periode?->id, 'periode_daftar_at' => $periode ? now() : null])->save();
        }

        if ($eka && ! $eka->jadwal()->exists()) {
            $this->buat($eka, 'Evaluasi Kinerja Drainase Perkotaan Kawasan Umbulharjo', $dosen[2], $dosen[0], $dosen[1], $this->hariKerjaDepan(7), '13:00', JadwalPendadaran::PENDING, null);
        }
    }

    private function buat(User $m, string $judul, string $p1, ?string $p2, string $penguji, Carbon $tgl, string $jam, string $status, ?int $verifikator): JadwalPendadaran
    {
        $path = "bukti-bayar/{$m->id}/contoh-bukti-bayar.pdf";
        $pdf = (new PdfSederhana('Contoh bukti bayar'))
            ->teks('CONTOH BUKTI BAYAR (DATA UJI COBA)', 14, true, 'tengah')->jarak(10)
            ->pasangan(['Nama' => $m->name, 'NIM' => $m->username, 'Keperluan' => 'Biaya ujian pendadaran', 'Keterangan' => 'Berkas ini dibuat otomatis oleh seeder untuk uji coba.']);
        Storage::disk('local')->put($path, $pdf->keluaran());

        return JadwalPendadaran::create([
            'user_id' => $m->id, 'nim' => $m->username, 'nama' => $m->name, 'judul' => $judul,
            'pembimbing_1' => $p1, 'pembimbing_2' => $p2, 'penguji' => $penguji,
            'tanggal_ujian' => $tgl->toDateString(), 'jam_ujian' => $jam . ':00', 'status' => $status,
            'bukti_bayar_path' => $path, 'bukti_bayar_nama' => 'bukti-bayar-' . $m->username . '.pdf', 'bukti_bayar_mime' => 'application/pdf',
            'bukti_bayar_status' => $status === JadwalPendadaran::DISETUJUI ? JadwalPendadaran::BAYAR_DISETUJUI : JadwalPendadaran::BAYAR_MENUNGGU,
            'diverifikasi_oleh' => $verifikator, 'diverifikasi_at' => $verifikator ? now()->subDays(7) : null,
        ]);
    }

    private function undangan(JadwalPendadaran $j): void
    {
        $path = "undangan/{$j->id}/contoh-undangan.pdf";
        $pdf = (new PdfSederhana('Contoh undangan'))
            ->teks('CONTOH UNDANGAN JADWAL UJIAN PENDADARAN', 13, true, 'tengah')
            ->teks('(data uji coba, bukan surat resmi)', 9, false, 'tengah')->jarak(12)
            ->pasangan([
                'Nama' => $j->nama, 'Nomor Mahasiswa' => $j->nim,
                'Hari, tanggal' => $j->hariIndo() . ', ' . $j->tanggalIndo(), 'Pukul' => $j->jamWib(), 'Tempat' => 'Ruang Pendadaran',
                'Pembimbing 1' => $j->pembimbing_1, 'Pembimbing 2' => $j->pembimbing_2 ?: '-', 'Penguji' => $j->penguji,
            ]);
        Storage::disk('local')->put($path, $pdf->keluaran());
        $j->update(['undangan_path' => $path, 'undangan_nama' => 'undangan-' . $j->nim . '.pdf', 'undangan_at' => now()->subDays(5)]);
    }

    private function hariKerjaLalu(int $hari): Carbon
    {
        $t = today()->subDays($hari);
        while ($t->isWeekend()) {
            $t->subDay();
        }

        return $t;
    }

    private function hariKerjaDepan(int $hari): Carbon
    {
        $t = today()->addDays($hari);
        while ($t->isWeekend()) {
            $t->addDay();
        }

        return $t;
    }
}
