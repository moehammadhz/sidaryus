<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'username.required' => 'Username wajib diisi.',
            'password.required' => 'Password wajib diisi.',
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'username' => 'Username atau password salah.',
            ]);
        }

        /** @var User $user */
        $user = Auth::user();

        // Akun yang belum verifikasi WhatsApp tidak boleh login; arahkan ke halaman OTP
        if (! $user->isWaVerified()) {
            $userId = $user->id;
            Auth::logout();
            $request->session()->put('otp_user_id', $userId);

            return redirect()->route('register.verifikasi')
                ->with('warning', 'Verifikasi nomor WhatsApp dulu ya.');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    // Registrasi mahasiswa ditangani RegistrasiController (dengan OTP WhatsApp)

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}