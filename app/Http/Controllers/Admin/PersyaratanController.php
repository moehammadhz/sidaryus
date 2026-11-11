<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Persyaratan;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PersyaratanController extends Controller
{
    private const PESAN = [
        'kode.required'  => 'Kode wajib diisi.',
        'kode.max'       => 'Kode maksimal 20 karakter.',
        'kode.regex'     => 'Kode hanya boleh huruf, angka, strip, dan underscore.',
        'kode.unique'    => 'Kode sudah dipakai.',
        'nama.required'  => 'Nama dokumen wajib diisi.',
        'nama.max'       => 'Nama dokumen maksimal 255 karakter.',
    ];

    public function index(): View
    {
        return view('admin.persyaratan', [
            'daftar' => Persyaratan::withCount('dokumen')->orderBy('urutan')->orderBy('id')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->normalisasi($request);

        $data = $request->validate([
            'kode' => ['required', 'string', 'max:20', 'regex:/^[A-Za-z0-9_-]+$/', 'unique:persyaratan,kode'],
            'nama' => ['required', 'string', 'max:255'],
        ], self::PESAN);

        Persyaratan::create($data + ['urutan' => (int) Persyaratan::max('urutan') + 1]);

        return redirect()->route('admin.persyaratan.index')->with('status', 'Persyaratan berhasil ditambahkan.');
    }

    public function update(Request $request, Persyaratan $persyaratan): RedirectResponse
    {
        $this->normalisasi($request);

        $validator = Validator::make($request->all(), [
            'kode' => ['required', 'string', 'max:20', 'regex:/^[A-Za-z0-9_-]+$/',
                       Rule::unique('persyaratan', 'kode')->ignore($persyaratan->id)],
            'nama' => ['required', 'string', 'max:255'],
        ], self::PESAN);

        if ($validator->fails()) {
            // kesalahan masuk ke bag "edit" supaya tidak tercampur dengan form tambah
            return redirect()->route('admin.persyaratan.index')
                ->withErrors($validator, 'edit')
                ->withInput()
                ->with('edit_id', $persyaratan->id);
        }

        $persyaratan->update($validator->validated());

        return redirect()->route('admin.persyaratan.index')->with('status', 'Persyaratan berhasil diperbarui.');
    }

    public function destroy(Persyaratan $persyaratan): RedirectResponse
    {
        // baris dokumen ikut terhapus oleh database (cascade); berkas fisiknya dibersihkan setelahnya
        $berkas = $persyaratan->dokumen()->pluck('path')->all();

        DB::transaction(function () use ($persyaratan) {
            $persyaratan->delete();

            // rapikan nomor urut supaya tidak ada celah
            Persyaratan::orderBy('urutan')->orderBy('id')->get()
                ->each(fn (Persyaratan $p, int $i) => $p->update(['urutan' => $i + 1]));
        });

        Storage::disk('local')->delete($berkas);

        $pesan = 'Persyaratan berhasil dihapus.' . ($berkas ? ' ' . count($berkas) . ' berkas mahasiswa ikut dihapus.' : '');

        return redirect()->route('admin.persyaratan.index')->with('status', $pesan);
    }

    public function reorder(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ids'   => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'distinct', 'exists:persyaratan,id'],
        ]);

        DB::transaction(function () use ($data) {
            foreach ($data['ids'] as $i => $id) {
                Persyaratan::whereKey($id)->update(['urutan' => $i + 1]);
            }
        });

        return response()->json(['ok' => true]);
    }

    /** Kode disimpan huruf besar tanpa spasi di tepi */
    private function normalisasi(Request $request): void
    {
        $request->merge([
            'kode' => strtoupper(trim((string) $request->input('kode'))),
            'nama' => trim((string) $request->input('nama')),
        ]);
    }
}