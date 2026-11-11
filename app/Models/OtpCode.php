<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string $code
 * @property \Illuminate\Support\Carbon $expires_at
 * @property int $attempts
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class OtpCode extends Model
{
    public const MAKS_PERCOBAAN = 5;
    public const MASA_BERLAKU_MENIT = 5;
    public const JEDA_KIRIM_ULANG_DETIK = 60;

    protected $table = 'otp_codes';

    protected $fillable = [
        'user_id',
        'code',
        'expires_at',
        'attempts',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'attempts'   => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return now()->greaterThan($this->expires_at);
    }

    public function isLocked(): bool
    {
        return $this->attempts >= self::MAKS_PERCOBAAN;
    }

    /**
     * Hitung sisa detik cooldown kirim ulang berdasarkan updated_at.
     */
    public function remainingCooldown(): int
    {
        if (! $this->updated_at) {
            return 0;
        }

        $elapsed = abs((int) now()->diffInSeconds($this->updated_at));

        return (int) max(0, self::JEDA_KIRIM_ULANG_DETIK - $elapsed);
    }
}
