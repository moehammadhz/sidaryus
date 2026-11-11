<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dosen;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class DosenController extends Controller
{
    private const PESAN = [
        'nidn.regex'           => 'NIDN harus berupa angka 5 sampai 20 digit.',
        'nidn.unique'          => 'NIDN sudah terdaftar.',
        'nama.required'        => 'Nama dosen wajib diisi.',
        'nama.max'             => 'Nama dosen maksimal 120 karakter.',
        'program_studi.required' => 'Pilih program studi.',
        'program_studi.in'     => 'Program studi tidak valid.',
    ];

    public function index(): View
    {
        return view('admin.dosen', [
            'daftar' => Dosen::orderBy('nama')->orderBy('id')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->normalisasi($request);

        $data = $request->validate($this->aturan(), self::PESAN);

        Dosen::create($data);

        return redirect()->route('admin.dosen.index')->with('status', 'Data dosen berhasil disimpan.');
    }

    public function update(Request $request, Dosen $dosen): RedirectResponse
    {
        $this->normalisasi($request);

        $validator = Validator::make($request->all(), $this->aturan($dosen->id), self::PESAN);

        if ($validator->fails()) {
            // kesalahan masuk ke bag "edit" supaya tidak tercampur dengan form tambah
            return redirect()->route('admin.dosen.index')
                ->withErrors($validator, 'edit')
                ->withInput()
                ->with('edit_id', $dosen->id);
        }

        $dosen->update($validator->validated());

        return redirect()->route('admin.dosen.index')->with('status', 'Data dosen berhasil diperbarui.');
    }

    public function destroy(Dosen $dosen): RedirectResponse
    {
        $nama = $dosen->nama;
        $dosen->delete();

        return redirect()->route('admin.dosen.index')->with('status', "Data dosen {$nama} berhasil dihapus.");
    }

    /** @return array<string, array<int, mixed>> */
    private function aturan(?int $abaikanId = null): array
    {
        return [
            'nidn'          => ['nullable', 'regex:/^[0-9]{5,20}$/', Rule::unique('dosen', 'nidn')->ignore($abaikanId)],
            'nama'          => ['required', 'string', 'max:120'],
            'program_studi' => ['required', Rule::in(User::PROGRAM_STUDI)],
        ];
    }

    /** Rapikan spasi; NIDN kosong disimpan sebagai null */
    private function normalisasi(Request $request): void
    {
        $nidn = trim((string) $request->input('nidn'));

        $request->merge([
            'nidn' => $nidn === '' ? null : $nidn,
            'nama' => trim((string) $request->input('nama')),
        ]);
    }
}