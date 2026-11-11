<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\DokumenMahasiswa;
use App\Models\JadwalPendadaran;
use App\Models\KontenDashboard;
use App\Models\Persyaratan;
use App\Models\User;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $stats = [
            'mahasiswa'        => User::where('role', Role::Mahasiswa->value)->count(),
            'dokumen_menunggu' => DokumenMahasiswa::where('status', DokumenMahasiswa::MENUNGGU)->count(),
            'jadwal_bulan_ini' => JadwalPendadaran::where('status', JadwalPendadaran::DISETUJUI)
                ->whereBetween('tanggal_ujian', [
                    now()->startOfMonth()->toDateString(),
                    now()->endOfMonth()->toDateString(),
                ])
                ->count(),
            'lulus'            => JadwalPendadaran::where('hasil', JadwalPendadaran::LULUS)->count(),
        ];

        $konten = KontenDashboard::semua();
        // Syarat dokumen diambil dari menu Kelola Persyaratan
        $konten['syarat'] = Persyaratan::orderBy('urutan')->orderBy('id')->pluck('nama')->all();

        return view('admin.dashboard', [
            'stats'   => $stats,
            'konten'  => $konten,
            'tanggal' => now()->locale('id')->translatedFormat('l, j F Y'),
        ]);
    }
}