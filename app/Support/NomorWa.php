<?php

namespace App\Support;

/** Nomor WhatsApp Indonesia disimpan dalam format internasional tanpa "+", contoh 6281234567890 */
class NomorWa
{
    /** "0812-3456-7890", "+62 812 3456 7890", "812..." -> "6281234567890" */
    public static function normalisasi(?string $nomor): string
    {
        $angka = preg_replace('/\D+/', '', (string) $nomor);

        if (str_starts_with($angka, '0')) {
            $angka = '62' . substr($angka, 1);
        } elseif (str_starts_with($angka, '8')) {
            $angka = '62' . $angka;
        }

        return $angka;
    }

    /** Nomor seluler Indonesia: 62 8xx, total 10–13 digit setelah 62 */
    public static function valid(string $normal): bool
    {
        return (bool) preg_match('/^628[1-9][0-9]{7,11}$/', $normal);
    }

    /** "6281234567890" -> "0812-3456-7890" untuk ditampilkan */
    public static function tampil(?string $normal): string
    {
        if (! $normal) {
            return '-';
        }
        $lokal = str_starts_with($normal, '62') ? '0' . substr($normal, 2) : $normal;

        return trim(chunk_split($lokal, 4, '-'), '-');
    }

    /** "0812-****-7890" supaya nomor tidak terbaca utuh di layar verifikasi */
    public static function samarkan(string $normal): string
    {
        $t = self::tampil($normal);

        return substr($t, 0, 4) . '-****-' . substr($t, -4);
    }
}
