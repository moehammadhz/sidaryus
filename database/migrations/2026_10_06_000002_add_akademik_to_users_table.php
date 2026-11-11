<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // nullable karena akun admin/prodi tidak punya data akademik
            $table->string('program_studi', 50)->nullable()->after('role');
            $table->string('kelas', 5)->nullable()->after('program_studi');
            $table->unsignedSmallInteger('angkatan')->nullable()->after('kelas');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['program_studi', 'kelas', 'angkatan']);
        });
    }
};