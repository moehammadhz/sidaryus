<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('periode', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100)->unique(); // contoh: Periode II Yudisium April 2026
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('periode_id')->nullable()->after('angkatan')
                  ->constrained('periode')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('periode_id');
        });

        Schema::dropIfExists('periode');
    }
};