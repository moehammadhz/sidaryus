<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Bukti bayar diperiksa terpisah dari jadwal. Bukti yang ditolak tidak membuat jadwal ditolak:
 * mahasiswa cukup mengunggah ulang buktinya. Setiap penolakan disimpan sebagai riwayat.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jadwal_pendadaran', function (Blueprint $table) {
            $table->string('bukti_bayar_status', 15)->nullable()->after('bukti_bayar_mime'); // menunggu | ditolak | disetujui
            $table->string('bukti_bayar_alasan', 500)->nullable()->after('bukti_bayar_status');
        });

        DB::table('jadwal_pendadaran')->whereNotNull('bukti_bayar_path')->where('status', 'disetujui')->update(['bukti_bayar_status' => 'disetujui']);
        DB::table('jadwal_pendadaran')->whereNotNull('bukti_bayar_path')->whereNull('bukti_bayar_status')->update(['bukti_bayar_status' => 'menunggu']);

        Schema::create('riwayat_bukti_bayar', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jadwal_id')->constrained('jadwal_pendadaran')->cascadeOnDelete();
            $table->string('path');
            $table->string('nama');
            $table->string('mime', 50)->nullable();
            $table->string('alasan', 500);
            $table->foreignId('ditolak_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('ditolak_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('riwayat_bukti_bayar');

        Schema::table('jadwal_pendadaran', function (Blueprint $table) {
            $table->dropColumn(['bukti_bayar_status', 'bukti_bayar_alasan']);
        });
    }
};
