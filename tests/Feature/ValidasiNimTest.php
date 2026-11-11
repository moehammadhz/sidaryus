<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\Otp\PengirimOtp;
use Tests\Support\PengirimOtpPalsu;
use Tests\TestCase;

class ValidasiNimTest extends TestCase
{
    use RefreshDatabase;

    private PengirimOtpPalsu $wa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->wa = new PengirimOtpPalsu();
        $this->app->instance(PengirimOtp::class, $this->wa);
    }

    /** Isi formulir lalu masukkan kode OTP yang "dikirim" ke WhatsApp */
    private function daftarLengkap(array $isian)
    {
        $this->post(route('register.store'), $isian)->assertSessionHasNoErrors()->assertRedirect(route('register.verifikasi'));

        return $this->post(route('register.konfirmasi'), ['kode' => $this->wa->kodeTerakhir()]);
    }

    private function isianDaftar(array $ubah = []): array
    {
        return $ubah + [
            'name'                  => 'Nayla Contoh',
            'username'              => '22110015',
            'program_studi'         => 'Informatika',
            'kelas'                 => 'A',
            'angkatan'              => '2022',
            'no_wa'                 => '081234567890',
            'password'              => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ];
    }

    public function test_nim_berisi_huruf_ditolak_saat_registrasi(): void
    {
        foreach (['abc12345', '221100A5', '22-11001', 'admin'] as $nim) {
            $this->post(route('register.store'), $this->isianDaftar(['username' => $nim]))
                ->assertSessionHasErrors(['username' => 'NIM harus berupa 8 digit angka.']);
        }

        $this->assertDatabaseCount('users', 0);
    }

    public function test_panjang_nim_harus_tepat(): void
    {
        foreach (['2211001', '221100150'] as $nim) { // 7 dan 9 digit
            $this->post(route('register.store'), $this->isianDaftar(['username' => $nim]))
                ->assertSessionHasErrors('username');
        }
    }

    public function test_registrasi_valid_menyimpan_data_akademik(): void
    {
        $this->daftarLengkap($this->isianDaftar())
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('users', [
            'username' => '22110015', 'role' => 'mahasiswa',
            'program_studi' => 'Informatika', 'kelas' => 'A', 'angkatan' => 2022,
        ]);
    }

    public function test_spasi_di_nim_dibuang(): void
    {
        $this->daftarLengkap($this->isianDaftar(['username' => ' 22 110 015 ']));

        $this->assertDatabaseHas('users', ['username' => '22110015']);
    }

    public function test_nim_ganda_ditolak(): void
    {
        $this->daftarLengkap($this->isianDaftar());
        auth()->logout();

        $this->post(route('register.store'), $this->isianDaftar(['name' => 'Orang Lain', 'no_wa' => '081299990000']))
            ->assertSessionHasErrors(['username' => 'NIM sudah terdaftar.']);
    }

    public function test_prodi_kelas_angkatan_wajib_dan_harus_valid(): void
    {
        $this->post(route('register.store'), $this->isianDaftar([
            'program_studi' => 'Kedokteran', 'kelas' => 'Z', 'angkatan' => '1999',
        ]))->assertSessionHasErrors(['program_studi', 'kelas', 'angkatan']);
    }

    public function test_form_registrasi_memakai_keyboard_angka(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertSee('inputmode="numeric"', false)
            ->assertSee('pattern="[0-9]{8}"', false)
            ->assertSee('maxlength="8"', false);
    }

    public function test_admin_tidak_bisa_mengubah_nim_menjadi_huruf(): void
    {
        $admin = User::create(['name' => 'Admin', 'username' => 'admin', 'password' => 'password123', 'role' => Role::Admin->value]);
        $mhs = User::create([
            'name' => 'Mhs', 'username' => '21110001', 'password' => 'password123', 'role' => Role::Mahasiswa->value,
            'program_studi' => 'Informatika', 'kelas' => 'A', 'angkatan' => 2021,
        ]);

        $isian = ['name' => 'Mhs', 'program_studi' => 'Informatika', 'kelas' => 'A', 'angkatan' => 2021];

        $this->actingAs($admin)
            ->put(route('admin.mahasiswa.update', $mhs), $isian + ['username' => 'NIM-SALAH1'])
            ->assertSessionHasErrorsIn('edit', 'username');
        $this->assertSame('21110001', $mhs->fresh()->username);

        // NIM sendiri tidak dianggap ganda saat disimpan ulang
        $this->actingAs($admin)
            ->put(route('admin.mahasiswa.update', $mhs), $isian + ['username' => '21110002'])
            ->assertSessionHasNoErrors();
        $this->assertSame('21110002', $mhs->fresh()->username);
    }

    public function test_login_petugas_dengan_username_huruf_tetap_bisa(): void
    {
        User::create(['name' => 'Staf', 'username' => 'pengajaran', 'password' => 'password123', 'role' => Role::Pengajaran->value]);

        $this->post(route('login.store'), ['username' => 'pengajaran', 'password' => 'password123'])
            ->assertRedirect(route('dashboard'));
    }
}
