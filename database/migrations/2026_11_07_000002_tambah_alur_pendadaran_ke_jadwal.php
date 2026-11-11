<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Alur baru pada jadwal pendadaran:
 * bukti bayar (diunggah mahasiswa) -> verifikasi -> undangan PDF (diunggah petugas) -> nilai & hasil.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jadwal_pendadaran', function (Blueprint $table) {
            // Bukti bayar. Nullable karena jadwal lama dibuat sebelum aturan ini ada.
            $table->string('bukti_bayar_path')->nullable()->after('status');
            $table->string('bukti_bayar_nama')->nullable()->after('bukti_bayar_path');
            $table->string('bukti_bayar_mime', 50)->nullable()->after('bukti_bayar_nama');

            // Verifikasi jadwal + bukti bayar
            $table->string('alasan_tolak', 500)->nullable()->after('bukti_bayar_mime');
            $table->foreignId('diverifikasi_oleh')->nullable()->after('alasan_tolak')->constrained('users')->nullOnDelete();
            $table->timestamp('diverifikasi_at')->nullable()->after('diverifikasi_oleh');

            // Undangan jadwal ujian (PDF dari pengajaran)
            $table->string('undangan_path')->nullable()->after('diverifikasi_at');
            $table->string('undangan_nama')->nullable()->after('undangan_path');
            $table->timestamp('undangan_at')->nullable()->after('undangan_nama');

            // Nilai dari dosen (diinput petugas) dan hasil
            $table->decimal('nilai_pembimbing_1', 5, 2)->nullable()->after('undangan_at');
            $table->decimal('nilai_pembimbing_2', 5, 2)->nullable()->after('nilai_pembimbing_1');
            $table->decimal('nilai_penguji', 5, 2)->nullable()->after('nilai_pembimbing_2');
            $table->decimal('nilai_akhir', 5, 2)->nullable()->after('nilai_penguji');
            $table->string('hasil', 15)->nullable()->index()->after('nilai_akhir'); // lulus | tidak_lulus
            $table->foreignId('hasil_oleh')->nullable()->after('hasil')->constrained('users')->nullOnDelete();
            $table->timestamp('hasil_at')->nullable()->after('hasil_oleh');
        });
    }

    public function down(): void
    {
        Schema::table('jadwal_pendadaran', function (Blueprint $table) {
            $table->dropConstrainedForeignId('hasil_oleh');
            $table->dropConstrainedForeignId('diverifikasi_oleh');
            $table->dropIndex(['hasil']);
            $table->dropColumn([
                'bukti_bayar_path', 'bukti_bayar_nama', 'bukti_bayar_mime', 'alasan_tolak', 'diverifikasi_at',
                'undangan_path', 'undangan_nama', 'undangan_at',
                'nilai_pembimbing_1', 'nilai_pembimbing_2', 'nilai_penguji', 'nilai_akhir', 'hasil', 'hasil_at',
            ]);
        });
    }
};
