<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Registrasi mahasiswa memakai kode OTP yang dikirim ke WhatsApp. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('no_wa', 20)->nullable()->unique()->after('email');
            $table->timestamp('wa_terverifikasi_at')->nullable()->after('no_wa');
        });

        // Data pendaftaran ditahan di sini sampai kode OTP benar; baru setelah itu akun dibuat
        Schema::create('otp_registrasi', function (Blueprint $table) {
            $table->id();
            $table->string('no_wa', 20)->index();
            $table->text('data');                  // isian formulir (terenkripsi)
            $table->string('kode_hash');           // kode OTP tidak disimpan apa adanya
            $table->dateTime('berlaku_sampai');
            $table->unsignedTinyInteger('percobaan')->default(0);
            $table->dateTime('dikirim_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otp_registrasi');

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['no_wa']);
            $table->dropColumn(['no_wa', 'wa_terverifikasi_at']);
        });
    }
};
