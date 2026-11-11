<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JadwalPendadaran;
use App\Models\RiwayatBuktiBayar;
use App\Models\RiwayatPenolakanJadwal;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class JadwalController extends Controller
{
    private const PER_HALAMAN = 10;

    /** Undangan PDF maksimal 5 MB */
    private const UNDANGAN_MAKS_KB = 5120;

    /** Tab di atas tabel => kondisi */
    public const TAB = [
        'semua'          => 'Semua',
        'pending'        => 'Perlu verifikasi',
        'perlu_undangan' => 'Perlu undangan',
        'perlu_nilai'    => 'Perlu nilai',
        'selesai'        => 'Sudah ada hasil',
        'ditolak'        => 'Ditolak',
    ];

    public function index(Request $request): View
    {
        [$sort, $dir, $q, $tab] = $this->parameter($request);

        $jadwal = $this->kueri($q, $sort, $dir, $tab)
            ->with('user')
            ->paginate(self::PER_HALAMAN)
            ->withQueryString();

        $hitung = collect(array_keys(self::TAB))
            ->mapWithKeys(fn ($t) => [$t => $this->kueri('', 'id', 'asc', $t)->count()]);

        return view('admin.jadwal', compact('jadwal', 'sort', 'dir', 'q', 'tab', 'hitung'));
    }

    public function show(JadwalPendadaran $jadwal): View
    {
        $jadwal->load(['user', 'verifikator', 'penginputHasil', 'riwayatBuktiBayar.penolak', 'riwayatPenolakan.penolak']);

        return view('admin.jadwal-show', [
            'jadwal'       => $jadwal,
            'belumLengkap' => $jadwal->user?->syaratBelumDisetujui() ?? collect(),
            'riwayat'      => $jadwal->user_id
                ? JadwalPendadaran::where('user_id', $jadwal->user_id)->whereKeyNot($jadwal->id)->latest('id')->get()
                : collect(),
        ]);
    }

    /* =========================================================
       Verifikasi jadwal + bukti bayar
       ========================================================= */

    public function setuju(Request $request, JadwalPendadaran $jadwal): RedirectResponse
    {
        if ($jadwal->status !== JadwalPendadaran::PENDING) {
            return back()->with('error', 'Jadwal ini sudah diverifikasi sebelumnya.');
        }

        if ($alasan = $this->alasanTidakBisaDisetujui($jadwal)) {
            return back()->with('error', $alasan);
        }

        $jadwal->update([
            'status'            => JadwalPendadaran::DISETUJUI,
            'alasan_tolak'      => null,
            'diverifikasi_oleh' => $request->user()->id,
            'diverifikasi_at'   => now(),
        ]);

        return back()->with('status', "Jadwal {$jadwal->nama} ({$jadwal->nim}) disetujui. Unggah undangan ujiannya agar bisa diunduh mahasiswa.");
    }

    public function tolak(Request $request, JadwalPendadaran $jadwal): RedirectResponse
    {
        if ($jadwal->status !== JadwalPendadaran::PENDING) {
            return back()->with('error', 'Jadwal ini sudah diverifikasi sebelumnya.');
        }

        $data = $request->validateWithBag('tolak', [
            'alasan_tolak' => ['required', 'string', 'max:500'],
        ], [
            'alasan_tolak.required' => 'Tuliskan alasan penolakan agar mahasiswa tahu apa yang harus diperbaiki.',
            'alasan_tolak.max'      => 'Alasan maksimal 500 karakter.',
        ]);

        RiwayatPenolakanJadwal::create([
            'jadwal_id'     => $jadwal->id,
            'judul'         => $jadwal->judul,
            'pembimbing_1'  => $jadwal->pembimbing_1,
            'pembimbing_2'  => $jadwal->pembimbing_2,
            'penguji'       => $jadwal->penguji,
            'tanggal_ujian' => $jadwal->tanggal_ujian->toDateString(),
            'jam_ujian'     => $jadwal->jam_ujian,
            'alasan'        => trim($data['alasan_tolak']),
            'ditolak_oleh'  => $request->user()->id,
            'ditolak_at'    => now(),
        ]);

        $jadwal->update([
            'status'            => JadwalPendadaran::DITOLAK,
            'alasan_tolak'      => trim($data['alasan_tolak']),
            'diverifikasi_oleh' => $request->user()->id,
            'diverifikasi_at'   => now(),
        ]);

        return back()->with('status', "Jadwal {$jadwal->nama} ({$jadwal->nim}) ditolak. Mahasiswa memperbaiki pengajuan yang sama; riwayat penolakan tersimpan.");
    }

    /**
     * Tolak bukti bayar saja. Jadwal tetap menunggu; mahasiswa cukup mengunggah ulang
     * bukti bayar di menu Upload Bukti Pembayaran tanpa mengajukan jadwal lagi.
     */
    /** Langkah 1: bukti bayar sah. Setelah ini tombol "Setujui jadwal" baru muncul. */
    public function setujuBukti(Request $request, JadwalPendadaran $jadwal): RedirectResponse
    {
        if ($pesan = $this->buktiTidakBisaDiperiksa($jadwal)) {
            return back()->with('error', $pesan);
        }

        $jadwal->update(['bukti_bayar_status' => JadwalPendadaran::BAYAR_DISETUJUI, 'bukti_bayar_alasan' => null]);

        return redirect()->to(route('admin.jadwal.show', $jadwal) . '#verifikasi')
            ->with('status', "Bukti bayar {$jadwal->nama} disetujui. Lanjutkan dengan verifikasi jadwal.");
    }

    public function tolakBukti(Request $request, JadwalPendadaran $jadwal): RedirectResponse
    {
        if ($pesan = $this->buktiTidakBisaDiperiksa($jadwal)) {
            return back()->with('error', $pesan);
        }

        $data = $request->validateWithBag('tolakBukti', [
            'alasan_bukti' => ['required', 'string', 'max:500'],
        ], [
            'alasan_bukti.required' => 'Tuliskan alasan penolakan bukti bayar.',
            'alasan_bukti.max'      => 'Alasan maksimal 500 karakter.',
        ]);
        $alasan = trim($data['alasan_bukti']);

        RiwayatBuktiBayar::create([
            'jadwal_id'    => $jadwal->id,
            'path'         => $jadwal->bukti_bayar_path,
            'nama'         => $jadwal->bukti_bayar_nama ?? basename($jadwal->bukti_bayar_path),
            'mime'         => $jadwal->bukti_bayar_mime,
            'alasan'       => $alasan,
            'ditolak_oleh' => $request->user()->id,
            'ditolak_at'   => now(),
        ]);

        $jadwal->update(['bukti_bayar_status' => JadwalPendadaran::BAYAR_DITOLAK, 'bukti_bayar_alasan' => $alasan]);

        return back()->with('status', "Bukti bayar {$jadwal->nama} ditolak. Jadwal tetap menunggu sampai mahasiswa mengunggah bukti yang baru.");
    }

    /** Buka berkas bukti bayar lama yang pernah ditolak */
    public function riwayatBukti(RiwayatBuktiBayar $riwayat): Response
    {
        return $this->berkas($riwayat->path, $riwayat->nama, $riwayat->mime);
    }

    public function buktiBayar(JadwalPendadaran $jadwal): Response
    {
        return $this->berkas($jadwal->bukti_bayar_path, $jadwal->bukti_bayar_nama, $jadwal->bukti_bayar_mime);
    }

    /* =========================================================
       Undangan jadwal ujian (PDF dari pengajaran)
       ========================================================= */

    public function unggahUndangan(Request $request, JadwalPendadaran $jadwal): RedirectResponse
    {
        if ($jadwal->status !== JadwalPendadaran::DISETUJUI) {
            return back()->with('error', 'Undangan hanya bisa diunggah untuk jadwal yang sudah disetujui.');
        }

        $maksMb = self::UNDANGAN_MAKS_KB / 1024;
        $request->validateWithBag('undangan', [
            'undangan' => ['required', 'file', 'mimes:pdf', 'mimetypes:application/pdf', 'max:' . self::UNDANGAN_MAKS_KB],
        ], [
            'undangan.required'  => 'Pilih berkas PDF undangan.',
            'undangan.mimes'     => 'Undangan harus berformat PDF.',
            'undangan.mimetypes' => 'Undangan harus berformat PDF.',
            'undangan.max'       => "Ukuran undangan maksimal {$maksMb} MB.",
            'undangan.uploaded'  => "Undangan gagal diunggah. Pastikan ukurannya tidak lebih dari {$maksMb} MB.",
        ]);

        $file = $request->file('undangan');
        $path = $file->store("undangan/{$jadwal->id}", 'local');

        if (! $path) {
            return back()->with('error', 'Undangan gagal disimpan. Silakan coba lagi.');
        }

        $lama = $jadwal->undangan_path;
        $jadwal->update([
            'undangan_path' => $path,
            'undangan_nama' => Str::limit($file->getClientOriginalName(), 255, ''),
            'undangan_at'   => now(),
        ]);

        if ($lama && $lama !== $path) {
            Storage::disk('local')->delete($lama);
        }

        return back()->with('status', ($lama ? 'Undangan diganti.' : 'Undangan diunggah.') . ' Mahasiswa kini bisa mengunduhnya di menu Undangan Jadwal Ujian.');
    }

    public function lihatUndangan(JadwalPendadaran $jadwal): Response
    {
        return $this->berkas($jadwal->undangan_path, $jadwal->undangan_nama, 'application/pdf');
    }

    /* =========================================================
       Nilai dan hasil
       ========================================================= */

    public function simpanNilai(Request $request, JadwalPendadaran $jadwal): RedirectResponse
    {
        if ($jadwal->status !== JadwalPendadaran::DISETUJUI) {
            return back()->with('error', 'Nilai hanya bisa diinput untuk jadwal yang sudah disetujui.');
        }
        if (! $jadwal->sudahUjian()) {
            return back()->with('error', 'Nilai bisa diinput setelah ujian berlangsung (' . $jadwal->tanggalIndo() . ').');
        }
        if ($jadwal->hasilTerkunci()) {
            return back()->with('error', 'Nilai terkunci karena mahasiswa sudah terdaftar di periode yudisium. Lepas dulu pendaftarannya di menu Data Yudisium jika perlu diralat.');
        }

        $nilai = ['required', 'numeric', 'min:0', 'max:100', 'decimal:0,2'];
        $aturan = [
            'nilai_pembimbing_1' => $nilai,
            'nilai_pembimbing_2' => $jadwal->pembimbing_2 ? $nilai : ['prohibited'],
            'nilai_penguji'      => $nilai,
        ];
        $pesan = [];
        foreach (['nilai_pembimbing_1' => 'pembimbing utama', 'nilai_pembimbing_2' => 'pembimbing pendamping', 'nilai_penguji' => 'penguji'] as $k => $l) {
            $pesan["{$k}.required"] = "Nilai dari {$l} wajib diisi.";
            $pesan["{$k}.numeric"]  = "Nilai dari {$l} harus berupa angka.";
            $pesan["{$k}.min"]      = "Nilai dari {$l} minimal 0.";
            $pesan["{$k}.max"]      = "Nilai dari {$l} maksimal 100.";
            $pesan["{$k}.decimal"]  = "Nilai dari {$l} maksimal 2 angka di belakang koma.";
        }

        // Petugas sering mengetik koma (82,5); ubah ke titik sebelum divalidasi
        $request->merge(collect(array_keys($aturan))
            ->mapWithKeys(fn ($k) => [$k => $request->filled($k) ? str_replace(',', '.', trim((string) $request->input($k))) : null])
            ->all());

        $data = $request->validateWithBag('nilai', $aturan, $pesan);

        $p1 = (float) $data['nilai_pembimbing_1'];
        $p2 = $jadwal->pembimbing_2 ? (float) $data['nilai_pembimbing_2'] : null;
        $pg = (float) $data['nilai_penguji'];
        $akhir = JadwalPendadaran::hitungNilaiAkhir($p1, $p2, $pg);
        $hasil = $akhir >= JadwalPendadaran::BATAS_LULUS ? JadwalPendadaran::LULUS : JadwalPendadaran::TIDAK_LULUS;
        $ralat = $jadwal->hasil !== null;

        $jadwal->update([
            'nilai_pembimbing_1' => $p1,
            'nilai_pembimbing_2' => $p2,
            'nilai_penguji'      => $pg,
            'nilai_akhir'        => $akhir,
            'hasil'              => $hasil,
            'hasil_oleh'         => $request->user()->id,
            'hasil_at'           => now(),
        ]);

        $teks = 'Nilai akhir ' . JadwalPendadaran::formatNilai($akhir) . ' (' . JadwalPendadaran::huruf($akhir) . '): '
            . ($hasil === JadwalPendadaran::LULUS
                ? 'Lulus. Mahasiswa bisa mendaftar ke periode yudisium yang dibuka.'
                : 'Tidak Lulus. Mahasiswa bisa mengajukan jadwal ujian ulang.');

        return back()->with('status', ($ralat ? 'Nilai diralat. ' : 'Nilai disimpan. ') . $teks);
    }

    /* =========================================================
       Ekspor
       ========================================================= */

    /**
     * Ekspor ke file CSV yang langsung terbuka di Excel.
     * Mengikuti tab, pencarian, dan urutan yang sedang aktif.
     */
    public function ekspor(Request $request): StreamedResponse
    {
        [$sort, $dir, $q, $tab] = $this->parameter($request);
        $namaFile = 'jadwal-pendadaran-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($q, $sort, $dir, $tab) {
            $out = fopen('php://output', 'w');

            fwrite($out, "\xEF\xBB\xBF");   // BOM supaya huruf Indonesia tampil benar di Excel
            fwrite($out, "sep=;\r\n");      // Excel memakai titik koma sebagai pemisah kolom

            fputcsv($out, [
                'No', 'NIM', 'Nama Mahasiswa', 'Judul Skripsi', 'Pembimbing Utama', 'Pembimbing Pendamping',
                'Penguji', 'Tanggal Ujian', 'Jam', 'Status', 'Nilai Pemb. Utama', 'Nilai Pemb. Pendamping', 'Nilai Penguji',
                'Nilai Akhir', 'Huruf', 'Hasil',
            ], ';', '"', '\\');

            $no = 0;
            $this->kueri($q, $sort, $dir, $tab)->lazy(200)->each(function (JadwalPendadaran $j) use ($out, &$no) {
                fputcsv($out, array_map([$this, 'aman'], [
                    ++$no, $j->nim, $j->nama, $j->judul, $j->pembimbing_1, (string) $j->pembimbing_2,
                    $j->penguji, $j->tanggalIndo(), $j->jamWib(), ucfirst($j->status),
                    JadwalPendadaran::formatNilai($j->nilai_pembimbing_1), JadwalPendadaran::formatNilai($j->nilai_pembimbing_2),
                    JadwalPendadaran::formatNilai($j->nilai_penguji), JadwalPendadaran::formatNilai($j->nilai_akhir),
                    $j->hurufAkhir(), $j->hasil ? $j->labelHasil() : '-',
                ]), ';', '"', '\\');
            });

            fclose($out);
        }, $namaFile, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /* =========================================================
       Pembantu
       ========================================================= */

    /** @return array{0: string, 1: string, 2: string, 3: string} [kolom urut, arah, kata cari, tab] */
    private function parameter(Request $request): array
    {
        $sort = in_array($request->query('sort'), JadwalPendadaran::KOLOM_URUT, true)
            ? (string) $request->query('sort')
            : 'id';
        $dir = $request->query('dir') === 'asc' ? 'asc' : 'desc';
        $q   = trim((string) $request->query('q'));
        $tab = array_key_exists((string) $request->query('tab'), self::TAB) ? (string) $request->query('tab') : 'semua';

        return [$sort, $dir, $q, $tab];
    }

    private function kueri(string $q, string $sort, string $dir, string $tab = 'semua'): Builder
    {
        $query = JadwalPendadaran::query()->cari($q);

        match ($tab) {
            'pending'        => $query->where('status', JadwalPendadaran::PENDING),
            'ditolak'        => $query->where('status', JadwalPendadaran::DITOLAK),
            'perlu_undangan' => $query->where('status', JadwalPendadaran::DISETUJUI)->whereNull('undangan_path'),
            'perlu_nilai'    => $query->where('status', JadwalPendadaran::DISETUJUI)->whereNull('hasil')
                ->whereDate('tanggal_ujian', '<=', today()),
            'selesai'        => $query->whereNotNull('hasil'),
            default          => null,
        };

        $query->orderBy($sort, $dir);

        if ($sort !== 'id') {
            $query->orderBy('id', 'desc');
        }

        return $query;
    }

    /**
     * Dicek ulang saat persetujuan karena dokumen bisa berubah status dan
     * dua pengajuan pending bisa saja memakai dosen yang sama di jam yang sama.
     */
    /** Bukti bayar hanya bisa disetujui/ditolak saat sudah diunggah, belum diperiksa, dan jadwal belum disetujui */
    private function buktiTidakBisaDiperiksa(JadwalPendadaran $jadwal): ?string
    {
        if ($jadwal->status === JadwalPendadaran::DISETUJUI || ! $jadwal->bukti_bayar_path) {
            return 'Tidak ada bukti bayar yang menunggu pemeriksaan.';
        }
        if ($jadwal->buktiDitolak()) {
            return 'Bukti bayar ini sudah ditolak. Tunggu mahasiswa mengunggah bukti yang baru.';
        }
        if ($jadwal->buktiDisetujui()) {
            return 'Bukti bayar ini sudah disetujui.';
        }

        return null;
    }

    private function alasanTidakBisaDisetujui(JadwalPendadaran $jadwal): ?string
    {
        // Jadwal yang diinput tanpa akun mahasiswa (mis. data lama) tidak punya dokumen untuk dicek
        if ($jadwal->user) {
            $belumLengkap = $jadwal->user->syaratBelumDisetujui();

            if ($belumLengkap->isNotEmpty()) {
                return "Jadwal {$jadwal->nama} belum bisa disetujui. Dokumen yang belum disetujui: "
                    . $belumLengkap->pluck('kode')->join(', ') . '.';
            }

            if (! $jadwal->bukti_bayar_path) {
                return "Jadwal {$jadwal->nama} belum bisa disetujui karena bukti pembayaran belum diunggah oleh mahasiswa.";
            }
            if ($jadwal->buktiDitolak()) {
                return "Jadwal {$jadwal->nama} belum bisa disetujui karena bukti pembayarannya ditolak. Tunggu mahasiswa mengunggah bukti yang baru.";
            }
            if (! $jadwal->buktiDisetujui()) {
                return "Jadwal {$jadwal->nama} belum bisa disetujui. Periksa dan setujui bukti pembayarannya terlebih dahulu.";
            }
        }

        $bentrok = JadwalPendadaran::cariBentrok(
            $jadwal->namaDosen(),
            $jadwal->tanggal_ujian->toDateString(),
            $jadwal->jam_ujian,
            [JadwalPendadaran::DISETUJUI],
            $jadwal->id,
        );

        if ($bentrok) {
            [$lain, $dosen] = $bentrok;

            return "Jadwal bentrok: {$dosen} sudah dijadwalkan menguji {$lain->nama} ({$lain->nim}) "
                . "pada {$lain->tanggalIndo()} pukul {$lain->jamWib()}.";
        }

        return null;
    }

    /** Tampilkan berkas dari disk privat di tab browser */
    private function berkas(?string $path, ?string $nama, ?string $mime): Response
    {
        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('local');

        abort_unless($path && $disk->exists($path), 404, 'Berkas tidak ditemukan.');

        return $disk->response($path, $nama ?: basename($path),
            ['Content-Type' => $mime ?: 'application/octet-stream', 'X-Content-Type-Options' => 'nosniff'], 'inline');
    }

    /** Cegah rumus Excel dari data yang diketik pengguna (CSV injection) */
    private function aman(mixed $nilai): mixed
    {
        if (is_string($nilai) && $nilai !== '' && in_array($nilai[0], ['=', '+', '-', '@'], true)) {
            return "'" . $nilai;
        }

        return $nilai;
    }
}
