<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\DokumenMahasiswa;
use App\Models\Dosen;
use App\Models\JadwalPendadaran;
use App\Models\Persyaratan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PengajuanJadwalTest extends TestCase
{
    use RefreshDatabase;

    private const BUDI  = 'Dr. Budi Santoso, S.T., M.T.';
    private const SITI  = 'Ir. Siti Aminah, M.Eng.';
    private const RAHMAT = 'Dr. Rahmat Hidayat, S.T., M.Sc.';

    /** Selasa, hari kerja */
    private const BESOK = '2026-11-03';

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-11-02 08:00:00'); // Senin
        Storage::fake('local');

        foreach ([self::BUDI, self::SITI, self::RAHMAT] as $nama) {
            Dosen::create(['nama' => $nama, 'program_studi' => 'Informatika']);
        }

        Persyaratan::query()->delete(); // migrasi sudah mengisi persyaratan bawaan; test memakai dua saja
        Persyaratan::create(['kode' => 'KTM', 'nama' => 'Kartu Tanda Mahasiswa', 'urutan' => 1]);
        Persyaratan::create(['kode' => 'DHS', 'nama' => 'Daftar Hasil Studi', 'urutan' => 2]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // ---------- pembantu ----------

    private function mahasiswa(string $nim = '21110001', bool $dokumenLengkap = true): User
    {
        $user = User::create([
            'name' => "Mahasiswa {$nim}", 'username' => $nim, 'password' => 'password123',
            'role' => Role::Mahasiswa->value, 'program_studi' => 'Informatika', 'kelas' => 'A', 'angkatan' => 2021,
        ]);

        foreach (Persyaratan::all() as $i => $syarat) {
            // jika tidak lengkap, dokumen kedua dibiarkan "menunggu"
            $status = ($dokumenLengkap || $i === 0) ? DokumenMahasiswa::DISETUJUI : DokumenMahasiswa::MENUNGGU;

            DokumenMahasiswa::create([
                'user_id' => $user->id, 'persyaratan_id' => $syarat->id, 'path' => "dokumen/{$user->id}/{$syarat->kode}.pdf",
                'nama_asli' => "{$syarat->kode}.pdf", 'mime' => 'application/pdf', 'ukuran' => 1000,
                'status' => $status, 'diunggah_at' => now(),
            ]);
        }

        return $user;
    }

    private function admin(): User
    {
        return User::create(['name' => 'Admin', 'username' => 'admin', 'password' => 'password123', 'role' => Role::Admin->value]);
    }

    private function isian(array $ubah = []): array
    {
        return $ubah + [
            'judul'         => 'Sistem Informasi Pendadaran',
            'pembimbing_1'  => self::BUDI,
            'pembimbing_2'  => self::SITI,
            'penguji'       => self::RAHMAT,
            'tanggal_ujian' => self::BESOK,
            'jam_ujian'     => '10:00',
        ];
    }

    private function jadwalLain(array $ubah = []): JadwalPendadaran
    {
        return JadwalPendadaran::create($ubah + [
            'nim' => '21119999', 'nama' => 'Mahasiswa Lain', 'judul' => 'Judul Lain',
            'pembimbing_1' => self::SITI, 'pembimbing_2' => null, 'penguji' => self::BUDI,
            'tanggal_ujian' => self::BESOK, 'jam_ujian' => '10:00', 'status' => JadwalPendadaran::DISETUJUI,
        ]);
    }

    // ---------- poin 1: dokumen harus lengkap ----------

    public function test_jadwal_ditolak_jika_dokumen_belum_lengkap(): void
    {
        $this->actingAs($this->mahasiswa(dokumenLengkap: false))
            ->post(route('mahasiswa.jadwal.store'), $this->isian())
            ->assertSessionHas('error', fn ($pesan) => str_contains($pesan, 'Daftar Hasil Studi'));

        $this->assertDatabaseCount('jadwal_pendadaran', 0);
    }

    public function test_jadwal_tersimpan_jika_dokumen_lengkap(): void
    {
        $this->actingAs($this->mahasiswa())
            ->post(route('mahasiswa.jadwal.store'), $this->isian())
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('jadwal_pendadaran', ['nim' => '21110001', 'status' => JadwalPendadaran::PENDING]);
    }

    public function test_form_dikunci_jika_dokumen_belum_lengkap(): void
    {
        $this->actingAs($this->mahasiswa(dokumenLengkap: false))
            ->get(route('mahasiswa.jadwal.index'))
            ->assertOk()
            ->assertSee('Daftar Hasil Studi')
            ->assertDontSee('Ajukan Jadwal</button>', false);
    }

    // ---------- poin 2: tanggal & jam ----------

    public function test_tanggal_hari_ini_atau_lampau_ditolak(): void
    {
        $user = $this->mahasiswa();

        foreach (['2026-11-02', '2026-10-30'] as $tanggal) {
            $this->actingAs($user)
                ->post(route('mahasiswa.jadwal.store'), $this->isian(['tanggal_ujian' => $tanggal]))
                ->assertSessionHasErrors('tanggal_ujian');
        }
    }

    public function test_akhir_pekan_ditolak(): void
    {
        $this->actingAs($this->mahasiswa())
            ->post(route('mahasiswa.jadwal.store'), $this->isian(['tanggal_ujian' => '2026-11-07'])) // Sabtu
            ->assertSessionHasErrors(['tanggal_ujian' => 'Ujian tidak dapat dijadwalkan pada hari Sabtu atau Minggu.']);
    }

    public function test_jam_di_luar_jam_kerja_ditolak(): void
    {
        $user = $this->mahasiswa();

        foreach (['06:30', '18:00', '25:00', 'abc'] as $jam) {
            $this->actingAs($user)
                ->post(route('mahasiswa.jadwal.store'), $this->isian(['jam_ujian' => $jam]))
                ->assertSessionHasErrors('jam_ujian');
        }
    }

    // ---------- poin 3: dosen ----------

    public function test_dosen_harus_dari_daftar(): void
    {
        $this->actingAs($this->mahasiswa())
            ->post(route('mahasiswa.jadwal.store'), $this->isian(['pembimbing_1' => 'Dosen Asal Ketik']))
            ->assertSessionHasErrors('pembimbing_1');
    }

    public function test_penguji_tidak_boleh_sama_dengan_pembimbing(): void
    {
        $user = $this->mahasiswa();

        $this->actingAs($user)
            ->post(route('mahasiswa.jadwal.store'), $this->isian(['penguji' => self::BUDI]))
            ->assertSessionHasErrors('penguji');

        $this->actingAs($user)
            ->post(route('mahasiswa.jadwal.store'), $this->isian(['penguji' => self::SITI]))
            ->assertSessionHasErrors('penguji');

        $this->actingAs($user)
            ->post(route('mahasiswa.jadwal.store'), $this->isian(['pembimbing_2' => self::BUDI]))
            ->assertSessionHasErrors('pembimbing_2');
    }

    public function test_pembimbing_pendamping_wajib(): void
    {
        $this->actingAs($this->mahasiswa())
            ->post(route('mahasiswa.jadwal.store'), $this->isian(['pembimbing_2' => '']))
            ->assertSessionHasErrors(['pembimbing_2' => 'Pembimbing pendamping wajib diisi.']);

        $this->assertDatabaseCount('jadwal_pendadaran', 0);
    }

    // ---------- poin 4: bentrok ----------

    public function test_dosen_yang_sama_di_jam_berdekatan_bentrok(): void
    {
        $this->jadwalLain(); // Budi menguji besok 10:00

        $this->actingAs($this->mahasiswa())
            ->post(route('mahasiswa.jadwal.store'), $this->isian(['jam_ujian' => '11:00']))
            ->assertSessionHasErrors('jam_ujian');

        $this->assertDatabaseCount('jadwal_pendadaran', 1);
    }

    public function test_jarak_dua_jam_tidak_bentrok(): void
    {
        $this->jadwalLain();

        $this->actingAs($this->mahasiswa())
            ->post(route('mahasiswa.jadwal.store'), $this->isian(['jam_ujian' => '12:00']))
            ->assertSessionHasNoErrors();
    }

    public function test_dosen_berbeda_di_jam_sama_tidak_bentrok(): void
    {
        Dosen::create(['nama' => 'Dewi Lestari, M.Kom.', 'program_studi' => 'Informatika']);
        Dosen::create(['nama' => 'Eko Prasetyo, M.T.', 'program_studi' => 'Informatika']);
        $this->jadwalLain(['pembimbing_1' => 'Dewi Lestari, M.Kom.', 'penguji' => 'Eko Prasetyo, M.T.']);

        $this->actingAs($this->mahasiswa())
            ->post(route('mahasiswa.jadwal.store'), $this->isian()) // Budi, Siti, Rahmat di jam yang sama
            ->assertSessionHasNoErrors();
    }

    public function test_beda_huruf_besar_kecil_tetap_terdeteksi_bentrok(): void
    {
        $this->jadwalLain(['penguji' => '  dr. budi santoso,  s.t., m.t. ']); // data lama yang diketik bebas

        $this->actingAs($this->mahasiswa())
            ->post(route('mahasiswa.jadwal.store'), $this->isian())
            ->assertSessionHasErrors('jam_ujian');
    }

    public function test_jadwal_ditolak_tidak_dihitung_bentrok(): void
    {
        $this->jadwalLain(['status' => JadwalPendadaran::DITOLAK]);

        $this->actingAs($this->mahasiswa())
            ->post(route('mahasiswa.jadwal.store'), $this->isian())
            ->assertSessionHasNoErrors();
    }

    public function test_memperbarui_jadwal_pending_sendiri_tidak_bentrok_dengan_dirinya(): void
    {
        $user = $this->mahasiswa();

        $this->actingAs($user)->post(route('mahasiswa.jadwal.store'), $this->isian())->assertSessionHasNoErrors();
        $this->actingAs($user)->post(route('mahasiswa.jadwal.store'), $this->isian(['jam_ujian' => '10:30']))->assertSessionHasNoErrors();

        $this->assertDatabaseCount('jadwal_pendadaran', 1);
        $this->assertSame('10:30', substr(JadwalPendadaran::first()->jam_ujian, 0, 5));
    }

    // ---------- persetujuan oleh admin ----------

    public function test_admin_tidak_bisa_menyetujui_jika_dokumen_belum_lengkap(): void
    {
        $mhs = $this->mahasiswa();
        $this->actingAs($mhs)->post(route('mahasiswa.jadwal.store'), $this->isian());

        // dokumen berubah jadi ditolak setelah jadwal diajukan
        $mhs->dokumen()->first()->update(['status' => DokumenMahasiswa::DITOLAK]);

        $jadwal = JadwalPendadaran::first();
        $this->actingAs($this->admin())
            ->patch(route('admin.jadwal.setuju', $jadwal))
            ->assertSessionHas('error');

        $this->assertSame(JadwalPendadaran::PENDING, $jadwal->fresh()->status);
    }

    public function test_admin_tidak_bisa_menyetujui_dua_pengajuan_yang_bentrok(): void
    {
        // dua pengajuan pending yang bentrok (jam 10:00 dan 10:30, sama-sama memakai Budi)
        $a = $this->jadwalLain(['status' => JadwalPendadaran::PENDING]);
        $b = $this->jadwalLain(['status' => JadwalPendadaran::PENDING, 'nim' => '21118888', 'jam_ujian' => '10:30']);

        $admin = $this->admin();

        $this->actingAs($admin)->patch(route('admin.jadwal.setuju', $a))->assertSessionHas('status');
        $this->actingAs($admin)->patch(route('admin.jadwal.setuju', $b))
            ->assertSessionHas('error', fn ($pesan) => str_contains($pesan, 'bentrok'));

        $this->assertSame(JadwalPendadaran::PENDING, $b->fresh()->status);
    }

    public function test_admin_bisa_menyetujui_jadwal_yang_valid(): void
    {
        $mhs = $this->mahasiswa();
        $this->actingAs($mhs)->post(route('mahasiswa.jadwal.store'), $this->isian());
        $this->actingAs($mhs)->post(route('mahasiswa.pembayaran.store'), ['bukti_bayar' => UploadedFile::fake()->create('bukti.pdf', 40, 'application/pdf')]);
        $jadwal = JadwalPendadaran::first();
        $admin = $this->admin();
        $this->actingAs($admin)->patch(route('admin.jadwal.setuju-bukti', $jadwal))->assertSessionHas('status');

        $this->actingAs($admin)
            ->patch(route('admin.jadwal.setuju', $jadwal))
            ->assertSessionHas('status');

        $this->assertSame(JadwalPendadaran::DISETUJUI, $jadwal->fresh()->status);
    }
}
