<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\DokumenController;
use App\Http\Controllers\Admin\DosenController;
use App\Http\Controllers\Admin\JadwalController;
use App\Http\Controllers\Admin\KontenController;
use App\Http\Controllers\Admin\MahasiswaController;
use App\Http\Controllers\Admin\PeriodeController;
use App\Http\Controllers\Admin\PersyaratanController;
use App\Http\Controllers\Admin\PetugasController;
use App\Http\Controllers\Admin\YudisiumController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Baak\MahasiswaController as BaakMahasiswaController;
use App\Http\Controllers\Mahasiswa\DashboardController as MahasiswaDashboardController;
use App\Http\Controllers\Mahasiswa\DokumenController as MahasiswaDokumenController;
use App\Http\Controllers\Mahasiswa\JadwalController as MahasiswaJadwalController;
use App\Http\Controllers\Mahasiswa\PembayaranController;
use App\Http\Controllers\Mahasiswa\PeriodeController as MahasiswaPeriodeController;
use App\Http\Controllers\Mahasiswa\UndanganController;
use App\Http\Controllers\Petugas\DashboardController as PetugasDashboardController;
use App\Http\Controllers\RegistrasiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1')
        ->name('login.store');

    // Registrasi mahasiswa dengan kode OTP WhatsApp
    Route::get('/register', [RegistrasiController::class, 'create'])->name('register');
    Route::post('/register', [RegistrasiController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('register.store');
    Route::get('/register/verifikasi', [RegistrasiController::class, 'verifikasi'])->name('register.verifikasi');
    Route::post('/register/verifikasi', [RegistrasiController::class, 'konfirmasi'])
        ->middleware('throttle:10,1')
        ->name('register.konfirmasi');
    Route::post('/register/kirim-ulang', [RegistrasiController::class, 'kirimUlang'])
        ->middleware('throttle:3,1')
        ->name('register.kirim-ulang');
    Route::post('/register/ganti-nomor', [RegistrasiController::class, 'gantiNomor'])
        ->middleware('throttle:5,1')
        ->name('register.ganti-nomor');

    // Alias URL /verifikasi-otp
    Route::get('/verifikasi-otp', [RegistrasiController::class, 'verifikasi'])->name('otp.index');
    Route::post('/verifikasi-otp', [RegistrasiController::class, 'konfirmasi'])
        ->middleware('throttle:10,1')
        ->name('otp.verify');
    Route::post('/verifikasi-otp/kirim-ulang', [RegistrasiController::class, 'kirimUlang'])
        ->middleware('throttle:3,1')
        ->name('otp.resend');
    Route::post('/verifikasi-otp/ganti-nomor', [RegistrasiController::class, 'gantiNomor'])
        ->middleware('throttle:5,1')
        ->name('otp.update-phone');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // /dashboard mengarahkan setiap pengguna ke halaman awal sesuai role-nya
    Route::get('/dashboard', function (Request $request) {
        return redirect()->route($request->user()->role->dashboardRoute());
    })->name('dashboard');

    // Membuka berkas: admin, pengajaran, dan BAAK (BAAK hanya melihat)
    Route::middleware('role:admin,pengajaran,baak')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dokumen/berkas/{dokumen}', [DokumenController::class, 'berkas'])->name('dokumen.berkas');
        Route::get('/jadwal/{jadwal}/bukti-bayar', [JadwalController::class, 'buktiBayar'])->name('jadwal.bukti-bayar');
        Route::get('/jadwal/{jadwal}/undangan', [JadwalController::class, 'lihatUndangan'])->name('jadwal.undangan');
        Route::get('/bukti-bayar/riwayat/{riwayat}', [JadwalController::class, 'riwayatBukti'])->name('jadwal.riwayat-bukti');
    });

    // Admin dan pengajaran: verifikasi, jadwal, nilai, yudisium
    Route::middleware('role:admin,pengajaran')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/jadwal', [JadwalController::class, 'index'])->name('jadwal.index');
        Route::get('/jadwal/ekspor', [JadwalController::class, 'ekspor'])->name('jadwal.ekspor');
        Route::get('/jadwal/{jadwal}', [JadwalController::class, 'show'])->name('jadwal.show');
        Route::patch('/jadwal/{jadwal}/setuju', [JadwalController::class, 'setuju'])->name('jadwal.setuju');
        Route::patch('/jadwal/{jadwal}/tolak', [JadwalController::class, 'tolak'])->name('jadwal.tolak');
        Route::patch('/jadwal/{jadwal}/setuju-bukti', [JadwalController::class, 'setujuBukti'])->name('jadwal.setuju-bukti');
        Route::patch('/jadwal/{jadwal}/tolak-bukti', [JadwalController::class, 'tolakBukti'])->name('jadwal.tolak-bukti');
        Route::post('/jadwal/{jadwal}/undangan', [JadwalController::class, 'unggahUndangan'])->name('jadwal.undangan.store');
        Route::put('/jadwal/{jadwal}/nilai', [JadwalController::class, 'simpanNilai'])->name('jadwal.nilai');

        Route::get('/dokumen', [DokumenController::class, 'index'])->name('dokumen.index');
        Route::get('/dokumen/ekspor', [DokumenController::class, 'ekspor'])->name('dokumen.ekspor');
        Route::get('/dokumen/mahasiswa/{mahasiswa}', [DokumenController::class, 'show'])->name('dokumen.show');
        Route::post('/dokumen/mahasiswa/{mahasiswa}/setuju-semua', [DokumenController::class, 'setujuSemua'])->name('dokumen.setuju-semua');
        Route::patch('/dokumen/berkas/{dokumen}/setuju', [DokumenController::class, 'setuju'])->name('dokumen.setuju');
        Route::patch('/dokumen/berkas/{dokumen}/tolak', [DokumenController::class, 'tolak'])->name('dokumen.tolak');

        Route::get('/yudisium', [YudisiumController::class, 'index'])->name('yudisium.index');
        Route::put('/yudisium/{mahasiswa}', [YudisiumController::class, 'ubahPeriode'])->name('yudisium.periode');
        Route::delete('/yudisium/{mahasiswa}', [YudisiumController::class, 'lepas'])->name('yudisium.lepas');

        Route::get('/periode', [PeriodeController::class, 'index'])->name('periode.index');
        Route::post('/periode', [PeriodeController::class, 'store'])->name('periode.store');
        Route::get('/periode/{periode}', [PeriodeController::class, 'show'])->name('periode.show');
        Route::put('/periode/{periode}', [PeriodeController::class, 'update'])->name('periode.update');
        Route::patch('/periode/{periode}/status', [PeriodeController::class, 'ubahStatus'])->name('periode.status');
        Route::delete('/periode/{periode}', [PeriodeController::class, 'destroy'])->name('periode.destroy');
    });

    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

        Route::get('/konten', [KontenController::class, 'edit'])->name('konten');
        Route::put('/konten', [KontenController::class, 'update'])->name('konten.update');

        Route::get('/persyaratan', [PersyaratanController::class, 'index'])->name('persyaratan.index');
        Route::post('/persyaratan', [PersyaratanController::class, 'store'])->name('persyaratan.store');
        Route::post('/persyaratan/urutan', [PersyaratanController::class, 'reorder'])->name('persyaratan.reorder');
        Route::put('/persyaratan/{persyaratan}', [PersyaratanController::class, 'update'])->name('persyaratan.update');
        Route::delete('/persyaratan/{persyaratan}', [PersyaratanController::class, 'destroy'])->name('persyaratan.destroy');

        Route::get('/dosen', [DosenController::class, 'index'])->name('dosen.index');
        Route::post('/dosen', [DosenController::class, 'store'])->name('dosen.store');
        Route::put('/dosen/{dosen}', [DosenController::class, 'update'])->name('dosen.update');
        Route::delete('/dosen/{dosen}', [DosenController::class, 'destroy'])->name('dosen.destroy');

        Route::get('/mahasiswa', [MahasiswaController::class, 'index'])->name('mahasiswa.index');
        Route::put('/mahasiswa/{mahasiswa}', [MahasiswaController::class, 'update'])->name('mahasiswa.update');
        Route::delete('/mahasiswa/{mahasiswa}', [MahasiswaController::class, 'destroy'])->name('mahasiswa.destroy');
        Route::post('/mahasiswa/{mahasiswa}/verifikasi-manual', [MahasiswaController::class, 'verifikasiManual'])->name('mahasiswa.verifikasi-manual');

        Route::get('/petugas', [PetugasController::class, 'index'])->name('petugas.index');
        Route::post('/petugas', [PetugasController::class, 'store'])->name('petugas.store');
        Route::put('/petugas/{petugas}', [PetugasController::class, 'update'])->name('petugas.update');
        Route::delete('/petugas/{petugas}', [PetugasController::class, 'destroy'])->name('petugas.destroy');
    });

    Route::middleware('role:pengajaran')->prefix('petugas')->name('petugas.')->group(function () {
        Route::get('/dashboard', [PetugasDashboardController::class, 'index'])->name('dashboard');
    });

    // BAAK: hanya melihat data mahasiswa dan mengunduh PDF-nya
    Route::middleware('role:baak')->prefix('baak')->name('baak.')->group(function () {
        Route::get('/mahasiswa', [BaakMahasiswaController::class, 'index'])->name('mahasiswa.index');
        Route::get('/mahasiswa/{mahasiswa}', [BaakMahasiswaController::class, 'show'])->name('mahasiswa.show');
        Route::get('/mahasiswa/{mahasiswa}/pdf', [BaakMahasiswaController::class, 'pdf'])->name('mahasiswa.pdf');
    });

    Route::middleware('role:mahasiswa')->prefix('mahasiswa')->name('mahasiswa.')->group(function () {
        Route::get('/dashboard', [MahasiswaDashboardController::class, 'index'])->name('dashboard');

        Route::get('/dokumen', [MahasiswaDokumenController::class, 'index'])->name('dokumen.index');
        Route::post('/dokumen', [MahasiswaDokumenController::class, 'store'])->name('dokumen.store');
        Route::delete('/dokumen/{dokumen}', [MahasiswaDokumenController::class, 'destroy'])->name('dokumen.destroy');

        Route::get('/jadwal', [MahasiswaJadwalController::class, 'index'])->name('jadwal.index');
        Route::post('/jadwal', [MahasiswaJadwalController::class, 'store'])->name('jadwal.store');
        Route::get('/jadwal/{jadwal}/bukti-bayar', [MahasiswaJadwalController::class, 'buktiBayar'])->name('jadwal.bukti-bayar');

        Route::get('/pembayaran', [PembayaranController::class, 'index'])->name('pembayaran.index');
        Route::post('/pembayaran', [PembayaranController::class, 'store'])->name('pembayaran.store');

        Route::get('/undangan', [UndanganController::class, 'index'])->name('undangan.index');
        Route::get('/undangan/{jadwal}', [UndanganController::class, 'unduh'])->name('undangan.unduh');

        Route::post('/periode', [MahasiswaPeriodeController::class, 'store'])->name('periode.daftar');
    });
});
