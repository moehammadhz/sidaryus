<?php

namespace App\Providers;

use App\Services\Otp\FonntePengirimOtp;
use App\Services\Otp\LogPengirimOtp;
use App\Services\Otp\PengirimOtp;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Pengirim OTP dipilih lewat OTP_DRIVER di .env (lihat config/otp.php)
        $this->app->bind(PengirimOtp::class, fn () => match (config('otp.driver')) {
            'fonnte' => new FonntePengirimOtp(),
            default  => new LogPengirimOtp(),
        });
    }

    public function boot(): void
    {
        //
    }
}
