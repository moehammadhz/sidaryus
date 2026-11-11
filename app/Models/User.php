<?php

namespace App\Models;

use App\Enums\Role;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Validation\Rule;

/**
 * @property int         $id
 * @property string      $name
 * @property string      $username
 * @property string|null $email
 * @property string|null $no_wa
 * @property \Illuminate\Support\Carbon|null $wa_verified_at
 * @property int|null    $wa_verified_by
 * @property Role        $role
 * @property string|null $program_studi
 * @property string|null $kelas
 * @property int|null    $angkatan
 * @property int|null    $periode_id
 */
class User extends Authenticatable
{
    use Notifiable;

    public const PROGRAM_STUDI = ['Informatika', 'Teknik Mesin', 'Teknik Sipil'];
    public const KELAS         = ['A', 'B'];
    public const ANGKATAN_MIN  = 2020;
    public const ANGKATAN_MAX  = 2026;

    /** NIM Universitas Janabadra: 8 digit angka, contoh 21110003 */
    public const NIM_PANJANG = 8;

    /** Aturan validasi NIM, dipakai di registrasi dan di menu Kelola Mahasiswa */
    public static function aturanNim(?int $abaikanId = null): array
    {
        return [
            'required', 'string',
            'regex:/^[0-9]{' . self::NIM_PANJANG . '}$/',
            Rule::unique('users', 'username')->ignore($abaikanId),
        ];
    }

    /** Pesan validasi NIM; $kolom = nama input (biasanya "username") */
    public static function pesanNim(string $kolom = 'username'): array
    {
        return [
            "{$kolom}.required" => 'NIM wajib diisi.',
            "{$kolom}.regex"    => 'NIM harus berupa ' . self::NIM_PANJANG . ' digit angka.',
            "{$kolom}.unique"   => 'NIM sudah terdaftar.',
        ];
    }

    protected $fillable = [
        'name', 'username', 'email', 'role',
        'program_studi', 'kelas', 'angkatan', 'no_wa',
        'wa_verified_at', 'wa_verified_by',
        'password',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at'   => 'datetime',
            'password'            => 'hashed',
            'angkatan'            => 'integer',
            'role'                => Role::class,
            'periode_daftar_at'   => 'datetime',
            'wa_terverifikasi_at' => 'datetime',
            'wa_verified_at'      => 'datetime',
        ];
    }

    public function otpCode(): HasOne
    {
        return $this->hasOne(OtpCode::class, 'user_id');
    }

    public function waVerifiedBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'wa_verified_by');
    }

    public function isWaVerified(): bool
    {
        // Pengguna non-mahasiswa (Admin, Pengajaran, BAAK) tidak melalui alur pendaftaran OTP WhatsApp
        if ($this->role !== Role::Mahasiswa) {
            return true;
        }

        return ! is_null($this->wa_verified_at) || ! is_null($this->wa_terverifikasi_at);
    }

    public function markWaAsVerified(?int $verifiedById = null): void
    {
        $now = now();
        $this->wa_verified_at = $now;
        $this->wa_terverifikasi_at = $now;
        if ($verifiedById !== null) {
            $this->wa_verified_by = $verifiedById;
        }
        $this->save();
    }

    public function hasRole(Role ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function periode(): BelongsTo
    {
        return $this->belongsTo(Periode::class);
    }

    public function jadwal(): HasMany
    {
        return $this->hasMany(JadwalPendadaran::class, 'user_id');
    }

    /** Pengajuan jadwal terakhir (pengajuan ulang setelah tidak lulus membuat baris baru) */
    public function jadwalTerakhir(): ?JadwalPendadaran
    {
        return $this->jadwal()->latest('id')->first();
    }

    /** Jadwal yang hasilnya Lulus, atau null jika belum lulus */
    public function jadwalLulus(): ?JadwalPendadaran
    {
        return $this->jadwal()->where('hasil', JadwalPendadaran::LULUS)->latest('id')->first();
    }

    public function dokumen(): HasMany
    {
        return $this->hasMany(DokumenMahasiswa::class, 'user_id');
    }

    /**
     * Persyaratan yang dokumennya belum disetujui (belum diunggah, menunggu, atau ditolak).
     *
     * @return Collection<int, Persyaratan>
     */
    public function syaratBelumDisetujui(): Collection
    {
        $disetujui = $this->dokumen()
            ->where('status', DokumenMahasiswa::DISETUJUI)
            ->pluck('persyaratan_id');

        return Persyaratan::whereNotIn('id', $disetujui)->orderBy('urutan')->orderBy('id')->get();
    }
}
