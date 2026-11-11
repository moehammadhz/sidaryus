<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Periode Yudisium sekarang dibuka/ditutup oleh admin atau pengajaran, dan mahasiswa yang
 * sudah lulus pendadaran mendaftar sendiri. users.periode_id tetap dipakai sebagai periode
 * tempat mahasiswa terdaftar.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('periode', function (Blueprint $table) {
            $table->boolean('dibuka')->default(false)->after('nama');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('periode_daftar_at')->nullable()->after('periode_id');
        });

        // Dulu periode dipilih sebelum ujian. Pendaftaran lama itu tidak berlaku lagi
        // karena sekarang hanya mahasiswa yang sudah lulus yang boleh mendaftar.
        DB::table('users')->whereNotNull('periode_id')->update(['periode_id' => null]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('periode_daftar_at');
        });

        Schema::table('periode', function (Blueprint $table) {
            $table->dropColumn('dibuka');
        });
    }
};
