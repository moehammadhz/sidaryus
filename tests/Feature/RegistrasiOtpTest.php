<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Jobs\KirimOtpWaJob;
use App\Models\OtpCode;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class RegistrasiOtpTest extends TestCase
{
    use RefreshDatabase;

    private OtpService $otpService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->otpService = app(OtpService::class);
        config(['services.fonnte.token' => 'dummy-fonnte-token-test']);
        Http::fake([
            'https://api.fonnte.com/*' => Http::response(['status' => true], 200),
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function isian(array $ubah = []): array
    {
        return $ubah + [
            'name'                  => 'Nayla Contoh',
            'username'              => '22110015',
            'no_wa'                 => '0812-3456-7890',
            'program_studi'         => 'Informatika',
            'kelas'                 => 'A',
            'angkatan'              => '2022',
            'password'              => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ];
    }

    public function test_alur_registrasi_user_dibuat_pending_dan_aktif_setelah_otp_benar(): void
    {
        Queue::fake();

        // 1. Registrasi awal: user langsung dibuat namun belum terverifikasi
        $this->post(route('register.store'), $this->isian())
            ->assertRedirect(route('register.verifikasi'));

        $u = User::where('username', '22110015')->first();
        $this->assertNotNull($u);
        $this->assertSame('6281234567890', $u->no_wa); // nomor dinormalisasi
        $this->assertNull($u->wa_verified_at);         // belum terverifikasi
        $this->assertFalse($u->isWaVerified());

        // Pastikan job antrean terkirim
        Queue::assertPushed(KirimOtpWaJob::class);

        // Ambil kode dari database via OTP code user
        $otp = OtpCode::where('user_id', $u->id)->first();
        $this->assertNotNull($otp);
        $this->assertSame(0, $otp->attempts);

        // Mengambil kode dari session uji coba
        $kode = session('kode_uji');
        $this->assertNotEmpty($kode);

        // Halaman verifikasi menampilkan nomor disamarkan
        $this->get(route('register.verifikasi'))
            ->assertOk()
            ->assertSee('0812-****-7890');

        // 2. Input OTP benar -> akun aktif & langsung login ke dashboard
        $this->post(route('register.konfirmasi'), ['kode' => $kode])
            ->assertRedirect(route('dashboard'));

        $u->refresh();
        $this->assertNotNull($u->wa_verified_at);
        $this->assertTrue($u->isWaVerified());
        $this->assertAuthenticatedAs($u);
        $this->assertDatabaseMissing('otp_codes', ['user_id' => $u->id]);
    }

    public function test_login_sebelum_verifikasi_diarahkan_ke_halaman_otp(): void
    {
        // Buat user yang belum terverifikasi
        $user = User::create([
            'name'           => 'Mahasiswa Belum Verif',
            'username'       => '22110099',
            'no_wa'          => '6281299998888',
            'wa_verified_at' => null,
            'role'           => Role::Mahasiswa->value,
            'password'       => 'password123',
        ]);

        $this->post(route('login.store'), [
            'username' => '22110099',
            'password' => 'password123',
        ])->assertRedirect(route('register.verifikasi'))
          ->assertSessionHas('warning', 'Verifikasi nomor WhatsApp dulu ya.')
          ->assertSessionHas('otp_user_id', $user->id);

        $this->assertGuest();
    }

    public function test_login_sesudah_verifikasi_langsung_ke_dashboard(): void
    {
        $user = User::create([
            'name'           => 'Mahasiswa Sudah Verif',
            'username'       => '22110088',
            'no_wa'          => '6281277776666',
            'wa_verified_at' => now(),
            'role'           => Role::Mahasiswa->value,
            'password'       => 'password123',
        ]);

        $this->post(route('login.store'), [
            'username' => '22110088',
            'password' => 'password123',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_otp_salah_mengurangi_kesempatan(): void
    {
        $this->post(route('register.store'), $this->isian());
        $u = User::where('username', '22110015')->first();

        $this->post(route('register.konfirmasi'), ['kode' => '000000'])
            ->assertSessionHasErrors('kode');

        $otp = OtpCode::where('user_id', $u->id)->first();
        $this->assertSame(1, $otp->attempts);
        $this->assertNull($u->fresh()->wa_verified_at);
    }

    public function test_otp_5x_salah_terkunci(): void
    {
        $this->post(route('register.store'), $this->isian());
        $u = User::where('username', '22110015')->first();
        $kodeBenar = session('kode_uji');

        for ($i = 1; $i <= OtpCode::MAKS_PERCOBAAN; $i++) {
            $this->post(route('register.konfirmasi'), ['kode' => '999999'])
                ->assertSessionHasErrors('kode');
        }

        $otp = OtpCode::where('user_id', $u->id)->first();
        $this->assertSame(5, $otp->attempts);
        $this->assertTrue($otp->isLocked());

        // Kode benar pun ditolak setelah batas percobaan habis
        $this->post(route('register.konfirmasi'), ['kode' => $kodeBenar])
            ->assertSessionHasErrors('kode');

        $this->assertNull($u->fresh()->wa_verified_at);
    }

    public function test_otp_kedaluwarsa_dan_kirim_ulang(): void
    {
        Carbon::setTestNow('2026-11-10 10:00:00');
        $this->post(route('register.store'), $this->isian());
        $kodeLama = session('kode_uji');

        // Lewat 5 menit (kedaluwarsa)
        Carbon::setTestNow('2026-11-10 10:06:00');
        $this->post(route('register.konfirmasi'), ['kode' => $kodeLama])
            ->assertSessionHasErrors('kode');

        // Kirim ulang kode baru
        $this->post(route('register.kirim-ulang'))->assertSessionHas('status');
        $kodeBaru = session('kode_uji');
        $this->assertNotEmpty($kodeBaru);

        // Verifikasi dengan kode baru berhasil
        $this->post(route('register.konfirmasi'), ['kode' => $kodeBaru])
            ->assertRedirect(route('dashboard'));

        $u = User::where('username', '22110015')->first();
        $this->assertTrue($u->isWaVerified());
    }

    public function test_kirim_ulang_sebelum_60_detik_ditolak_cooldown(): void
    {
        Carbon::setTestNow('2026-11-10 10:00:00');
        $this->post(route('register.store'), $this->isian());

        // Kirim ulang setelah 20 detik (belum 60 detik)
        Carbon::setTestNow('2026-11-10 10:00:20');
        $this->post(route('register.kirim-ulang'))
            ->assertSessionHasErrors('kode');

        // Kirim ulang setelah 61 detik (sudah melewati cooldown)
        Carbon::setTestNow('2026-11-10 10:01:02');
        $this->post(route('register.kirim-ulang'))
            ->assertSessionHas('status');
    }

    public function test_nomor_wa_duplikat_ditolak(): void
    {
        User::create([
            'name'           => 'User Lama',
            'username'       => '22110001',
            'no_wa'          => '6281234567890',
            'wa_verified_at' => now(),
            'role'           => Role::Mahasiswa->value,
            'password'       => 'password123',
        ]);

        $this->post(route('register.store'), $this->isian(['username' => '22110002', 'no_wa' => '081234567890']))
            ->assertSessionHasErrors('no_wa');
    }

    public function test_format_nomor_08xx_628xx_plus628xx_dinormalisasi(): void
    {
        $this->assertSame('6281234567890', $this->otpService->normalizePhone('081234567890'));
        $this->assertSame('6281234567890', $this->otpService->normalizePhone('6281234567890'));
        $this->assertSame('6281234567890', $this->otpService->normalizePhone('+62 812-3456-7890'));
        $this->assertSame('6281234567890', $this->otpService->normalizePhone('81234567890'));

        $this->assertNull($this->otpService->normalizePhone('12345'));
        $this->assertNull($this->otpService->normalizePhone('0217654321')); // bukan nomor seluler 08
    }

    public function test_ganti_nomor_wa_di_halaman_otp(): void
    {
        $this->post(route('register.store'), $this->isian(['no_wa' => '081211112222']));
        $u = User::where('username', '22110015')->first();
        $this->assertSame('6281211112222', $u->no_wa);

        $this->post(route('register.ganti-nomor'), ['no_wa_baru' => '081299990000'])
            ->assertSessionHas('status');

        $u->refresh();
        $this->assertSame('6281299990000', $u->no_wa);

        $kodeBaru = session('kode_uji');
        $this->assertNotEmpty($kodeBaru);
        $this->post(route('register.konfirmasi'), ['kode' => $kodeBaru])
            ->assertRedirect(route('dashboard'));

        $this->assertTrue($u->fresh()->isWaVerified());
    }

    public function test_verifikasi_manual_oleh_admin(): void
    {
        $admin = User::create([
            'name'           => 'Admin Sistem',
            'username'       => 'admin_test',
            'role'           => Role::Admin->value,
            'password'       => 'password123',
            'wa_verified_at' => now(),
        ]);

        $mhs = User::create([
            'name'           => 'Mahasiswa Terkendala',
            'username'       => '22110077',
            'no_wa'          => '6281200001111',
            'role'           => Role::Mahasiswa->value,
            'password'       => 'password123',
            'wa_verified_at' => null,
        ]);

        // Belum terverifikasi
        $this->assertFalse($mhs->isWaVerified());

        // Admin melakukan verifikasi manual
        $this->actingAs($admin)
            ->post(route('admin.mahasiswa.verifikasi-manual', $mhs))
            ->assertRedirect()
            ->assertSessionHas('status');

        $mhs->refresh();
        $this->assertTrue($mhs->isWaVerified());
        $this->assertNotNull($mhs->wa_verified_at);
        $this->assertSame($admin->id, $mhs->wa_verified_by);
        $this->assertSame('Admin Sistem', $mhs->waVerifiedBy->name);
    }
}
