<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('konten_dashboard', function (Blueprint $table) {
            $table->id();
            $table->string('kunci', 30)->unique(); // panduan | syarat | bebas_teori
            $table->json('items');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('konten_dashboard');
    }
};