<?php

namespace App\Http\Controllers\Petugas;

use App\Http\Controllers\Controller;
use App\Models\DokumenMahasiswa;
use App\Models\JadwalPendadaran;
use App\Support\Akses;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Hak akses dibaca dari aturan route, jadi otomatis mengikuti web.php
        $bisaDokumen = Akses::bisa($user, 'admin.dokumen.index');
        $bisaJadwal  = Akses::bisa($user, 'admin.jadwal.index');

        $stats = [
            'dokumen_menunggu' => $bisaDokumen
                ? DokumenMahasiswa::where('status', DokumenMahasiswa::MENUNGGU)->count()
                : 0,
            'jadwal_pending'   => $bisaJadwal
                ? JadwalPendadaran::where('status', JadwalPendadaran::PENDING)->count()
                : 0,
            'jadwal_bulan_ini' => $bisaJadwal
                ? JadwalPendadaran::where('status', JadwalPendadaran::DISETUJUI)
                    ->whereMonth('tanggal_ujian', now()->month)
                    ->whereYear('tanggal_ujian', now()->year)
                    ->count()
                : 0,
        ];

        return view('petugas.dashboard', [
            'stats'       => $stats,
            'bisaDokumen' => $bisaDokumen,
            'bisaJadwal'  => $bisaJadwal,
            'tanggal'     => Carbon::now()->locale('id')->translatedFormat('l, d F Y'),
        ]);
    }
}