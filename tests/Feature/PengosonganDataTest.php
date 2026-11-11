<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Dosen;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DataContohSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PengosonganDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_bawaan_hanya_membuat_admin_dan_dosen(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(['admin'], User::pluck('username')->all());
        $this->assertGreaterThan(0, Dosen::count());
        $this->assertDatabaseCount('jadwal_pendadaran', 0);
        $this->assertDatabaseCount('periode', 0);
    }

    public function test_kosongkan_menyisakan_admin_dan_data_master(): void
    {
        Storage::fake('local');
        $this->seed(DataContohSeeder::class);
        Storage::disk('local')->put('dokumen/99/x.pdf', 'x');
        $this->assertGreaterThan(1, User::count());
        $this->assertGreaterThan(0, DB::table('jadwal_pendadaran')->count());
        $persyaratan = DB::table('persyaratan')->count();
        $dosen = Dosen::count();

        $this->artisan('sidaryus:kosongkan', ['--paksa' => true])->assertSuccessful();

        $this->assertSame([Role::Admin->value], User::pluck('role')->map(fn ($r) => $r instanceof Role ? $r->value : $r)->unique()->values()->all());
        foreach (['jadwal_pendadaran', 'dokumen_mahasiswa', 'periode', 'riwayat_bukti_bayar', 'riwayat_penolakan_jadwal', 'otp_registrasi'] as $t) {
            $this->assertDatabaseCount($t, 0);
        }
        $this->assertDatabaseCount('persyaratan', $persyaratan);
        $this->assertSame($dosen, Dosen::count());
        $this->assertFalse(Storage::disk('local')->exists('dokumen/99/x.pdf'));
    }

    public function test_kosongkan_membuat_admin_jika_terhapus(): void
    {
        User::create(['name' => 'Mhs', 'username' => '21110003', 'password' => 'x', 'role' => Role::Mahasiswa->value]);

        $this->artisan('sidaryus:kosongkan', ['--paksa' => true])->assertSuccessful();

        $this->assertSame(['admin'], User::pluck('username')->all());
    }

    public function test_kosongkan_bisa_dibatalkan(): void
    {
        User::create(['name' => 'Mhs', 'username' => '21110003', 'password' => 'x', 'role' => Role::Mahasiswa->value]);

        $this->artisan('sidaryus:kosongkan')->expectsConfirmation('Lanjutkan?', 'no')->assertSuccessful();

        $this->assertDatabaseHas('users', ['username' => '21110003']);
    }

    public function test_sesi_habis_kembali_ke_halaman_dengan_pesan_bukan_page_expired(): void
    {
        Route::post('/uji-sesi-habis', fn () => abort(419))->middleware('web');

        $this->from(route('register'))->post('/uji-sesi-habis', ['name' => 'Ramadhan', 'password' => 'rahasia'])
            ->assertRedirect(route('register'))
            ->assertSessionHas('error', fn ($e) => str_contains($e, 'Sesi halaman sudah berakhir'))
            ->assertSessionHasInput('name', 'Ramadhan')
            ->assertSessionMissing('_old_input.password');

        $this->get(route('register'))->assertSee('Sesi halaman sudah berakhir');
        $this->from(route('login'))->post('/uji-sesi-habis')->assertRedirect(route('login'));
        $this->get(route('login'))->assertSee('Sesi halaman sudah berakhir');
    }
}
