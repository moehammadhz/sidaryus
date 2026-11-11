<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Models\DokumenMahasiswa;
use App\Models\Persyaratan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class DokumenController extends Controller
{
    /** Ukuran maksimal berkas dalam KB (5120 KB = 5 MB). Ubah di sini jika ingin batas lain. */
    private const MAKS_KB = 5120;

    public function index(Request $request)
    {
        $persyaratan = Persyaratan::orderBy('urutan')->orderBy('id')->get();
        $dokumen = $request->user()->dokumen()->get()->keyBy('persyaratan_id');

        return view('mahasiswa.dokumen.index', [
            'persyaratan' => $persyaratan,
            'dokumen'     => $dokumen,
        ]);
    }

    public function store(Request $request)
    {
        $maksMb = self::MAKS_KB / 1024;

        $request->validate([
            'persyaratan_id' => ['required', 'integer', 'exists:persyaratan,id'],
            // mimes memeriksa ekstensi, mimetypes memeriksa isi berkas yang sebenarnya
            'file'           => ['required', 'file', 'mimes:pdf', 'mimetypes:application/pdf', 'max:' . self::MAKS_KB],
        ], [
            'persyaratan_id.required' => 'Jenis dokumen belum dipilih.',
            'persyaratan_id.exists'   => 'Jenis dokumen tidak valid.',
            'file.required'           => 'Pilih berkas PDF terlebih dahulu.',
            'file.file'               => 'Unggahan tidak valid.',
            'file.mimes'              => 'Berkas harus berformat PDF.',
            'file.mimetypes'          => 'Berkas harus berformat PDF.',
            'file.max'                => "Ukuran berkas maksimal {$maksMb} MB.",
            'file.uploaded'           => "Berkas gagal diunggah. Pastikan ukurannya tidak lebih dari {$maksMb} MB.",
        ]);

        $user = $request->user();
        $file = $request->file('file');

        $dokumenLama = DokumenMahasiswa::where('user_id', $user->id)
            ->where('persyaratan_id', $request->persyaratan_id)
            ->first();

        if ($dokumenLama && $dokumenLama->status === DokumenMahasiswa::DISETUJUI) {
            return back()->with('error', 'Dokumen sudah disetujui, tidak dapat diubah.');
        }

        // Simpan di disk privat "local": hanya bisa dibuka lewat halaman verifikasi, tanpa tautan publik
        $disk = Storage::disk('local');
        $path = $file->store("dokumen/{$user->id}", 'local');

        if (! $path) {
            return back()->with('error', 'Berkas gagal disimpan. Silakan coba lagi.');
        }

        try {
            DokumenMahasiswa::updateOrCreate(
                ['user_id' => $user->id, 'persyaratan_id' => $request->persyaratan_id],
                [
                    'path'              => $path,
                    'nama_asli'         => Str::limit($file->getClientOriginalName(), 255, ''),
                    'mime'              => 'application/pdf',
                    'ukuran'            => $file->getSize(),
                    'status'            => DokumenMahasiswa::MENUNGGU,
                    'catatan'           => null,
                    'diverifikasi_oleh' => null,
                    'diverifikasi_at'   => null,
                    'diunggah_at'       => now(),
                ]
            );
        } catch (Throwable $e) {
            // Data gagal tersimpan: buang berkas baru, berkas lama tetap utuh
            $disk->delete($path);
            throw $e;
        }

        // Berkas lama baru dihapus setelah data baru berhasil disimpan
        if ($dokumenLama && $dokumenLama->path && $dokumenLama->path !== $path) {
            $disk->delete($dokumenLama->path);
        }

        return back()->with('success', 'Dokumen berhasil diunggah.');
    }

    public function destroy(Request $request, DokumenMahasiswa $dokumen)
    {
        if ($dokumen->user_id !== $request->user()->id) {
            abort(403);
        }

        if ($dokumen->status === DokumenMahasiswa::DISETUJUI) {
            return back()->with('error', 'Dokumen yang sudah disetujui tidak dapat dihapus.');
        }

        if ($dokumen->path) {
            Storage::disk('local')->delete($dokumen->path);
        }

        $dokumen->delete();

        return back()->with('success', 'Dokumen berhasil dihapus.');
    }
}