<?php

namespace Tests\Feature;

use App\Jobs\KirimOtpWaJob;
use App\Models\OtpCode;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class OtpFonnteTest extends TestCase
{
    use RefreshDatabase;

    private OtpService $otpService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->otpService = app(OtpService::class);
        config(['services.fonnte.token' => 'test-fonnte-token-12345']);
    }

    public function test_send_wa_fonnte_sukses_ketika_http_200_dan_status_true(): void
    {
        Http::fake([
            'https://api.fonnte.com/send' => Http::response(['status' => true, 'target' => ['6281234567890']], 200),
        ]);

        $hasil = $this->otpService->sendWa('6281234567890', 'Pesan uji coba');
        $this->assertTrue($hasil);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.fonnte.com/send'
                && $request->hasHeader('Authorization', 'test-fonnte-token-12345')
                && $request['target'] === '6281234567890'
                && $request['message'] === 'Pesan uji coba';
        });
    }

    public function test_send_wa_fonnte_gagal_ketika_gateway_menolak_atau_status_false(): void
    {
        Http::fake([
            'https://api.fonnte.com/send' => Http::response(['status' => false, 'reason' => 'device disconnected'], 200),
        ]);

        $hasil = $this->otpService->sendWa('6281234567890', 'Pesan uji coba');
        $this->assertFalse($hasil);
    }

    public function test_send_wa_fonnte_menangani_exception_dan_kembalikan_false(): void
    {
        Http::fake([
            'https://api.fonnte.com/send' => function () {
                throw new \Illuminate\Http\Client\ConnectionException('Connection timed out');
            },
        ]);

        $hasil = $this->otpService->sendWa('6281234567890', 'Pesan uji coba');
        $this->assertFalse($hasil);
    }

    public function test_kirim_otp_wa_job_sukses_dan_skip_jika_sudah_terverifikasi(): void
    {
        // Simulasi Fonnte gateway berhasil
        Http::fake([
            'https://api.fonnte.com/send' => Http::response(['status' => true], 200),
        ]);

        $user = User::create([
            'name'           => 'Test Queue',
            'username'       => '22110091',
            'no_wa'          => '6281234567890',
            'role'           => 'mahasiswa',
            'password'       => 'password123',
            'wa_verified_at' => null,
        ]);

        $kode = $this->otpService->issue($user);

        // Jalankan job secara langsung
        $job = new KirimOtpWaJob($user->id, $user->no_wa, "Kode Anda {$kode}");
        $job->handle($this->otpService);

        // Pastikan Fonnte dipanggil
        Http::assertSentCount(2); // 1 dari dispatch otomatis saat issue, 1 dari manual handle

        // Kasus 2: Jika user sudah diverifikasi, job berikutnya harus di-skip tanpa panggil Fonnte
        $user->markWaAsVerified();
        Http::fake(); // reset
        $job2 = new KirimOtpWaJob($user->id, $user->no_wa, "Kode Anda {$kode}");
        $job2->handle($this->otpService);

        Http::assertSentCount(0); // tidak dikirim ulang karena sudah terverifikasi
    }

    public function test_command_bersihkan_otp_kedaluwarsa(): void
    {
        $user = User::create([
            'name'           => 'User Expired',
            'username'       => '22110092',
            'no_wa'          => '6281234567891',
            'role'           => 'mahasiswa',
            'password'       => 'password123',
            'wa_verified_at' => null,
        ]);

        OtpCode::create([
            'user_id'    => $user->id,
            'code'       => 'dummyhash',
            'expires_at' => now()->subMinutes(10), // sudah kedaluwarsa 10 menit lalu
            'attempts'   => 0,
        ]);

        $this->assertDatabaseCount('otp_codes', 1);

        Artisan::call('otp:clean-expired');

        $this->assertDatabaseCount('otp_codes', 0);
    }

    public function test_uji_beban_100_registrasi_berurutan_dengan_http_fake(): void
    {
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);

        Queue::fake();

        Http::fake([
            'https://api.fonnte.com/send' => Http::response(['status' => true], 200),
        ]);

        $jumlahRegistrasi = 100;
        $jobs = [];

        for ($i = 1; $i <= $jumlahRegistrasi; $i++) {
            $nim = sprintf('2211%04d', $i);
            $noWa = sprintf('0812%08d', $i);

            $data = [
                'name'                  => "Mahasiswa Beban {$i}",
                'username'              => $nim,
                'no_wa'                 => $noWa,
                'program_studi'         => 'Informatika',
                'kelas'                 => 'A',
                'angkatan'              => '2022',
                'password'              => 'rahasia123',
                'password_confirmation' => 'rahasia123',
            ];

            $res = $this->post(route('register.store'), $data);
            $res->assertRedirect(route('register.verifikasi'));

            $u = User::where('username', $nim)->first();
            $this->assertNotNull($u);
            $this->assertNull($u->wa_verified_at);

            $otp = OtpCode::where('user_id', $u->id)->first();
            $this->assertNotNull($otp);

            // Simpan job untuk dieksekusi pekerja antrean
            $jobs[] = new KirimOtpWaJob($u->id, $u->no_wa, "Kode OTP untuk {$nim}");
        }

        // Pastikan seluruh 100 registrasi masuk ke database dan 100 job didorong ke antrean
        $this->assertDatabaseCount('users', $jumlahRegistrasi);
        $this->assertDatabaseCount('otp_codes', $jumlahRegistrasi);
        Queue::assertPushed(KirimOtpWaJob::class, $jumlahRegistrasi);

        // Proses seluruh 100 job di antrean (simulasi worker memproses antrean)
        foreach ($jobs as $job) {
            $job->handle($this->otpService);
        }

        // Pastikan seluruh 100 panggilan ke gateway Fonnte sukses terkirim tanpa error
        Http::assertSentCount($jumlahRegistrasi);
    }
}
