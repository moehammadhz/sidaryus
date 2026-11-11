<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\DokumenMahasiswa;
use App\Models\Dosen;
use App\Models\JadwalPendadaran as J;
use App\Models\Periode;
use App\Models\Persyaratan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Alur revisi: bukti bayar → verifikasi → undangan → nilai → periode yudisium, plus BAAK. */
class AlurPendadaranTest extends TestCase
{
    use RefreshDatabase;

    private const P1 = 'Jemmy Edwin Bororing, S.Kom., M.Eng.';
    private const P2 = 'Yumarlin MZ, S.Kom., M.Pd., M.Kom.';
    private const PG = 'Eri Haryanto, S.Kom., M.Kom.';

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-02 08:00:00'); // Senin
        Storage::fake('local');

        foreach ([self::P1, self::P2, self::PG] as $n) {
            Dosen::create(['nama' => $n, 'program_studi' => 'Informatika']);
        }
        Persyaratan::query()->delete();
        Persyaratan::create(['kode' => 'KTM', 'nama' => 'Kartu Tanda Mahasiswa', 'urutan' => 1]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /* ---------------- pembantu ---------------- */

    private function petugas(Role $role = Role::Admin): User
    {
        return User::create(['name' => $role->label(), 'username' => $role->value, 'password' => 'password123', 'role' => $role->value]);
    }

    private function mahasiswa(string $nim = '21110003', bool $lengkap = true): User
    {
        $m = User::create([
            'name' => "Mhs {$nim}", 'username' => $nim, 'password' => 'password123', 'role' => Role::Mahasiswa->value,
            'program_studi' => 'Informatika', 'kelas' => 'A', 'angkatan' => 2021,
        ]);
        if ($lengkap) {
            DokumenMahasiswa::create([
                'user_id' => $m->id, 'persyaratan_id' => Persyaratan::first()->id, 'path' => "dokumen/{$m->id}/ktm.pdf",
                'nama_asli' => 'ktm.pdf', 'mime' => 'application/pdf', 'ukuran' => 10, 'status' => DokumenMahasiswa::DISETUJUI, 'diunggah_at' => now(),
            ]);
        }

        return $m;
    }

    private function isian(array $ubah = []): array
    {
        return $ubah + [
            'judul' => 'Sistem Informasi Pendadaran', 'pembimbing_1' => self::P1, 'pembimbing_2' => self::P2, 'penguji' => self::PG,
            'tanggal_ujian' => '2026-11-04', 'jam_ujian' => '12:30',
        ];
    }

    /** Jadwal disetujui untuk tanggal tertentu, langsung di database */
    private function jadwalDisetujui(User $m, string $tanggal = '2026-10-28', array $ubah = []): J
    {
        return J::create($ubah + [
            'user_id' => $m->id, 'nim' => $m->username, 'nama' => $m->name, 'judul' => 'Judul',
            'pembimbing_1' => self::P1, 'pembimbing_2' => self::P2, 'penguji' => self::PG,
            'tanggal_ujian' => $tanggal, 'jam_ujian' => '09:00', 'status' => J::DISETUJUI,
            'bukti_bayar_path' => 'bukti-bayar/x.pdf', 'bukti_bayar_nama' => 'x.pdf', 'bukti_bayar_mime' => 'application/pdf',
        ]);
    }

    private function nilai(J $j, User $oleh, array $n)
    {
        return $this->actingAs($oleh)->put(route('admin.jadwal.nilai', $j), $n);
    }

    /* ---------------- bukti bayar & verifikasi ---------------- */

    private function bayar(User $m, ?UploadedFile $file = null)
    {
        return $this->actingAs($m)->post(route('mahasiswa.pembayaran.store'), ['bukti_bayar' => $file ?? UploadedFile::fake()->create('bukti.pdf', 40, 'application/pdf')]);
    }

    private function setujuiBukti(J $j, ?User $oleh = null)
    {
        return $this->actingAs($oleh ?? $this->petugas())->patch(route('admin.jadwal.setuju-bukti', $j));
    }

    public function test_jadwal_bisa_diajukan_tanpa_bukti_bayar_lalu_bukti_di_menu_terpisah(): void
    {
        $m = $this->mahasiswa();

        // belum ada jadwal: halaman pembayaran mengarahkan ke pengajuan jadwal
        $this->actingAs($m)->get(route('mahasiswa.pembayaran.index'))->assertOk()->assertSee('Ajukan jadwal ujian terlebih dahulu');
        $this->bayar($m)->assertSessionHas('error');

        $this->actingAs($m)->post(route('mahasiswa.jadwal.store'), $this->isian())
            ->assertSessionHasNoErrors()->assertSessionHas('success', fn ($p) => str_contains($p, 'Upload Bukti Pembayaran'));
        $j = J::first();
        $this->assertNull($j->bukti_bayar_path);
        $this->assertSame('menunggu_bayar', $j->tahap());

        // format dibatasi
        $this->bayar($m, UploadedFile::fake()->create('a.gif', 10, 'image/gif'))->assertSessionHasErrors('bukti_bayar');
        $this->bayar($m, UploadedFile::fake()->create('struk.jpg', 10, 'image/jpeg'))->assertSessionHasNoErrors();

        $j->refresh();
        $this->assertSame('struk.jpg', $j->bukti_bayar_nama);
        $this->assertSame('cek_bayar', $j->tahap());
        Storage::disk('local')->assertExists($j->bukti_bayar_path);
    }

    public function test_bukti_bisa_diganti_saat_pending_dan_terkunci_setelah_disetujui(): void
    {
        $m = $this->mahasiswa();
        $this->actingAs($m)->post(route('mahasiswa.jadwal.store'), $this->isian());
        $this->bayar($m);
        $lama = J::first()->bukti_bayar_path;

        $this->bayar($m, UploadedFile::fake()->create('baru.png', 10, 'image/png'))->assertSessionHas('success');
        Storage::disk('local')->assertMissing($lama);

        // memperbarui data jadwal tidak menghapus bukti bayar
        $this->actingAs($m)->post(route('mahasiswa.jadwal.store'), $this->isian(['jam_ujian' => '13:00']))->assertSessionHasNoErrors();
        $this->assertSame('baru.png', J::first()->bukti_bayar_nama);

        $admin = $this->petugas();
        $this->setujuiBukti(J::first(), $admin)->assertSessionHas('status');
        $this->bayar($m)->assertSessionHas('error', 'Bukti pembayaran sudah disetujui sehingga tidak bisa diganti.');
        $this->actingAs($admin)->patch(route('admin.jadwal.setuju', J::first()))->assertSessionHas('status');
        $this->bayar($m)->assertSessionHas('error', 'Bukti pembayaran sudah disetujui sehingga tidak bisa diganti.');
        $this->actingAs($m)->get(route('mahasiswa.pembayaran.index'))->assertSee('Sudah diverifikasi')->assertDontSee('Unggah bukti');
    }

    public function test_jadwal_ditolak_diperbaiki_di_baris_yang_sama_dengan_riwayat(): void
    {
        $m = $this->mahasiswa();
        $this->actingAs($m)->post(route('mahasiswa.jadwal.store'), $this->isian());
        $this->bayar($m);
        $staf = $this->petugas(Role::Pengajaran);
        $j = J::first();
        $this->actingAs($staf)->patch(route('admin.jadwal.tolak', $j), ['alasan_tolak' => 'Tanggal bentrok rapat.']);

        $this->actingAs($m)->get(route('mahasiswa.jadwal.index'))->assertSee('Kirim Perbaikan');
        $this->actingAs($m)->post(route('mahasiswa.jadwal.store'), $this->isian(['tanggal_ujian' => '2026-11-05']))->assertSessionHasNoErrors();

        // tetap satu baris, kembali pending, bukti bayar tetap terpakai
        $this->assertDatabaseCount('jadwal_pendadaran', 1);
        $j->refresh();
        $this->assertSame(J::PENDING, $j->status);
        $this->assertNull($j->alasan_tolak);
        $this->assertNull($j->diverifikasi_oleh);
        $this->assertSame('2026-11-05', $j->tanggal_ujian->toDateString());
        $this->assertNotNull($j->bukti_bayar_path);

        // penolakan tercatat sebagai riwayat dengan data jadwal saat ditolak
        $this->assertDatabaseCount('riwayat_penolakan_jadwal', 1);
        $r = $j->riwayatPenolakan()->first();
        $this->assertSame('Tanggal bentrok rapat.', $r->alasan);
        $this->assertSame('2026-11-04', $r->tanggal_ujian->toDateString());
        $this->assertSame($staf->id, $r->ditolak_oleh);

        $this->actingAs($staf)->get(route('admin.jadwal.show', $j))->assertSee('Riwayat penolakan jadwal')->assertSee('Tanggal bentrok rapat.');
        $this->actingAs($m)->get(route('mahasiswa.jadwal.index'))->assertSee('Riwayat penolakan jadwal')->assertSee('Tanggal bentrok rapat.');

        // ditolak lagi lalu diperbaiki lagi: tetap satu baris, riwayat bertambah
        $this->actingAs($staf)->patch(route('admin.jadwal.tolak', $j), ['alasan_tolak' => 'Penguji berhalangan.']);
        $this->actingAs($m)->post(route('mahasiswa.jadwal.store'), $this->isian(['tanggal_ujian' => '2026-11-06']))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('jadwal_pendadaran', 1);
        $this->assertDatabaseCount('riwayat_penolakan_jadwal', 2);
        $this->assertSame('Penguji berhalangan.', $j->riwayatPenolakan()->first()->alasan);
    }

    public function test_migrasi_menggabungkan_pengajuan_ganda_lama(): void
    {
        $m = $this->mahasiswa();
        $admin = $this->petugas();
        $lama = $this->jadwalDisetujui($m, '2026-11-04', ['status' => J::DITOLAK, 'alasan_tolak' => 'Salah tanggal.', 'diverifikasi_oleh' => $admin->id, 'diverifikasi_at' => now()]);
        \App\Models\RiwayatBuktiBayar::create(['jadwal_id' => $lama->id, 'path' => 'bukti-bayar/lama.pdf', 'nama' => 'lama.pdf', 'mime' => 'application/pdf', 'alasan' => 'Buram', 'ditolak_at' => now()]);
        $baru = $this->jadwalDisetujui($m, '2026-11-05');
        $lain = $this->jadwalDisetujui($this->mahasiswa('21110009'), '2026-11-06', ['status' => J::DITOLAK]);

        $migrasi = require database_path('migrations/2026_11_07_000007_buat_riwayat_penolakan_jadwal.php');
        $ref = new \ReflectionMethod($migrasi, 'gabungkanPengajuanGanda');
        $ref->setAccessible(true);
        $ref->invoke($migrasi);

        $this->assertDatabaseMissing('jadwal_pendadaran', ['id' => $lama->id]);
        $this->assertDatabaseHas('jadwal_pendadaran', ['id' => $baru->id]);
        $this->assertDatabaseHas('jadwal_pendadaran', ['id' => $lain->id]);   // satu-satunya pengajuan tidak disentuh
        $this->assertDatabaseHas('riwayat_penolakan_jadwal', ['jadwal_id' => $baru->id, 'alasan' => 'Salah tanggal.', 'ditolak_oleh' => $admin->id]);
        $this->assertDatabaseHas('riwayat_bukti_bayar', ['jadwal_id' => $baru->id, 'nama' => 'lama.pdf']);
    }

    public function test_tolak_bukti_bayar_tidak_menolak_jadwal_dan_riwayat_tersimpan(): void
    {
        $m = $this->mahasiswa();
        $this->actingAs($m)->post(route('mahasiswa.jadwal.store'), $this->isian());
        $this->bayar($m, UploadedFile::fake()->create('buram.jpg', 10, 'image/jpeg'));
        $j = J::first();
        $fileLama = $j->bukti_bayar_path;
        $staf = $this->petugas(Role::Pengajaran);

        // alasan wajib
        $this->actingAs($staf)->patch(route('admin.jadwal.tolak-bukti', $j), ['alasan_bukti' => ''])->assertSessionHasErrorsIn('tolakBukti', 'alasan_bukti');

        $this->actingAs($staf)->patch(route('admin.jadwal.tolak-bukti', $j), ['alasan_bukti' => 'Gambar buram.'])->assertSessionHas('status');
        $j->refresh();
        $this->assertSame(J::PENDING, $j->status);             // jadwal TIDAK ditolak
        $this->assertTrue($j->buktiDitolak());
        $this->assertSame('bayar_ditolak', $j->tahap());
        $this->assertDatabaseCount('riwayat_bukti_bayar', 1);

        // tidak bisa ditolak dua kali, dan belum bisa disetujui
        $this->actingAs($staf)->patch(route('admin.jadwal.tolak-bukti', $j), ['alasan_bukti' => 'lagi'])->assertSessionHas('error');
        $this->actingAs($staf)->patch(route('admin.jadwal.setuju', $j))->assertSessionHas('error', fn ($e) => str_contains($e, 'ditolak'));

        // mahasiswa melihat alasan di menu pembayaran dan jadwal, lalu unggah ulang tanpa mengajukan jadwal lagi
        $this->actingAs($m)->get(route('mahasiswa.pembayaran.index'))->assertSee('Gambar buram.')->assertSee('Riwayat penolakan');
        $this->actingAs($m)->get(route('mahasiswa.jadwal.index'))->assertSee('Bukti pembayaran ditolak');
        $this->bayar($m, UploadedFile::fake()->create('jelas.pdf', 10, 'application/pdf'))->assertSessionHas('success');

        $j->refresh();
        $this->assertSame(J::BAYAR_MENUNGGU, $j->bukti_bayar_status);
        $this->assertDatabaseCount('jadwal_pendadaran', 1);
        Storage::disk('local')->assertExists($fileLama);        // berkas yang ditolak tidak dihapus

        // riwayat tampil di detail admin dan berkas lamanya bisa dibuka
        $riwayat = \App\Models\RiwayatBuktiBayar::first();
        $this->actingAs($staf)->get(route('admin.jadwal.show', $j))->assertSee('Riwayat penolakan bukti bayar')->assertSee('Gambar buram.')->assertSee('buram.jpg');
        $this->actingAs($staf)->get(route('admin.jadwal.riwayat-bukti', $riwayat))->assertOk();
        $this->actingAs($m)->get(route('admin.jadwal.riwayat-bukti', $riwayat))->assertForbidden();

        // bukti baru: setujui bukti dulu, baru jadwal bisa disetujui
        $this->actingAs($staf)->patch(route('admin.jadwal.setuju', $j))->assertSessionHas('error', fn ($e) => str_contains($e, 'setujui bukti pembayaran'));
        $this->setujuiBukti($j, $staf)->assertSessionHas('status');
        $this->assertSame(J::BAYAR_DISETUJUI, $j->fresh()->bukti_bayar_status);
        $this->actingAs($staf)->patch(route('admin.jadwal.setuju', $j))->assertSessionHas('status');
        $this->assertDatabaseCount('riwayat_bukti_bayar', 1);   // riwayat tetap ada
    }

    public function test_riwayat_ujian_memakai_judul_hasil_ujian(): void
    {
        $m = $this->mahasiswa();
        $j = $this->jadwalDisetujui($m);
        $this->nilai($j, $this->petugas(), ['nilai_pembimbing_1' => 40, 'nilai_pembimbing_2' => 40, 'nilai_penguji' => 40]);
        $this->actingAs($m)->post(route('mahasiswa.jadwal.store'), $this->isian());

        $this->assertDatabaseCount('jadwal_pendadaran', 2);   // baris baru hanya untuk ujian ulang
        $this->actingAs($m)->get(route('mahasiswa.jadwal.index'))->assertSee('Riwayat ujian')->assertSee('Hasil ujian')->assertSee('Tidak Lulus');
    }

    public function test_ujian_ulang_butuh_bukti_bayar_baru(): void
    {
        $m = $this->mahasiswa();
        $j = $this->jadwalDisetujui($m);
        $this->nilai($j, $this->petugas(), ['nilai_pembimbing_1' => 40, 'nilai_pembimbing_2' => 40, 'nilai_penguji' => 40]);

        $this->bayar($m)->assertSessionHas('error', fn ($e) => str_contains($e, 'ujian ulang'));

        $this->actingAs($m)->post(route('mahasiswa.jadwal.store'), $this->isian())->assertSessionHasNoErrors();
        $this->assertNull(J::latest('id')->first()->bukti_bayar_path);
    }

    public function test_undangan_menunggu_bukti_bayar(): void
    {
        $m = $this->mahasiswa();
        $this->actingAs($m)->post(route('mahasiswa.jadwal.store'), $this->isian());

        $this->actingAs($m)->get(route('mahasiswa.undangan.index'))->assertSee('Bukti pembayaran belum diunggah');
        $this->actingAs($m)->get(route('mahasiswa.dashboard'))->assertSee('Unggah bukti pembayaran');
    }

    public function test_tolak_wajib_alasan_dan_alasan_tampil_ke_mahasiswa(): void
    {
        $m = $this->mahasiswa();
        $this->actingAs($m)->post(route('mahasiswa.jadwal.store'), $this->isian());
        $j = J::first();
        $admin = $this->petugas();

        $this->actingAs($admin)->patch(route('admin.jadwal.tolak', $j), ['alasan_tolak' => ''])->assertSessionHasErrorsIn('tolak', 'alasan_tolak');
        $this->assertSame(J::PENDING, $j->fresh()->status);

        $this->actingAs($admin)->patch(route('admin.jadwal.tolak', $j), ['alasan_tolak' => 'Bukti bayar tidak terbaca.']);
        $this->assertSame(J::DITOLAK, $j->fresh()->status);

        $this->actingAs($m)->get(route('mahasiswa.jadwal.index'))->assertSee('Bukti bayar tidak terbaca.');
    }

    public function test_jadwal_tanpa_bukti_bayar_tidak_bisa_disetujui(): void
    {
        $m = $this->mahasiswa();
        $j = $this->jadwalDisetujui($m, '2026-11-04', ['status' => J::PENDING, 'bukti_bayar_path' => null]);

        $this->actingAs($this->petugas())->patch(route('admin.jadwal.setuju', $j))
            ->assertSessionHas('error', fn ($e) => str_contains($e, 'bukti pembayaran'));
        $this->actingAs($this->petugas(Role::Pengajaran))->get(route('admin.jadwal.show', $j))
            ->assertSee('Menunggu bukti bayar')->assertDontSee('Setujui jadwal &amp; bukti bayar', false);
        $this->assertSame(J::PENDING, $j->fresh()->status);
    }

    public function test_pengajaran_bisa_menyetujui_dan_tercatat(): void
    {
        $m = $this->mahasiswa();
        $this->actingAs($m)->post(route('mahasiswa.jadwal.store'), $this->isian());
        $this->bayar($m);
        $j = J::first();
        $staf = $this->petugas(Role::Pengajaran);

        $this->setujuiBukti($j, $staf);
        $this->actingAs($staf)->patch(route('admin.jadwal.setuju', $j))->assertSessionHas('status');
        $this->assertSame(J::DISETUJUI, $j->fresh()->status);
        $this->assertSame($staf->id, $j->fresh()->diverifikasi_oleh);
    }

    /* ---------------- undangan ---------------- */

    public function test_undangan_hanya_untuk_jadwal_disetujui_dan_harus_pdf(): void
    {
        $m = $this->mahasiswa();
        $staf = $this->petugas(Role::Pengajaran);
        $pending = $this->jadwalDisetujui($m, '2026-11-04', ['status' => J::PENDING]);

        $this->actingAs($staf)->post(route('admin.jadwal.undangan.store', $pending), ['undangan' => UploadedFile::fake()->create('u.pdf', 20, 'application/pdf')])
            ->assertSessionHas('error');

        $j = $this->jadwalDisetujui($this->mahasiswa('21110004'), '2026-11-04');
        $this->actingAs($staf)->post(route('admin.jadwal.undangan.store', $j), ['undangan' => UploadedFile::fake()->create('u.docx', 20, 'application/msword')])
            ->assertSessionHasErrorsIn('undangan', 'undangan');
        $this->actingAs($staf)->post(route('admin.jadwal.undangan.store', $j), ['undangan' => UploadedFile::fake()->create('undangan.pdf', 20, 'application/pdf')])
            ->assertSessionHas('status');

        $this->assertNotNull($j->fresh()->undangan_path);
    }

    public function test_mahasiswa_melihat_dan_mengunduh_undangannya_sendiri_saja(): void
    {
        $m = $this->mahasiswa();
        $lain = $this->mahasiswa('21110004');
        $j = $this->jadwalDisetujui($m, '2026-11-04');

        $this->actingAs($m)->get(route('mahasiswa.undangan.index'))->assertOk()->assertSee('sedang disiapkan');

        $this->actingAs($this->petugas())->post(route('admin.jadwal.undangan.store', $j), ['undangan' => UploadedFile::fake()->create('undangan.pdf', 20, 'application/pdf')]);

        $this->actingAs($m)->get(route('mahasiswa.undangan.index'))->assertOk()->assertSee('Unduh PDF');
        $this->actingAs($m)->get(route('mahasiswa.undangan.unduh', ['jadwal' => $j, 'unduh' => 1]))
            ->assertOk()->assertHeader('content-disposition', 'attachment; filename=Undangan-Pendadaran-21110003-2026-11-04.pdf');
        $this->actingAs($lain)->get(route('mahasiswa.undangan.unduh', $j))->assertNotFound();
    }

    /* ---------------- nilai ---------------- */

    public function test_nilai_belum_bisa_diinput_sebelum_ujian(): void
    {
        $j = $this->jadwalDisetujui($this->mahasiswa(), '2026-11-04');

        $this->nilai($j, $this->petugas(), ['nilai_pembimbing_1' => 80, 'nilai_pembimbing_2' => 80, 'nilai_penguji' => 80])
            ->assertSessionHas('error');
        $this->assertNull($j->fresh()->hasil);
    }

    public function test_rata_rata_tiga_nilai_dan_huruf(): void
    {
        $j = $this->jadwalDisetujui($this->mahasiswa());

        $this->nilai($j, $this->petugas(Role::Pengajaran), ['nilai_pembimbing_1' => '85', 'nilai_pembimbing_2' => '82,5', 'nilai_penguji' => '80'])
            ->assertSessionHasNoErrors();

        $j->refresh();
        $this->assertEquals(82.5, (float) $j->nilai_akhir);
        $this->assertSame('A', $j->hurufAkhir());
        $this->assertSame(J::LULUS, $j->hasil);
    }

    /** Jadwal lama (sebelum pembimbing pendamping wajib) tetap bisa dinilai */
    public function test_data_lama_tanpa_pembimbing_pendamping_dihitung_dari_dua_nilai(): void
    {
        $j = $this->jadwalDisetujui($this->mahasiswa(), '2026-10-28', ['pembimbing_2' => null]);

        $this->nilai($j, $this->petugas(), ['nilai_pembimbing_1' => 70, 'nilai_penguji' => 49])->assertSessionHasNoErrors();

        $this->assertEquals(59.5, (float) $j->fresh()->nilai_akhir);
        $this->assertSame(J::TIDAK_LULUS, $j->fresh()->hasil);   // di bawah 60
    }

    public function test_nilai_tidak_valid_ditolak(): void
    {
        $j = $this->jadwalDisetujui($this->mahasiswa());
        $admin = $this->petugas();

        $this->nilai($j, $admin, ['nilai_pembimbing_1' => 101, 'nilai_pembimbing_2' => 'abc', 'nilai_penguji' => ''])
            ->assertSessionHasErrorsIn('nilai', ['nilai_pembimbing_1', 'nilai_pembimbing_2', 'nilai_penguji']);
        $this->assertNull($j->fresh()->hasil);
    }

    public function test_tidak_lulus_boleh_ajukan_ulang_lulus_tidak(): void
    {
        $m = $this->mahasiswa();
        $j = $this->jadwalDisetujui($m);
        $this->nilai($j, $this->petugas(), ['nilai_pembimbing_1' => 50, 'nilai_pembimbing_2' => 55, 'nilai_penguji' => 50]);

        $this->actingAs($m)->get(route('mahasiswa.jadwal.index'))->assertSee('Ajukan Ujian Ulang');
        $this->actingAs($m)->post(route('mahasiswa.jadwal.store'), $this->isian())->assertSessionHasNoErrors();
        $this->assertDatabaseCount('jadwal_pendadaran', 2);

        $lulus = $this->mahasiswa('21110004');
        $j2 = $this->jadwalDisetujui($lulus, '2026-10-29', ['pembimbing_1' => self::P2, 'pembimbing_2' => null]);
        $this->nilai($j2, $this->petugas(Role::Pengajaran), ['nilai_pembimbing_1' => 80, 'nilai_penguji' => 80]);
        $this->actingAs($lulus)->post(route('mahasiswa.jadwal.store'), $this->isian(['jam_ujian' => '15:00']))
            ->assertSessionHas('error', 'Anda sudah lulus ujian pendadaran.');
    }

    /* ---------------- periode yudisium ---------------- */

    public function test_mahasiswa_belum_lulus_tidak_bisa_daftar_periode(): void
    {
        $m = $this->mahasiswa();
        $p = Periode::create(['nama' => 'Periode I', 'dibuka' => true]);

        $this->actingAs($m)->post(route('mahasiswa.periode.daftar'), ['periode_id' => $p->id])->assertSessionHas('error');
        $this->assertNull($m->fresh()->periode_id);
    }

    public function test_alur_daftar_periode_sampai_nilai_terkunci(): void
    {
        $m = $this->mahasiswa();
        $j = $this->jadwalDisetujui($m);
        $admin = $this->petugas();
        $this->nilai($j, $admin, ['nilai_pembimbing_1' => 80, 'nilai_pembimbing_2' => 80, 'nilai_penguji' => 80]);

        $tutup = Periode::create(['nama' => 'Periode Lama', 'dibuka' => false]);
        $buka = Periode::create(['nama' => 'Periode II Yudisium Desember 2026', 'dibuka' => true]);

        // dashboard menawarkan periode yang dibuka saja
        $this->actingAs($m)->get(route('mahasiswa.dashboard'))->assertSee('Periode II Yudisium Desember 2026')->assertDontSee('Periode Lama');

        $this->actingAs($m)->post(route('mahasiswa.periode.daftar'), ['periode_id' => $tutup->id])->assertSessionHas('error');
        $this->actingAs($m)->post(route('mahasiswa.periode.daftar'), ['periode_id' => $buka->id])->assertSessionHas('status');
        $this->assertSame($buka->id, $m->fresh()->periode_id);

        $this->actingAs($m)->get(route('mahasiswa.dashboard'))->assertSee('Anda terdaftar sejak');

        // nilai terkunci setelah terdaftar
        $this->nilai($j, $admin, ['nilai_pembimbing_1' => 90, 'nilai_pembimbing_2' => 90, 'nilai_penguji' => 90])->assertSessionHas('error');
        $this->assertEquals(80, (float) $j->fresh()->nilai_akhir);

        // muncul di detail periode, periode tidak bisa dihapus
        $this->actingAs($admin)->get(route('admin.periode.show', $buka))->assertOk()->assertSee($m->name);
        $this->actingAs($admin)->delete(route('admin.periode.destroy', $buka))->assertSessionHas('error');

        // mahasiswa tidak bisa pindah sendiri; rute batal tidak ada lagi
        $buka2 = Periode::create(['nama' => 'Periode III', 'dibuka' => true]);
        $this->actingAs($m->fresh())->post(route('mahasiswa.periode.daftar'), ['periode_id' => $buka2->id])
            ->assertSessionHas('error', fn ($e) => str_contains($e, 'Hubungi bagian pengajaran'));
        $this->assertSame($buka->id, $m->fresh()->periode_id);
        $this->actingAs($m->fresh())->get(route('mahasiswa.dashboard'))->assertDontSee('Batalkan pendaftaran');
        $this->actingAs($m->fresh())->delete('/mahasiswa/periode')->assertStatus(405);

        // pengajaran memindahkan, juga ke periode yang sudah ditutup
        $staf = $this->petugas(Role::Pengajaran);
        $this->actingAs($staf)->patch(route('admin.periode.status', $buka));
        $this->assertFalse($buka->fresh()->dibuka);
        $this->actingAs($staf)->put(route('admin.yudisium.periode', $m), ['periode_id' => $tutup->id])->assertSessionHas('status');
        $this->assertSame($tutup->id, $m->fresh()->periode_id);

        // admin melepas, nilai bisa diralat lagi
        $this->actingAs($admin)->delete(route('admin.yudisium.lepas', $m))->assertSessionHas('status');
        $this->assertNull($m->fresh()->periode_id);
        $this->nilai($j, $admin, ['nilai_pembimbing_1' => 90, 'nilai_pembimbing_2' => 90, 'nilai_penguji' => 90])->assertSessionHas('status');
    }

    public function test_data_yudisium_terisi_otomatis_dari_hasil_lulus(): void
    {
        $lulus = $this->mahasiswa();
        $gagal = $this->mahasiswa('21110004');
        $admin = $this->petugas();
        $this->nilai($this->jadwalDisetujui($lulus), $admin, ['nilai_pembimbing_1' => 75, 'nilai_pembimbing_2' => 75, 'nilai_penguji' => 75]);
        $this->nilai($this->jadwalDisetujui($gagal, '2026-10-29'), $admin, ['nilai_pembimbing_1' => 40, 'nilai_pembimbing_2' => 40, 'nilai_penguji' => 40]);

        $this->actingAs($admin)->get(route('admin.yudisium.index'))
            ->assertOk()->assertSee($lulus->name)->assertDontSee($gagal->name)->assertSee('Belum mendaftar');
    }

    /* ---------------- hak akses & BAAK ---------------- */

    public function test_baak_hanya_bisa_melihat_dan_mengunduh_pdf(): void
    {
        $m = $this->mahasiswa();
        $j = $this->jadwalDisetujui($m);
        $baak = $this->petugas(Role::Baak);

        $this->actingAs($baak)->get(route('dashboard'))->assertRedirect(route('baak.mahasiswa.index'));
        $this->actingAs($baak)->get(route('baak.mahasiswa.index'))->assertOk()->assertSee($m->name);
        $this->actingAs($baak)->get(route('baak.mahasiswa.show', $m))->assertOk()->assertSee('Kartu Tanda Mahasiswa');

        $pdf = $this->actingAs($baak)->get(route('baak.mahasiswa.pdf', $m));
        $pdf->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-1.4', $pdf->getContent());
        $this->assertStringContainsString('(' . $m->name . ')', $pdf->getContent());
        $this->assertStringEndsWith('%%EOF', $pdf->getContent());

        // tidak bisa mengubah apa pun
        $this->actingAs($baak)->get(route('admin.jadwal.index'))->assertForbidden();
        $this->actingAs($baak)->put(route('admin.jadwal.nilai', $j), [])->assertForbidden();
        $this->actingAs($baak)->patch(route('admin.dokumen.setuju', DokumenMahasiswa::first()))->assertForbidden();
        $this->actingAs($baak)->get(route('admin.yudisium.index'))->assertForbidden();

        // admin tidak melihat menu BAAK
        $admin = $this->petugas();
        $this->actingAs($admin)->get(route('baak.mahasiswa.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->assertDontSee('Kelengkapan Mahasiswa');
    }

    public function test_menu_yudisium_tampil_untuk_admin_dan_pengajaran(): void
    {
        $this->actingAs($this->petugas(Role::Pengajaran))->get(route('petugas.dashboard'))
            ->assertOk()->assertSee('Data Yudisium')->assertSee('Periode Yudisium')->assertDontSee('Data Petugas');

        $this->actingAs($this->petugas(Role::Baak))->get(route('baak.mahasiswa.index'))
            ->assertOk()->assertDontSee('Data Yudisium')->assertDontSee('Jadwal Pendadaran');
    }

    public function test_bentrok_tetap_dua_jam(): void
    {
        $this->jadwalDisetujui($this->mahasiswa('21110009'), '2026-11-04', ['jam_ujian' => '12:30']);
        $m = $this->mahasiswa();

        $this->actingAs($m)->post(route('mahasiswa.jadwal.store'), $this->isian(['jam_ujian' => '14:00']))->assertSessionHasErrors('jam_ujian');
        $this->actingAs($m)->post(route('mahasiswa.jadwal.store'), $this->isian(['jam_ujian' => '14:30']))->assertSessionHasNoErrors();
    }

    public function test_tombol_setujui_jadwal_muncul_setelah_bukti_disetujui(): void
    {
        $m = $this->mahasiswa();
        $this->actingAs($m)->post(route('mahasiswa.jadwal.store'), $this->isian());
        $j = J::first();
        $staf = $this->petugas(Role::Pengajaran);

        // belum ada bukti: tidak ada tombol apa pun untuk bukti, tombol setujui jadwal tidak muncul
        $this->actingAs($staf)->get(route('admin.jadwal.show', $j))->assertDontSee('Setujui bukti bayar')->assertDontSee('>Setujui jadwal<', false);
        $this->setujuiBukti($j, $staf)->assertSessionHas('error');

        // bukti diunggah: muncul setujui & tolak bukti, setujui jadwal belum muncul
        $this->bayar($m);
        $this->actingAs($staf)->get(route('admin.jadwal.show', $j))
            ->assertSee('Setujui bukti bayar')->assertSee('Tolak bukti bayar')->assertDontSee('>Setujui jadwal<', false)
            ->assertSee('setujui bukti pembayaran di langkah 1 terlebih dahulu');

        // ditolak: tombol setujui jadwal tetap tidak muncul, mahasiswa unggah ulang
        $this->actingAs($staf)->patch(route('admin.jadwal.tolak-bukti', $j), ['alasan_bukti' => 'Nominal kurang.']);
        $this->actingAs($staf)->get(route('admin.jadwal.show', $j))->assertDontSee('Setujui bukti bayar')->assertDontSee('>Setujui jadwal<', false);
        $this->setujuiBukti($j, $staf)->assertSessionHas('error');
        $this->assertSame(J::PENDING, $j->fresh()->status);
        $this->bayar($m)->assertSessionHas('success');

        // bukti baru disetujui: tombol setujui jadwal muncul, tombol bukti hilang
        $this->setujuiBukti($j, $staf)->assertRedirect(route('admin.jadwal.show', $j) . '#verifikasi');
        $this->assertSame('pending', $j->fresh()->tahap());
        $this->actingAs($staf)->get(route('admin.jadwal.show', $j))->assertSee('>Setujui jadwal<', false)->assertDontSee('Setujui bukti bayar');
        $this->setujuiBukti($j, $staf)->assertSessionHas('error', 'Bukti bayar ini sudah disetujui.');
    }

    public function test_baak_tidak_bisa_menyetujui_bukti(): void
    {
        $m = $this->mahasiswa();
        $this->actingAs($m)->post(route('mahasiswa.jadwal.store'), $this->isian());
        $this->bayar($m);

        $this->setujuiBukti(J::first(), $this->petugas(Role::Baak))->assertForbidden();
        $this->setujuiBukti(J::first(), $m)->assertForbidden();
    }
}
