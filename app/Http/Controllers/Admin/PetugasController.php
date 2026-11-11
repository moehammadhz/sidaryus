<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PetugasController extends Controller
{
    private const MIN_PASSWORD = 8;

    private const PESAN = [
        'name.required'     => 'Nama wajib diisi.',
        'name.max'          => 'Nama maksimal 100 karakter.',
        'username.required' => 'Username wajib diisi.',
        'username.min'      => 'Username minimal 4 karakter.',
        'username.max'      => 'Username maksimal 50 karakter.',
        'username.alpha_dash' => 'Username hanya boleh huruf, angka, strip, dan underscore.',
        'username.unique'   => 'Username sudah dipakai.',
        'role.required'     => 'Pilih peran petugas.',
        'role.in'           => 'Peran tidak valid.',
        'password.required' => 'Password wajib diisi.',
        'password.min'      => 'Password minimal 8 karakter.',
    ];

    /** Peran yang termasuk petugas (admin dan mahasiswa dikelola di tempat lain) */
    private function peranPetugas(): array
    {
        return [Role::Pengajaran->value, Role::Baak->value];
    }

    public function index(): View
    {
        return view('admin.petugas', [
            'daftar' => User::whereIn('role', $this->peranPetugas())->orderBy('username')->get(),
            'peran'  => [
                Role::Pengajaran->value => Role::Pengajaran->label(),
                Role::Baak->value      => Role::Baak->label(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->normalisasi($request);

        $data = $request->validate([
            'name'     => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'min:4', 'max:50', 'alpha_dash', 'unique:users,username'],
            'role'     => ['required', Rule::in($this->peranPetugas())],
            'password' => ['required', 'string', 'min:' . self::MIN_PASSWORD],
        ], self::PESAN);

        User::create($data); // password di-hash otomatis oleh cast pada model

        return redirect()->route('admin.petugas.index')->with('status', "Akun petugas {$data['username']} berhasil dibuat.");
    }

    public function update(Request $request, User $petugas): RedirectResponse
    {
        $this->pastikanPetugas($petugas);
        $this->normalisasi($request);

        $validator = Validator::make($request->all(), [
            'name'     => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'min:4', 'max:50', 'alpha_dash', Rule::unique('users', 'username')->ignore($petugas->id)],
            'role'     => ['required', Rule::in($this->peranPetugas())],
            'password' => ['nullable', 'string', 'min:' . self::MIN_PASSWORD],
        ], self::PESAN);

        if ($validator->fails()) {
            // kesalahan masuk ke bag "edit" supaya pop-up terbuka lagi dengan pesannya
            return redirect()->route('admin.petugas.index')
                ->withErrors($validator, 'edit')
                ->withInput($request->except('password'))
                ->with('edit_id', $petugas->id);
        }

        $data = $validator->validated();

        // password hanya diganti jika diisi
        if (empty($data['password'])) {
            unset($data['password']);
        }

        $petugas->update($data);

        return redirect()->route('admin.petugas.index')->with('status', "Akun {$petugas->username} berhasil diperbarui.");
    }

    public function destroy(User $petugas): RedirectResponse
    {
        $this->pastikanPetugas($petugas);

        $username = $petugas->username;
        $petugas->delete();

        return redirect()->route('admin.petugas.index')->with('status', "Akun petugas {$username} berhasil dihapus.");
    }

    private function pastikanPetugas(User $user): void
    {
        abort_unless(in_array($user->role->value, $this->peranPetugas(), true), 404);
    }

    private function normalisasi(Request $request): void
    {
        $request->merge([
            'name'     => trim((string) $request->input('name')),
            'username' => trim((string) $request->input('username')),
        ]);
    }
}