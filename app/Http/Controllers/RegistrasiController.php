<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\OtpCode;
use App\Models\User;
use App\Services\OtpService;
use App\Support\NomorWa;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Throwable;

/**
 * Alur registrasi mahasiswa dengan verifikasi WhatsApp via gateway Fonnte:
 * 1) Isi formulir -> User dibuat (belum terverifikasi) & kode OTP dimasukkan ke antrean
 * 2) Masukkan kode OTP -> Akun aktif & login ke dashboard
 */
class RegistrasiController extends Controller
{
    private const SESI = 'otp_user_id';

    public function __construct(private OtpService $otpService)
    {
    }

    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $normalizedWa = $this->otpService->normalizePhone($request->input('no_wa'));

        $request->merge([
            'name'     => trim((string) $request->input('name')),
            'username' => preg_replace('/\s+/', '', (string) $request->input('username')),
            'no_wa'    => $normalizedWa,
        ]);

        $data = $request->validate([
            'name'          => ['required', 'string', 'max:100'],
            'username'      => User::aturanNim(),
            'no_wa'         => [
                'required',
                'string',
                function ($attr, $nilai, $gagal) use ($normalizedWa) {
                    if (! $normalizedWa || ! $this->otpService->isValidPhone($normalizedWa)) {
                        $gagal('Nomor WhatsApp tidak valid. Gunakan format contoh: 081234567890.');
                    }
                },
                Rule::unique('users', 'no_wa'),
            ],
            'program_studi' => ['required', Rule::in(User::PROGRAM_STUDI)],
            'kelas'         => ['required', Rule::in(User::KELAS)],
            'angkatan'      => ['required', 'integer', 'between:' . User::ANGKATAN_MIN . ',' . User::ANGKATAN_MAX],
            'password'      => ['required', 'string', 'min:8', 'confirmed'],
        ], User::pesanNim() + [
            'name.required'          => 'Nama lengkap wajib diisi.',
            'name.max'               => 'Nama maksimal 100 karakter.',
            'no_wa.required'         => 'Nomor WhatsApp wajib diisi untuk menerima kode verifikasi.',
            'no_wa.unique'           => 'Nomor WhatsApp ini sudah terdaftar.',
            'program_studi.required' => 'Pilih program studi.',
            'program_studi.in'       => 'Program studi tidak valid.',
            'kelas.required'         => 'Pilih kelas.',
            'kelas.in'               => 'Kelas tidak valid.',
            'angkatan.required'      => 'Pilih angkatan.',
            'angkatan.integer'       => 'Angkatan harus berupa angka.',
            'angkatan.between'       => 'Angkatan harus antara ' . User::ANGKATAN_MIN . ' dan ' . User::ANGKATAN_MAX . '.',
            'password.required'      => 'Password wajib diisi.',
            'password.min'           => 'Password minimal 8 karakter.',
            'password.confirmed'     => 'Konfirmasi password tidak sama.',
        ]);

        // Buat user dalam status belum terverifikasi
        $user = User::create([
            'name'           => $data['name'],
            'username'       => $data['username'],
            'no_wa'          => $data['no_wa'],
            'wa_verified_at' => null,
            'program_studi'  => $data['program_studi'],
            'kelas'          => $data['kelas'],
            'angkatan'       => (int) $data['angkatan'],
            'password'       => Hash::make($data['password']),
            'role'           => Role::Mahasiswa->value,
        ]);

        $request->session()->put(self::SESI, $user->id);

        try {
            $kode = $this->otpService->issue($user);
            $request->session()->put('otp_last_sent_at', now()->timestamp);
        } catch (Throwable $e) {
            Log::error('Gagal memasukkan OTP registrasi ke antrean: ' . $e->getMessage());

            return redirect()->route('register.verifikasi')
                ->with('warning', 'Pendaftaran akun berhasil, namun pengiriman kode OTP mengalami kendala. Silakan coba klik "Kirim ulang kode".');
        }

        return redirect()->route('register.verifikasi')->with('kode_uji', $this->kodeUji($kode));
    }

    public function verifikasi(Request $request): View|RedirectResponse
    {
        $user = $this->userSesi($request);
        if (! $user) {
            return redirect()->route('register')->with('error', 'Sesi verifikasi berakhir. Silakan isi formulir registrasi lagi.');
        }

        if ($user->isWaVerified()) {
            $request->session()->forget(self::SESI);
            return redirect()->route('login')->with('status', 'Nomor WhatsApp Anda sudah terverifikasi. Silakan login.');
        }

        $otp = OtpCode::where('user_id', $user->id)->first();
        $cooldownDb = $otp ? $otp->remainingCooldown() : 0;
        $lastSent = (int) $request->session()->get('otp_last_sent_at', 0);
        $elapsedSession = $lastSent > 0 ? abs((int) now()->timestamp - $lastSent) : 999;
        $cooldownSession = max(0, OtpCode::JEDA_KIRIM_ULANG_DETIK - $elapsedSession);
        $cooldown = max($cooldownDb, $cooldownSession);

        return view('auth.verifikasi', [
            'user'     => $user,
            'nomor'    => NomorWa::samarkan($user->no_wa ?? ''),
            'noWaAsli' => $user->no_wa,
            'cooldown' => $cooldown,
            'kodeUji'  => session('kode_uji'),
        ]);
    }

    public function konfirmasi(Request $request): RedirectResponse
    {
        $user = $this->userSesi($request);
        if (! $user) {
            return redirect()->route('register')->with('error', 'Sesi verifikasi berakhir. Silakan isi formulir lagi.');
        }

        $request->merge(['kode' => preg_replace('/\D+/', '', (string) $request->input('kode'))]);
        $request->validate(
            ['kode' => ['required', 'digits:6']],
            [
                'kode.required' => 'Masukkan 6 digit kode verifikasi.',
                'kode.digits'   => 'Kode verifikasi harus terdiri dari 6 angka.',
            ]
        );

        $hasil = $this->otpService->verify($user, (string) $request->input('kode'));

        if (! $hasil['status']) {
            return back()->withErrors(['kode' => $hasil['message']]);
        }

        // Bersihkan sesi pending, login, dan regenerate session
        $request->session()->forget(self::SESI);
        $request->session()->forget('otp_last_sent_at');
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('status', 'Nomor WhatsApp berhasil diverifikasi! Selamat datang di SIDARYUS.');
    }

    public function kirimUlang(Request $request): RedirectResponse
    {
        $user = $this->userSesi($request);
        if (! $user) {
            return redirect()->route('register')->with('error', 'Sesi verifikasi berakhir. Silakan isi formulir lagi.');
        }

        $lastSent = (int) $request->session()->get('otp_last_sent_at', 0);
        $otp = OtpCode::where('user_id', $user->id)->first();
        $cooldownDb = $otp ? $otp->remainingCooldown() : 0;
        $elapsedSession = $lastSent > 0 ? abs((int) now()->timestamp - $lastSent) : 999;
        $cooldownSession = max(0, OtpCode::JEDA_KIRIM_ULANG_DETIK - $elapsedSession);
        $sisa = max($cooldownDb, $cooldownSession);

        if ($sisa > 0) {
            return back()->withErrors(['kode' => "Tunggu {$sisa} detik sebelum meminta kode baru."]);
        }

        try {
            $kode = $this->otpService->issue($user);
            $request->session()->put('otp_last_sent_at', now()->timestamp);
        } catch (Throwable $e) {
            Log::error('Gagal mengirim ulang OTP: ' . $e->getMessage());

            return back()->withErrors(['kode' => 'Kode gagal dikirim. Coba beberapa saat lagi.']);
        }

        return back()->with('status', 'Kode verifikasi baru sedang dikirim ke WhatsApp Anda.')
            ->with('kode_uji', $this->kodeUji($kode));
    }

    public function gantiNomor(Request $request): RedirectResponse
    {
        $user = $this->userSesi($request);
        if (! $user) {
            return redirect()->route('register')->with('error', 'Sesi verifikasi berakhir. Silakan isi formulir lagi.');
        }

        if ($user->isWaVerified()) {
            return redirect()->route('dashboard');
        }

        $normalized = $this->otpService->normalizePhone($request->input('no_wa_baru'));

        if (! $normalized || ! $this->otpService->isValidPhone($normalized)) {
            return back()->withErrors(['no_wa_baru' => 'Format nomor WhatsApp tidak valid. Contoh: 081234567890.']);
        }

        if (User::where('no_wa', $normalized)->where('id', '!=', $user->id)->exists()) {
            return back()->withErrors(['no_wa_baru' => 'Nomor WhatsApp ini sudah dipakai akun lain.']);
        }

        $user->no_wa = $normalized;
        $user->save();

        try {
            $kode = $this->otpService->issue($user);
            $request->session()->put('otp_last_sent_at', now()->timestamp);
        } catch (Throwable $e) {
            Log::error('Gagal mengirim OTP ke nomor baru: ' . $e->getMessage());

            return back()->with('status', 'Nomor WhatsApp berhasil diubah, namun pengiriman kode mengalami kendala. Silakan kirim ulang kode.');
        }

        return back()->with('status', 'Nomor WhatsApp berhasil diubah dan kode verifikasi telah dikirim ke nomor baru.')
            ->with('kode_uji', $this->kodeUji($kode));
    }

    private function userSesi(Request $request): ?User
    {
        $id = $request->session()->get(self::SESI);

        return $id ? User::find($id) : null;
    }

    /**
     * Mode uji coba: kode ditampilkan di layar hanya jika config OTP_TAMPILKAN_KODE aktif.
     */
    private function kodeUji(string $kode): ?string
    {
        if (app()->environment('testing')) {
            return $kode;
        }

        return config('otp.tampilkan_kode', false) ? $kode : null;
    }
}
