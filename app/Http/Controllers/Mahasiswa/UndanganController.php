<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Models\JadwalPendadaran;
use Illuminate\Contracts\View\View;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/** Undangan jadwal ujian yang diunggah pengajaran setelah jadwal disetujui. */
class UndanganController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        return view('mahasiswa.undangan', [
            'undangan' => JadwalPendadaran::where('user_id', $user->id)->whereNotNull('undangan_path')->latest('id')->get(),
            'terakhir' => $user->jadwalTerakhir(),
        ]);
    }

    public function unduh(Request $request, JadwalPendadaran $jadwal): Response
    {
        abort_unless($jadwal->user_id === $request->user()->id && $jadwal->undangan_path, 404);

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('local');
        abort_unless($disk->exists($jadwal->undangan_path), 404, 'Berkas tidak ditemukan.');

        $nama = 'Undangan-Pendadaran-' . $jadwal->nim . '-' . $jadwal->tanggal_ujian->format('Y-m-d') . '.pdf';

        return $disk->response($jadwal->undangan_path, $nama,
            ['Content-Type' => 'application/pdf', 'X-Content-Type-Options' => 'nosniff'],
            $request->boolean('unduh') ? 'attachment' : 'inline');
    }
}
