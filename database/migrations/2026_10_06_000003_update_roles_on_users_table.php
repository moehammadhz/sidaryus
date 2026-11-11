<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Ubah enum menjadi string dulu, supaya nilai baru bisa disimpan.
        //    Nilai yang valid dijaga oleh App\Enums\Role.
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('mahasiswa')->change();
        });

        // 2. Baru pindahkan role lama ke role baru
        DB::table('users')->where('role', 'prodi')->update(['role' => 'pengajaran']);
        DB::table('users')->where('role', 'dekan')->update(['role' => 'bsikd']);
    }

    public function down(): void
    {
        // Kembalikan nilai lama dulu, baru ubah kolom kembali ke enum
        DB::table('users')->where('role', 'pengajaran')->update(['role' => 'prodi']);
        DB::table('users')->where('role', 'bsikd')->update(['role' => 'dekan']);

        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['mahasiswa', 'admin', 'prodi', 'dekan'])->default('mahasiswa')->change();
        });
    }
};