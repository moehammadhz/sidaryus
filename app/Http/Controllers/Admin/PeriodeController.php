<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JadwalPendadaran;
use App\Models\Periode;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/** Periode Yudisium: dibuka/ditutup oleh admin atau pengajaran, mahasiswa lulus mendaftar sendiri. */
class PeriodeController extends Controller
{
    private const PESAN = [
        'nama.required' => 'Nama periode wajib diisi.',
        'nama.max'      => 'Nama periode maksimal 100 karakter.',
        'nama.unique'   => 'Nama periode sudah ada.',
    ];

    public function index(): View
    {
        return view('admin.periode', [
            'daftar' => Periode::withCount('mahasiswa')->orderByDesc('dibuka')->orderByDesc('id')->get(),
        ]);
    }

    /** Daftar mahasiswa yang terdaftar di satu periode */
    public function show(Periode $periode): View
    {
        $mahasiswa = $periode->mahasiswa()->orderBy('program_studi')->orderBy('username')->get();

        // Nilai dan judul diambil dari jadwal yang lulus milik setiap mahasiswa
        $jadwal = JadwalPendadaran::whereIn('user_id', $mahasiswa->pluck('id'))
            ->where('hasil', JadwalPendadaran::LULUS)
            ->get()
            ->keyBy('user_id');

        $nilai = $jadwal->pluck('nilai_akhir')->map(fn ($n) => (float) $n);

        return view('admin.periode-show', [
            'periode'   => $periode,
            'mahasiswa' => $mahasiswa,
            'jadwal'    => $jadwal,
            'perProdi'  => $mahasiswa->countBy(fn ($m) => $m->program_studi ?? 'Tanpa prodi')->sortKeys(),
            'rataRata'  => $nilai->isNotEmpty() ? round($nilai->avg(), 2) : null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nama'   => ['required', 'string', 'max:100', 'unique:periode,nama'],
            'dibuka' => ['nullable', 'boolean'],
        ], self::PESAN);

        $periode = Periode::create(['nama' => trim($data['nama']), 'dibuka' => $request->boolean('dibuka')]);

        return redirect()->route('admin.periode.index')->with('status', 'Periode berhasil ditambahkan'
            . ($periode->dibuka ? ' dan langsung dibuka untuk pendaftaran.' : '. Buka periode agar mahasiswa bisa mendaftar.'));
    }

    public function update(Request $request, Periode $periode): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'nama' => ['required', 'string', 'max:100', Rule::unique('periode', 'nama')->ignore($periode->id)],
        ], self::PESAN);

        if ($validator->fails()) {
            return redirect()->route('admin.periode.index')
                ->withErrors($validator, 'edit')
                ->withInput()
                ->with('edit_id', $periode->id);
        }

        $periode->update(['nama' => trim($validator->validated()['nama'])]);

        return redirect()->route('admin.periode.index')->with('status', 'Periode berhasil diperbarui.');
    }

    /** Buka atau tutup pendaftaran periode */
    public function ubahStatus(Periode $periode): RedirectResponse
    {
        $periode->update(['dibuka' => ! $periode->dibuka]);

        return back()->with('status', $periode->dibuka
            ? "{$periode->nama} dibuka. Mahasiswa yang sudah lulus kini bisa mendaftar dari dashboard."
            : "{$periode->nama} ditutup. Mahasiswa yang sudah terdaftar tetap tercatat.");
    }

    public function destroy(Periode $periode): RedirectResponse
    {
        if ($periode->mahasiswa()->exists()) {
            return redirect()->route('admin.periode.index')
                ->with('error', 'Periode tidak bisa dihapus karena masih ada mahasiswa yang terdaftar. Lepas mereka dulu dari halaman detail periode.');
        }

        $periode->delete();

        return redirect()->route('admin.periode.index')->with('status', 'Periode berhasil dihapus.');
    }
}
