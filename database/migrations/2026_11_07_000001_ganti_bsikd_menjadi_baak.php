<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Peran BSIKD diganti BAAK (hanya melihat dan mengunduh data mahasiswa). */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->where('role', 'bsikd')->update(['role' => 'baak']);
    }

    public function down(): void
    {
        DB::table('users')->where('role', 'baak')->update(['role' => 'bsikd']);
    }
};
