<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jadwal_pendadaran', function (Blueprint $table) {
            $table->id();
            // terisi saat mahasiswa mengajukan sendiri lewat akunnya (fitur role mahasiswa)
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('nim', 30)->index();
            $table->string('nama', 100);
            $table->string('judul');
            $table->string('pembimbing_1', 120);
            $table->string('pembimbing_2', 120)->nullable();
            $table->string('penguji', 120);
            $table->date('tanggal_ujian')->index();
            $table->time('jam_ujian');
            $table->string('status', 15)->default('pending')->index(); // pending | disetujui | ditolak
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jadwal_pendadaran');
    }
};