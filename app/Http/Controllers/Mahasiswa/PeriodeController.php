<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Models\Periode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Mahasiswa yang sudah lulus pendadaran mendaftar sendiri ke periode yudisium yang dibuka.
 * Setelah terdaftar, mahasiswa tidak bisa membatalkan atau pindah; itu wewenang admin dan pengajaran.
 */
class PeriodeController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user->jadwalLulus()) {
            return back()->with('error', 'Pendaftaran yudisium dibuka setelah Anda dinyatakan lulus ujian pendadaran.');
        }

        $data = $request->validate(
            ['periode_id' => ['required', 'integer', 'exists:periode,id']],
            ['periode_id.required' => 'Pilih periode yudisium.', 'periode_id.exists' => 'Periode tidak ditemukan.'],
        );

        $periode = Periode::findOrFail($data['periode_id']);

        if (! $periode->dibuka) {
            return back()->with('error', "{$periode->nama} sedang tidak menerima pendaftaran.");
        }

        // Setelah terdaftar, perubahan hanya oleh admin atau pengajaran
        if ($user->periode_id) {
            return back()->with('error', "Anda sudah terdaftar di {$user->periode->nama}. Hubungi bagian pengajaran jika periode perlu diubah.");
        }

        $user->forceFill(['periode_id' => $periode->id, 'periode_daftar_at' => now()])->save();

        return back()->with('status', "Anda berhasil terdaftar di {$periode->nama}.");
    }
}
