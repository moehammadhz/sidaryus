<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\DokumenMahasiswa;
use App\Models\JadwalPendadaran;
use App\Models\Persyaratan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PerbaikanUmumTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create(['name' => 'Admin', 'username' => 'admin', 'password' => 'password123', 'role' => Role::Admin->value]);
    }

    private function mahasiswa(string $nim = '21110001', string $nama = 'Mahasiswa Contoh'): User
    {
        return User::create([
            'name' => $nama, 'username' => $nim, 'password' => 'password123', 'role' => Role::Mahasiswa->value,
            'program_studi' => 'Informatika', 'kelas' => 'A', 'angkatan' => 2021,
        ]);
    }

    public function test_form_upload_hanya_menawarkan_pdf(): void
    {
        $this->actingAs($this->mahasiswa())
            ->get(route('mahasiswa.dokumen.index'))
            ->assertOk()
            ->assertSee('accept=".pdf,application/pdf"', false)
            ->assertDontSee('.jpg', false);
    }

    public function test_pesan_gagal_upload_hanya_muncul_di_kartu_yang_diunggah(): void
    {
        $mhs = $this->mahasiswa();
        $syarat = Persyaratan::orderBy('urutan')->get();
        $this->assertGreaterThan(1, $syarat->count());

        $html = $this->actingAs($mhs)
            ->from(route('mahasiswa.dokumen.index'))
            ->followingRedirects()
            ->post(route('mahasiswa.dokumen.store'), [
                'persyaratan_id' => $syarat[1]->id,
                'file'           => UploadedFile::fake()->create('foto.jpg', 20, 'image/jpeg'), // tanpa ekstensi GD
            ])
            ->getContent();

        $this->assertSame(1, substr_count($html, 'Berkas harus berformat PDF.'));
    }

    public function test_nama_dengan_apostrof_tampil_benar_di_halaman_jadwal(): void
    {
        $j = JadwalPendadaran::create([
            'nim' => '21110001', 'nama' => "Nur'aini", 'judul' => 'J', 'pembimbing_1' => 'A', 'penguji' => 'B',
            'tanggal_ujian' => '2026-11-03', 'jam_ujian' => '10:00', 'status' => JadwalPendadaran::PENDING,
        ]);

        $this->actingAs($this->admin())->get(route('admin.jadwal.show', $j))
            ->assertOk()
            ->assertSee("Nur&#039;aini", false)        // di-escape sekali, tampil "Nur'aini" di browser
            ->assertDontSee('Nur&amp;#039;aini', false);
    }

    public function test_hapus_persyaratan_ikut_menghapus_berkas_fisik(): void
    {
        Storage::fake('local');
        $mhs = $this->mahasiswa();
        $syarat = Persyaratan::first();

        Storage::disk('local')->put("dokumen/{$mhs->id}/ktm.pdf", '%PDF');
        DokumenMahasiswa::create([
            'user_id' => $mhs->id, 'persyaratan_id' => $syarat->id, 'path' => "dokumen/{$mhs->id}/ktm.pdf",
            'nama_asli' => 'ktm.pdf', 'mime' => 'application/pdf', 'ukuran' => 4,
            'status' => DokumenMahasiswa::MENUNGGU, 'diunggah_at' => now(),
        ]);

        $admin = $this->admin();

        // dialog hapus diberi tahu jumlah berkas yang ikut terhapus
        $this->actingAs($admin)->get(route('admin.persyaratan.index'))->assertSee('data-jumlah="1"', false);

        $this->actingAs($admin)
            ->delete(route('admin.persyaratan.destroy', $syarat))
            ->assertSessionHas('status', fn ($p) => str_contains($p, '1 berkas mahasiswa ikut dihapus'));

        $this->assertDatabaseCount('dokumen_mahasiswa', 0);
        $this->assertFalse(Storage::disk('local')->exists("dokumen/{$mhs->id}/ktm.pdf"), 'Berkas fisik seharusnya ikut terhapus.');
    }

    public function test_ubah_nim_dan_nama_mahasiswa_ikut_memperbarui_jadwal(): void
    {
        $mhs = $this->mahasiswa();
        JadwalPendadaran::create([
            'user_id' => $mhs->id, 'nim' => $mhs->username, 'nama' => $mhs->name, 'judul' => 'J',
            'pembimbing_1' => 'A', 'penguji' => 'B', 'tanggal_ujian' => '2026-11-03', 'jam_ujian' => '10:00',
            'status' => JadwalPendadaran::PENDING,
        ]);

        $this->actingAs($this->admin())->put(route('admin.mahasiswa.update', $mhs), [
            'name' => 'Nama Diperbaiki', 'username' => '21110099',
            'program_studi' => 'Informatika', 'kelas' => 'A', 'angkatan' => 2021,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('jadwal_pendadaran', ['user_id' => $mhs->id, 'nim' => '21110099', 'nama' => 'Nama Diperbaiki']);
    }

    public function test_halaman_login_tidak_punya_tautan_mati(): void
    {
        $this->get(route('login'))->assertOk()->assertDontSee('href="#"', false);
    }
}
