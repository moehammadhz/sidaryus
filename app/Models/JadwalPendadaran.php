<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int                         $id
 * @property int|null                    $user_id
 * @property string                      $nim
 * @property string                      $nama
 * @property string                      $judul
 * @property string                      $pembimbing_1
 * @property string|null                 $pembimbing_2
 * @property string                      $penguji
 * @property \Illuminate\Support\Carbon  $tanggal_ujian
 * @property string                      $jam_ujian
 * @property string                      $status
 * @property string|null                 $bukti_bayar_path
 * @property string|null                 $alasan_tolak
 * @property string|null                 $undangan_path
 * @property string|null                 $nilai_pembimbing_1
 * @property string|null                 $nilai_pembimbing_2
 * @property string|null                 $nilai_penguji
 * @property string|null                 $nilai_akhir
 * @property string|null                 $hasil
 */
class JadwalPendadaran extends Model
{
    protected $table = 'jadwal_pendadaran';

    public const PENDING   = 'pending';
    public const DISETUJUI = 'disetujui';
    public const DITOLAK   = 'ditolak';

    public const LULUS       = 'lulus';
    public const TIDAK_LULUS = 'tidak_lulus';

    /** Nilai akhir minimal untuk dinyatakan lulus */
    public const BATAS_LULUS = 60;

    /** Konversi nilai angka ke huruf: [batas bawah, huruf] dari yang tertinggi */
    public const KONVERSI_HURUF = [[80, 'A'], [70, 'B'], [60, 'C'], [50, 'D'], [0, 'E']];

    /** Status pemeriksaan bukti bayar */
    public const BAYAR_MENUNGGU  = 'menunggu';
    public const BAYAR_DITOLAK   = 'ditolak';
    public const BAYAR_DISETUJUI = 'disetujui';

    /** Bukti bayar: foto atau PDF, maksimal 5 MB */
    public const BUKTI_BAYAR_MIMES = ['pdf', 'jpg', 'jpeg', 'png'];
    public const BUKTI_BAYAR_MAKS_KB = 5120;

    /** Lama satu sesi ujian. Dua ujian dengan dosen yang sama dianggap bentrok jika jaraknya kurang dari ini. */
    public const DURASI_MENIT = 120;

    /** Rentang jam mulai ujian yang diizinkan (format H:i, WIB) */
    public const JAM_MULAI_PALING_AWAL  = '07:00';
    public const JAM_MULAI_PALING_AKHIR = '16:00';

    /** Kolom yang boleh dipakai untuk mengurutkan tabel */
    public const KOLOM_URUT = [
        'id', 'nim', 'nama', 'judul', 'pembimbing_1', 'pembimbing_2',
        'penguji', 'tanggal_ujian', 'jam_ujian', 'status',
    ];

    protected $fillable = [
        'user_id', 'nim', 'nama', 'judul', 'pembimbing_1', 'pembimbing_2',
        'penguji', 'tanggal_ujian', 'jam_ujian', 'status',
        'bukti_bayar_path', 'bukti_bayar_nama', 'bukti_bayar_mime', 'bukti_bayar_status', 'bukti_bayar_alasan',
        'alasan_tolak', 'diverifikasi_oleh', 'diverifikasi_at',
        'undangan_path', 'undangan_nama', 'undangan_at',
        'nilai_pembimbing_1', 'nilai_pembimbing_2', 'nilai_penguji', 'nilai_akhir', 'hasil', 'hasil_oleh', 'hasil_at',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_ujian'   => 'date',
            'diverifikasi_at' => 'datetime',
            'undangan_at'     => 'datetime',
            'hasil_at'        => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Bukti bayar yang pernah ditolak untuk jadwal ini, terbaru dulu */
    public function riwayatBuktiBayar(): HasMany
    {
        return $this->hasMany(RiwayatBuktiBayar::class, 'jadwal_id')->latest('ditolak_at')->latest('id');
    }

    /** Penolakan jadwal sebelumnya untuk pengajuan ini, terbaru dulu */
    public function riwayatPenolakan(): HasMany
    {
        return $this->hasMany(RiwayatPenolakanJadwal::class, 'jadwal_id')->latest('ditolak_at')->latest('id');
    }

    public function buktiDitolak(): bool
    {
        return $this->bukti_bayar_path !== null && $this->bukti_bayar_status === self::BAYAR_DITOLAK;
    }

    public function buktiDisetujui(): bool
    {
        return $this->bukti_bayar_path !== null && $this->bukti_bayar_status === self::BAYAR_DISETUJUI;
    }

    /** Bukti sudah diunggah dan belum diperiksa */
    public function buktiMenunggu(): bool
    {
        return $this->bukti_bayar_path !== null && ! $this->buktiDitolak() && ! $this->buktiDisetujui();
    }

    public function verifikator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diverifikasi_oleh');
    }

    public function penginputHasil(): BelongsTo
    {
        return $this->belongsTo(User::class, 'hasil_oleh');
    }

    /* ---------- tahapan ---------- */

    /** Ujian sudah berlangsung (hari ini atau sebelumnya) */
    public function sudahUjian(): bool
    {
        return $this->tanggal_ujian->lte(today());
    }

    public function lulus(): bool
    {
        return $this->hasil === self::LULUS;
    }

    /**
     * Langkah berikutnya untuk jadwal ini, dipakai untuk label di daftar jadwal:
     * menunggu_bayar | cek_bayar | bayar_ditolak | pending | ditolak | perlu_undangan | menunggu_ujian | perlu_nilai | lulus | tidak_lulus
     * (cek_bayar = bukti bayar perlu diperiksa; pending = bukti sah, jadwal perlu diverifikasi)
     */
    public function tahap(): string
    {
        if ($this->status === self::PENDING && $this->user_id && ! $this->bukti_bayar_path) {
            return 'menunggu_bayar';
        }
        if ($this->status === self::PENDING && $this->buktiDitolak()) {
            return 'bayar_ditolak';
        }
        if ($this->status === self::PENDING && $this->user_id && $this->buktiMenunggu()) {
            return 'cek_bayar';
        }
        if ($this->status !== self::DISETUJUI) {
            return $this->status;
        }
        if ($this->hasil) {
            return $this->hasil;
        }
        if (! $this->undangan_path) {
            return 'perlu_undangan';
        }

        return $this->sudahUjian() ? 'perlu_nilai' : 'menunggu_ujian';
    }

    /** Hasil lulus terkunci setelah mahasiswa mendaftar ke periode yudisium */
    public function hasilTerkunci(): bool
    {
        return $this->lulus() && $this->user?->periode_id !== null;
    }

    /* ---------- nilai ---------- */

    /** Rata-rata nilai dosen yang terlibat (pembimbing 2 hanya dihitung jika ada) */
    public static function hitungNilaiAkhir(float $p1, ?float $p2, float $penguji): float
    {
        $nilai = $p2 === null ? [$p1, $penguji] : [$p1, $p2, $penguji];

        return round(array_sum($nilai) / count($nilai), 2);
    }

    public static function huruf(float|string|null $nilai): string
    {
        if ($nilai === null) {
            return '-';
        }
        foreach (self::KONVERSI_HURUF as [$batas, $huruf]) {
            if ((float) $nilai >= $batas) {
                return $huruf;
            }
        }

        return 'E';
    }

    public function hurufAkhir(): string
    {
        return self::huruf($this->nilai_akhir);
    }

    public function labelHasil(): string
    {
        return match ($this->hasil) {
            self::LULUS       => 'Lulus',
            self::TIDAK_LULUS => 'Tidak Lulus',
            default           => 'Belum ada hasil',
        };
    }

    /** "83,67" */
    public static function formatNilai(float|string|null $nilai): string
    {
        if ($nilai === null) {
            return '-';
        }
        $n = round((float) $nilai, 2);

        return rtrim(rtrim(number_format($n, 2, ',', '.'), '0'), ',');
    }

    public function scopeCari(Builder $query, string $kata): Builder
    {
        if ($kata === '') {
            return $query;
        }

        $like = '%' . addcslashes($kata, '%_\\') . '%';

        return $query->where(function (Builder $w) use ($like) {
            foreach (['nim', 'nama', 'judul', 'pembimbing_1', 'pembimbing_2', 'penguji', 'status'] as $kolom) {
                $w->orWhere($kolom, 'like', $like);
            }
        });
    }

    /** @return string[] nama dosen yang terlibat (pembimbing 1, pembimbing 2 bila ada, penguji) */
    public function namaDosen(): array
    {
        return array_values(array_filter([$this->pembimbing_1, $this->pembimbing_2, $this->penguji]));
    }

    /**
     * Cari jadwal lain yang memakai dosen yang sama pada waktu yang berdekatan.
     *
     * @param  string[] $namaDosen
     * @param  string[] $status     status jadwal yang ikut diperiksa
     * @return array{0: self, 1: string}|null [jadwal yang bentrok, nama dosen yang bentrok]
     */
    public static function cariBentrok(array $namaDosen, string $tanggal, string $jam, array $status, ?int $kecualiId = null): ?array
    {
        $dosenDicek = self::normalisasiNama($namaDosen);
        $menitMulai = self::keMenit($jam);

        $kandidat = static::query()
            ->whereDate('tanggal_ujian', $tanggal)
            ->whereIn('status', $status)
            ->when($kecualiId, fn (Builder $q) => $q->whereKeyNot($kecualiId))
            ->orderBy('jam_ujian')
            ->get();

        foreach ($kandidat as $lain) {
            if (abs(self::keMenit($lain->jam_ujian) - $menitMulai) >= self::DURASI_MENIT) {
                continue;
            }

            $sama = array_intersect_key(self::normalisasiNama($lain->namaDosen()), $dosenDicek);

            if ($sama !== []) {
                return [$lain, reset($sama)];
            }
        }

        return null;
    }

    /** @return array<string, string> kunci huruf kecil => nama asli, supaya beda kapital/spasi tidak lolos */
    private static function normalisasiNama(array $nama): array
    {
        $hasil = [];
        foreach ($nama as $n) {
            $n = trim((string) $n);
            if ($n !== '') {
                $hasil[mb_strtolower(preg_replace('/\s+/', ' ', $n))] = $n;
            }
        }

        return $hasil;
    }

    /** "09:30" atau "09:30:00" menjadi 570 */
    public static function keMenit(string $jam): int
    {
        [$h, $m] = array_map('intval', explode(':', $jam) + [0, 0]);

        return $h * 60 + $m;
    }

    public function hariIndo(): string
    {
        return $this->tanggal_ujian->locale('id')->translatedFormat('l');
    }

    public function tanggalIndo(): string
    {
        return $this->tanggal_ujian->locale('id')->translatedFormat('d F Y');
    }

    public function jamWib(): string
    {
        return substr((string) $this->jam_ujian, 0, 5) . ' WIB';
    }
}