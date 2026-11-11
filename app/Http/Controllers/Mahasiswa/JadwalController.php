<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Models\Dosen;
use App\Models\JadwalPendadaran;
use Closure;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class JadwalController extends Controller
{
    private const PESAN = [
        'judul.required'           => 'Judul wajib diisi.',
        'judul.max'                => 'Judul maksimal 255 karakter.',
        'pembimbing_1.required'    => 'Pembimbing utama wajib diisi.',
        'pembimbing_1.in'          => 'Pembimbing utama harus dipilih dari daftar dosen.',
        'pembimbing_2.required'    => 'Pembimbing pendamping wajib diisi.',
        'pembimbing_2.in'          => 'Pembimbing pendamping harus dipilih dari daftar dosen.',
        'pembimbing_2.different'   => 'Pembimbing pendamping tidak boleh sama dengan pembimbing utama.',
        'penguji.required'         => 'Penguji wajib diisi.',
        'penguji.in'               => 'Penguji harus dipilih dari daftar dosen.',
        'penguji.different'        => 'Penguji tidak boleh sama dengan pembimbing.',
        'tanggal_ujian.required'   => 'Tanggal ujian wajib diisi.',
        'tanggal_ujian.date_format'=> 'Format tanggal tidak valid.',
        'tanggal_ujian.after'      => 'Tanggal ujian paling cepat besok.',
        'jam_ujian.required'       => 'Jam ujian wajib diisi.',
        'jam_ujian.date_format'    => 'Format jam tidak valid (gunakan JJ:MM).',
    ];

    public function index(Request $request)
    {
        $user = $request->user();
        $jadwal = JadwalPendadaran::where('user_id', $user->id)->latest('id')->first();
        $dosen = Dosen::orderBy('nama')->get();

        return view('mahasiswa.jadwal.index', [
            'jadwal'       => $jadwal?->load('riwayatPenolakan'),
            'riwayat'      => JadwalPendadaran::where('user_id', $user->id)->latest('id')->get(),
            'dosen'        => $dosen,
            'belumLengkap' => $user->syaratBelumDisetujui(),
        ]);
    }

    public function store(Request $request)
    {
        $request->merge([
            'judul'        => trim((string) $request->input('judul')),
            'pembimbing_1' => trim((string) $request->input('pembimbing_1')),
            'pembimbing_2' => trim((string) $request->input('pembimbing_2')),
            'penguji'      => trim((string) $request->input('penguji')),
        ]);

        $namaDosen = Dosen::pluck('nama')->all();
        $user = $request->user();

        // Ambil jadwal terakhir
        $jadwalExist = JadwalPendadaran::where('user_id', $user->id)->latest('id')->first();
        // Pengajuan yang masih menunggu atau ditolak diperbaiki di baris yang sama (tidak membuat data baru)
        $perbaruiPending = in_array($jadwalExist?->status, [JadwalPendadaran::PENDING, JadwalPendadaran::DITOLAK], true);
        $dariDitolak = $jadwalExist?->status === JadwalPendadaran::DITOLAK;

        $data = $request->validate([
            'judul'         => ['required', 'string', 'max:255'],
            'pembimbing_1'  => ['required', 'string', 'max:120', Rule::in($namaDosen)],
            'pembimbing_2'  => ['required', 'string', 'max:120', Rule::in($namaDosen), 'different:pembimbing_1'],
            'penguji'       => ['required', 'string', 'max:120', Rule::in($namaDosen), 'different:pembimbing_1', 'different:pembimbing_2'],
            'tanggal_ujian' => ['bail', 'required', 'date_format:Y-m-d', 'after:today', $this->bukanAkhirPekan()],
            'jam_ujian'     => ['bail', 'required', 'date_format:H:i', $this->dalamJamKerja()],
        ], self::PESAN);

        // Jadwal yang sudah disetujui tidak bisa diubah, kecuali hasil ujiannya Tidak Lulus (ujian ulang)
        if ($jadwalExist && $jadwalExist->status === JadwalPendadaran::DISETUJUI
            && $jadwalExist->hasil !== JadwalPendadaran::TIDAK_LULUS) {
            return back()->with('error', $jadwalExist->lulus()
                ? 'Anda sudah lulus ujian pendadaran.'
                : 'Jadwal Anda sudah disetujui, tidak dapat mengubah jadwal.');
        }

        // Jadwal hanya boleh diajukan setelah semua dokumen persyaratan disetujui
        $belumLengkap = $user->syaratBelumDisetujui();
        if ($belumLengkap->isNotEmpty()) {
            return back()->withInput()->with('error',
                'Jadwal belum bisa diajukan. Dokumen berikut belum disetujui: ' . $belumLengkap->pluck('nama')->join(', ') . '.');
        }

        // Dosen yang sama tidak boleh menguji di dua tempat dengan selisih kurang dari 2 jam
        $kecualiId = $perbaruiPending ? $jadwalExist->id : null;
        $bentrok = JadwalPendadaran::cariBentrok(
            [$data['pembimbing_1'], $data['pembimbing_2'] ?? null, $data['penguji']],
            $data['tanggal_ujian'],
            $data['jam_ujian'],
            [JadwalPendadaran::PENDING, JadwalPendadaran::DISETUJUI],
            $kecualiId,
        );

        if ($bentrok) {
            [$lain, $dosen] = $bentrok;

            return back()->withInput()->withErrors([
                'jam_ujian' => "{$dosen} sudah memiliki jadwal ujian pada {$lain->tanggalIndo()} pukul {$lain->jamWib()}. "
                    . 'Pilih jam dengan selisih minimal ' . (JadwalPendadaran::DURASI_MENIT / 60) . ' jam atau tanggal lain.',
            ]);
        }

        $isian = [
            'judul'         => $data['judul'],
            'pembimbing_1'  => $data['pembimbing_1'],
            'pembimbing_2'  => $data['pembimbing_2'],
            'penguji'       => $data['penguji'],
            'tanggal_ujian' => $data['tanggal_ujian'],
            'jam_ujian'     => $data['jam_ujian'],
        ];

        // Perbaiki pengajuan yang sama. Penolakan sebelumnya sudah tersimpan di riwayat_penolakan_jadwal.
        if ($perbaruiPending) {
            $jadwalExist->update($isian + ($dariDitolak ? [
                'status'            => JadwalPendadaran::PENDING,
                'alasan_tolak'      => null,
                'diverifikasi_oleh' => null,
                'diverifikasi_at'   => null,
            ] : []));

            return back()->with('success', $dariDitolak
                ? 'Perbaikan jadwal dikirim dan kembali menunggu verifikasi.' . ($jadwalExist->bukti_bayar_path ? '' : ' Jangan lupa unggah bukti pembayaran.')
                : 'Jadwal berhasil diperbarui dan menunggu persetujuan.');
        }

        // Pengajuan pertama, atau ujian ulang setelah tidak lulus (butuh bukti bayar baru): buat pengajuan baru
        $baru = JadwalPendadaran::create($isian + [
            'user_id' => $user->id,
            'nim'     => $user->username,
            'nama'    => $user->name,
            'status'  => JadwalPendadaran::PENDING,
        ]);

        return back()->with('success', $baru->bukti_bayar_path
            ? 'Jadwal berhasil diajukan dan menunggu verifikasi.'
            : 'Jadwal berhasil diajukan. Unggah bukti pembayaran di menu Upload Bukti Pembayaran agar jadwal bisa disetujui.');
    }

    /** Mahasiswa membuka bukti bayarnya sendiri */
    public function buktiBayar(Request $request, JadwalPendadaran $jadwal): Response
    {
        abort_unless($jadwal->user_id === $request->user()->id && $jadwal->bukti_bayar_path, 404);

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('local');
        abort_unless($disk->exists($jadwal->bukti_bayar_path), 404, 'Berkas tidak ditemukan.');

        return $disk->response($jadwal->bukti_bayar_path, $jadwal->bukti_bayar_nama,
            ['Content-Type' => $jadwal->bukti_bayar_mime ?: 'application/octet-stream', 'X-Content-Type-Options' => 'nosniff'], 'inline');
    }

    private function bukanAkhirPekan(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) {
            $tanggal = Carbon::createFromFormat('Y-m-d', (string) $value);

            if ($tanggal && $tanggal->isWeekend()) {
                $fail('Ujian tidak dapat dijadwalkan pada hari Sabtu atau Minggu.');
            }
        };
    }

    private function dalamJamKerja(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) {
            $menit = JadwalPendadaran::keMenit((string) $value);

            if ($menit < JadwalPendadaran::keMenit(JadwalPendadaran::JAM_MULAI_PALING_AWAL)
                || $menit > JadwalPendadaran::keMenit(JadwalPendadaran::JAM_MULAI_PALING_AKHIR)) {
                $fail('Jam mulai ujian harus antara ' . JadwalPendadaran::JAM_MULAI_PALING_AWAL
                    . ' dan ' . JadwalPendadaran::JAM_MULAI_PALING_AKHIR . ' WIB.');
            }
        };
    }
}
