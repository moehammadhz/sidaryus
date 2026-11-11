<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\JadwalPendadaran;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class MahasiswaController extends Controller
{
    private const PER_HALAMAN = 10;

    private const PESAN = [
        'name.required'          => 'Nama wajib diisi.',
        'name.max'               => 'Nama maksimal 100 karakter.',
        'program_studi.required' => 'Pilih program studi.',
        'program_studi.in'       => 'Program studi tidak valid.',
        'kelas.required'         => 'Pilih kelas.',
        'kelas.in'               => 'Kelas tidak valid.',
        'angkatan.required'      => 'Pilih angkatan.',
        'angkatan.integer'       => 'Angkatan harus berupa angka.',
        'angkatan.between'       => 'Angkatan di luar rentang yang diizinkan.',
        'password.min'           => 'Password minimal 8 karakter.',
    ];

    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q'));

        $query = User::query()
            ->where('role', Role::Mahasiswa->value)
            ->with(['periode', 'waVerifiedBy'])
            ->orderBy('name')
            ->orderBy('id');

        if ($q !== '') {
            $like = '%' . addcslashes($q, '%_\\') . '%';
            $query->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('username', 'like', $like));
        }

        return view('admin.mahasiswa', [
            'daftar'  => $query->paginate(self::PER_HALAMAN)->withQueryString(),
            'q'       => $q,
            'total'   => User::where('role', Role::Mahasiswa->value)->count(),
        ]);
    }

    public function update(Request $request, User $mahasiswa): RedirectResponse
    {
        abort_unless($mahasiswa->role === Role::Mahasiswa, 404);

        $request->merge([
            'name'     => trim((string) $request->input('name')),
            'username' => preg_replace('/\s+/', '', (string) $request->input('username')),
        ]);

        $validator = Validator::make($request->all(), [
            'name'          => ['required', 'string', 'max:100'],
            'username'      => User::aturanNim($mahasiswa->id),
            'program_studi' => ['required', Rule::in(User::PROGRAM_STUDI)],
            'kelas'         => ['required', Rule::in(User::KELAS)],
            'angkatan'      => ['required', 'integer', 'between:' . User::ANGKATAN_MIN . ',' . User::ANGKATAN_MAX],
            'password'      => ['nullable', 'string', 'min:8'],
        ], User::pesanNim() + self::PESAN);

        if ($validator->fails()) {
            // kesalahan masuk ke bag "edit" supaya pop-up terbuka lagi dengan pesannya
            return redirect()->route('admin.mahasiswa.index', $request->only('q', 'page'))
                ->withErrors($validator, 'edit')
                ->withInput($request->except('password'))
                ->with('edit_id', $mahasiswa->id);
        }

        $data = $validator->validated();

        // password hanya diganti jika diisi
        if (empty($data['password'])) {
            unset($data['password']);
        }

        DB::transaction(function () use ($mahasiswa, $data) {
            $mahasiswa->update($data);

            // jadwal menyimpan salinan NIM & nama, jadi ikut diperbarui supaya tidak berbeda
            JadwalPendadaran::where('user_id', $mahasiswa->id)
                ->update(['nim' => $mahasiswa->username, 'nama' => $mahasiswa->name]);
        });

        return redirect()->route('admin.mahasiswa.index', $request->only('q', 'page'))
            ->with('status', "Data {$mahasiswa->name} berhasil diperbarui.");
    }

    public function destroy(Request $request, User $mahasiswa): RedirectResponse
    {
        abort_unless($mahasiswa->role === Role::Mahasiswa, 404);

        $nama  = $mahasiswa->name;
        $berkas = $mahasiswa->dokumen()->pluck('path')->all();
        $id    = $mahasiswa->id;

        // baris dokumen ikut terhapus oleh database; berkas fisiknya dibersihkan di sini
        $mahasiswa->delete();

        $disk = Storage::disk('local');
        foreach ($berkas as $path) {
            $disk->delete($path);
        }
        $disk->deleteDirectory("dokumen/{$id}");

        return redirect()->route('admin.mahasiswa.index', $request->only('q'))
            ->with('status', "Data {$nama} berhasil dihapus.");
    }

    /**
     * Verifikasi manual nomor WhatsApp mahasiswa oleh admin/petugas pengajaran.
     * Mencatat user ID verifikator dan waktu verifikasi.
     */
    public function verifikasiManual(Request $request, User $mahasiswa): RedirectResponse
    {
        abort_unless($mahasiswa->role === Role::Mahasiswa, 404);

        /** @var User $petugas */
        $petugas = $request->user();
        abort_unless($petugas->hasRole(Role::Admin, Role::Pengajaran), 403);

        $mahasiswa->markWaAsVerified($petugas->id);
        \App\Models\OtpCode::where('user_id', $mahasiswa->id)->delete();

        return redirect()->route('admin.mahasiswa.index', $request->only('q', 'page'))
            ->with('status', "Nomor WhatsApp mahasiswa {$mahasiswa->name} berhasil diverifikasi manual oleh {$petugas->name}.");
    }
}