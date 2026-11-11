<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'no_wa')) {
                $table->string('no_wa', 20)->nullable()->unique()->after('email');
            }
            if (! Schema::hasColumn('users', 'wa_verified_at')) {
                $table->timestamp('wa_verified_at')->nullable()->after('no_wa');
            }
            if (! Schema::hasColumn('users', 'wa_verified_by')) {
                $table->foreignId('wa_verified_by')->nullable()->after('wa_verified_at')->constrained('users')->nullOnDelete();
            }
        });

        // User lama tidak boleh terkunci: isi wa_verified_at = now() untuk akun yang sudah ada
        DB::table('users')->whereNull('wa_verified_at')->update([
            'wa_verified_at' => now(),
        ]);

        if (! Schema::hasTable('otp_codes')) {
            Schema::create('otp_codes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
                $table->string('code'); // HASH
                $table->timestamp('expires_at');
                $table->unsignedTinyInteger('attempts')->default(0);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('otp_codes');

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'wa_verified_by')) {
                $table->dropForeign(['wa_verified_by']);
                $table->dropColumn('wa_verified_by');
            }
            if (Schema::hasColumn('users', 'wa_verified_at')) {
                $table->dropColumn('wa_verified_at');
            }
        });
    }
};
