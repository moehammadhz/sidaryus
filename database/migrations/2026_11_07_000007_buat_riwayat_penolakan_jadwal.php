<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Satu pengajuan = satu baris. Jadwal yang ditolak diperbaiki di baris yang sama,
 * dan setiap penolakan disimpan di riwayat_penolakan_jadwal (data jadwal saat itu + alasan).
 * Baris baru hanya untuk ujian ulang setelah Tidak Lulus.
 *
 * Data lama: pengajuan yang ditolak lalu diajukan ulang (dua baris) digabung menjadi satu.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('riwayat_penolakan_jadwal', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jadwal_id')->constrained('jadwal_pendadaran')->cascadeOnDelete();
            // salinan data jadwal saat ditolak, supaya terlihat apa yang diperbaiki
            $table->string('judul');
            $table->string('pembimbing_1', 120);
            $table->string('pembimbing_2', 120)->nullable();
            $table->string('penguji', 120);
            $table->date('tanggal_ujian');
            $table->time('jam_ujian');
            $table->string('alasan', 500)->nullable();
            $table->foreignId('ditolak_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('ditolak_at');
            $table->timestamps();
        });

        $this->gabungkanPengajuanGanda();
    }

    public function down(): void
    {
        Schema::dropIfExists('riwayat_penolakan_jadwal');
    }

    /** Baris "ditolak" yang punya pengajuan sesudahnya dipindah ke riwayat milik pengajuan sesudahnya, lalu dihapus. */
    private function gabungkanPengajuanGanda(): void
    {
        $perMahasiswa = DB::table('jadwal_pendadaran')->whereNotNull('user_id')->orderBy('id')->get()->groupBy('user_id');

        foreach ($perMahasiswa as $baris) {
            $baris = $baris->values();
            $tujuan = null;

            // dari yang terbaru ke yang terlama: setiap baris ditolak digabung ke baris sesudahnya
            for ($i = $baris->count() - 1; $i >= 0; $i--) {
                $b = $baris[$i];
                if ($b->status === 'ditolak' && $tujuan !== null) {
                    DB::table('riwayat_penolakan_jadwal')->insert([
                        'jadwal_id'     => $tujuan->id,
                        'judul'         => $b->judul,
                        'pembimbing_1'  => $b->pembimbing_1,
                        'pembimbing_2'  => $b->pembimbing_2,
                        'penguji'       => $b->penguji,
                        'tanggal_ujian' => $b->tanggal_ujian,
                        'jam_ujian'     => $b->jam_ujian,
                        'alasan'        => $b->alasan_tolak,
                        'ditolak_oleh'  => $b->diverifikasi_oleh,
                        'ditolak_at'    => $b->diverifikasi_at ?? $b->updated_at ?? now(),
                        'created_at'    => now(),
                        'updated_at'    => now(),
                    ]);
                    DB::table('riwayat_bukti_bayar')->where('jadwal_id', $b->id)->update(['jadwal_id' => $tujuan->id]);
                    // berkas bukti bayar tidak dihapus: baris tujuan biasanya memakai berkas yang sama
                    DB::table('jadwal_pendadaran')->where('id', $b->id)->delete();
                    continue;
                }
                $tujuan = $b;
            }
        }
    }
};
