<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dokumen_mahasiswa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('persyaratan_id')->constrained('persyaratan')->cascadeOnDelete();

            $table->string('path');                  // lokasi berkas di storage privat
            $table->string('nama_asli');
            $table->string('mime', 100);
            $table->unsignedInteger('ukuran');       // dalam byte

            $table->string('status', 12)->default('menunggu')->index(); // menunggu | disetujui | ditolak
            $table->string('catatan')->nullable();   // alasan penolakan
            $table->foreignId('diverifikasi_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('diverifikasi_at')->nullable();
            $table->timestamp('diunggah_at')->useCurrent();
            $table->timestamps();

            $table->unique(['user_id', 'persyaratan_id']); // satu berkas per jenis dokumen
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dokumen_mahasiswa');
    }
};