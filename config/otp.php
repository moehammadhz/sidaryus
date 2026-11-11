<?php

return [
    /*
    | Cara kode OTP dikirim:
    |  - "log"    : tidak dikirim ke WhatsApp, hanya dicatat di storage/logs/laravel.log (untuk pengembangan)
    |  - "fonnte" : dikirim lewat gateway WhatsApp Fonnte (isi FONNTE_TOKEN)
    | Untuk gateway lain, buat kelas baru yang mengimplementasikan App\Services\Otp\PengirimOtp
    | lalu daftarkan di App\Providers\AppServiceProvider.
    */
    'driver' => env('OTP_DRIVER', 'log'),

    // Tampilkan kode di layar verifikasi. HANYA untuk uji coba; matikan di server sungguhan.
    'tampilkan_kode' => (bool) env('OTP_TAMPILKAN_KODE', false),

    'fonnte' => [
        'token' => env('FONNTE_TOKEN'),
        'url'   => env('FONNTE_URL', 'https://api.fonnte.com/send'),
    ],
];
