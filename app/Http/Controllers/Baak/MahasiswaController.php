<?php

namespace App\Http\Controllers\Baak;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\DokumenMahasiswa;
use App\Models\JadwalPendadaran as J;
use App\Models\Persyaratan;
use App\Models\User;
use App\Support\NomorWa;
use App\Support\PdfSederhana;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

/** BAAK: melihat kelengkapan data setiap mahasiswa (tanpa mengubah) dan mengunduhnya sebagai PDF. */
class MahasiswaController extends Controller
{
    private const PER_HALAMAN = 15;

    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q'));
        $prodi = in_array($request->query('prodi'), User::PROGRAM_STUDI, true) ? (string) $request->query('prodi') : '';
        $total = Persyaratan::count();

        $query = User::where('role', Role::Mahasiswa->value)
            ->with('periode')
            ->withCount(['dokumen as disetujui_count' => fn ($w) => $w->where('status', DokumenMahasiswa::DISETUJUI)])
            ->orderBy('name')->orderBy('id');

        if ($q !== '') {
            $like = '%' . addcslashes($q, '%_\\') . '%';
            $query->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('username', 'like', $like));
        }
        if ($prodi !== '') {
            $query->where('program_studi', $prodi);
        }

        $daftar = $query->paginate(self::PER_HALAMAN)->withQueryString();

        // Jadwal terakhir setiap mahasiswa di halaman ini (satu kueri)
        $jadwal = J::whereIn('user_id', $daftar->pluck('id'))->orderBy('id')->get()->keyBy('user_id');

        return view('baak.mahasiswa', compact('daftar', 'jadwal', 'total', 'q', 'prodi'));
    }

    public function show(User $mahasiswa): View
    {
        abort_unless($mahasiswa->role === Role::Mahasiswa, 404);

        return view('baak.mahasiswa-show', $this->data($mahasiswa));
    }

    public function pdf(User $mahasiswa): Response
    {
        abort_unless($mahasiswa->role === Role::Mahasiswa, 404);

        $d = $this->data($mahasiswa);
        $m = $d['mahasiswa'];
        $j = $d['jadwal'];
        $statusDok = ['menunggu' => 'Menunggu', 'disetujui' => 'Disetujui', 'ditolak' => 'Ditolak'];

        $pdf = new PdfSederhana("Data Kelengkapan {$m->name} ({$m->username})", "SIDARYUS · Dicetak " . now()->locale('id')->translatedFormat('d F Y H:i'));
        $pdf->teks('UNIVERSITAS JANABADRA', 14, true, 'tengah', [0.04, 0.15, 0.39])
            ->teks('FAKULTAS TEKNIK', 12, true, 'tengah', [0.04, 0.15, 0.39])
            ->jarak(4)->garis(1.2)
            ->teks('DATA KELENGKAPAN PENDADARAN DAN YUDISIUM', 12, true, 'tengah')
            ->jarak(4);

        $pdf->judulBagian('Data mahasiswa')->pasangan([
            'Nama'          => $m->name,
            'NIM'           => $m->username,
            'Program studi' => $m->program_studi ?? '-',
            'Angkatan'      => $m->angkatan ?? '-',
            'Kelas'         => $m->kelas ?? '-',
            'No. WhatsApp'  => NomorWa::tampil($m->no_wa),
        ]);

        $pdf->judulBagian("Dokumen persyaratan ({$d['disetujui']} dari {$d['total']} disetujui)");
        $baris = [];
        foreach ($d['persyaratan'] as $i => $p) {
            $dok = $d['dokumen']->get($p->id);
            $baris[] = [
                (string) ($i + 1),
                $p->kode . ' - ' . $p->nama,
                $dok ? $statusDok[$dok->status] ?? $dok->status : 'Belum diunggah',
                $dok?->diunggah_at?->format('d/m/Y H:i') ?? '-',
                $dok?->diverifikasi_at ? $dok->diverifikasi_at->format('d/m/Y') . ' (' . ($dok->verifikator?->name ?? '-') . ')' : '-',
                $dok?->catatan ?: '-',
            ];
        }
        $pdf->tabel(['No', 'Dokumen', 'Status', 'Diunggah', 'Diverifikasi', 'Catatan'], $baris, [6, 30, 12, 14, 19, 19]);
        $pdf->teks($d['lengkap'] ? 'Kesimpulan: dokumen persyaratan LENGKAP.' : 'Kesimpulan: dokumen persyaratan BELUM LENGKAP.', 10, true, 'kiri',
            $d['lengkap'] ? [0.09, 0.4, 0.2] : [0.6, 0.11, 0.11]);

        $pdf->judulBagian('Jadwal ujian pendadaran');
        if (! $j) {
            $pdf->teks('Belum mengajukan jadwal pendadaran.');
        } else {
            $pdf->pasangan([
                'Judul'        => $j->judul,
                'Pembimbing Utama' => $j->pembimbing_1,
                'Pembimbing Pendamping' => $j->pembimbing_2 ?: '-',
                'Penguji'      => $j->penguji,
                'Waktu ujian'  => $j->hariIndo() . ', ' . $j->tanggalIndo() . ' · ' . $j->jamWib(),
                'Status'       => ucfirst($j->status) . ($j->alasan_tolak ? ' (' . $j->alasan_tolak . ')' : ''),
                'Bukti bayar'  => $j->bukti_bayar_path ? ucfirst($j->bukti_bayar_status ?? 'menunggu') . ' (' . $j->bukti_bayar_nama . ')' . ($j->buktiDitolak() ? ' - ' . $j->bukti_bayar_alasan : '') : 'Belum diunggah',
                'Penolakan bukti' => $j->riwayatBuktiBayar()->count() . ' kali',
                'Undangan'     => $j->undangan_path ? 'Diterbitkan ' . $j->undangan_at?->format('d/m/Y') : 'Belum diterbitkan',
            ]);

            $pdf->judulBagian('Nilai dan hasil ujian');
            if (! $j->hasil) {
                $pdf->teks('Nilai belum diinput.');
            } else {
                $nilai = [['Pembimbing Utama', $j->pembimbing_1, J::formatNilai($j->nilai_pembimbing_1)]];
                if ($j->pembimbing_2) {
                    $nilai[] = ['Pembimbing Pendamping', $j->pembimbing_2, J::formatNilai($j->nilai_pembimbing_2)];
                }
                $nilai[] = ['Penguji', $j->penguji, J::formatNilai($j->nilai_penguji)];
                $nilai[] = ['Nilai akhir', 'Rata-rata', J::formatNilai($j->nilai_akhir) . ' (' . $j->hurufAkhir() . ')'];
                $pdf->tabel(['Peran', 'Dosen', 'Nilai'], $nilai, [20, 55, 25]);
                $pdf->pasangan(['Hasil' => $j->labelHasil()]);
            }
        }

        $pdf->judulBagian('Yudisium');
        $pdf->pasangan([
            'Periode'        => $m->periode?->nama ?? ($j?->lulus() ? 'Belum mendaftar' : '-'),
            'Tanggal daftar' => $m->periode_daftar_at?->locale('id')->translatedFormat('d F Y') ?? '-',
        ]);

        if ($d['riwayat']->count() > 1) {
            $pdf->judulBagian('Riwayat pengajuan jadwal');
            $pdf->tabel(['Tanggal ujian', 'Status jadwal', 'Hasil ujian'], $d['riwayat']->map(fn ($r) => [
                $r->tanggalIndo(), ucfirst($r->status), $r->hasil ? $r->labelHasil() . ' (' . J::formatNilai($r->nilai_akhir) . ')' : '-',
            ])->all(), [35, 30, 35]);
        }

        $nama = 'Data-' . $m->username . '-' . Str::slug($m->name) . '.pdf';

        return response($pdf->keluaran(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $nama . '"',
        ]);
    }

    /** Data lengkap satu mahasiswa untuk halaman detail dan PDF */
    private function data(User $mahasiswa): array
    {
        $mahasiswa->load('periode');
        $persyaratan = Persyaratan::orderBy('urutan')->orderBy('id')->get();
        $dokumen = $mahasiswa->dokumen()->with('verifikator')->get()->keyBy('persyaratan_id');
        $disetujui = $dokumen->where('status', DokumenMahasiswa::DISETUJUI)->count();
        $riwayat = $mahasiswa->jadwal()->latest('id')->get();

        return [
            'mahasiswa'   => $mahasiswa,
            'persyaratan' => $persyaratan,
            'dokumen'     => $dokumen,
            'disetujui'   => $disetujui,
            'total'       => $persyaratan->count(),
            'lengkap'     => $persyaratan->count() > 0 && $disetujui >= $persyaratan->count(),
            'jadwal'      => $riwayat->first(),
            'riwayat'     => $riwayat,
        ];
    }
}
