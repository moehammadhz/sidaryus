<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\DokumenMahasiswa;
use App\Models\Persyaratan;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DokumenController extends Controller
{
    private const PER_HALAMAN = 10;

    /** Kunci urutan di URL => kolom database */
    private const URUT = [
        'name'          => 'name',
        'username'      => 'username',
        'program_studi' => 'program_studi',
        'angkatan'      => 'angkatan',
        'kelas'         => 'kelas',
        'timestamp'     => 'dokumen_max_diunggah_at',
        'progress'      => 'disetujui_count',
    ];

    public function index(Request $request): View
    {
        [$f, $sort, $dir] = $this->parameter($request);

        return view('admin.dokumen.index', [
            'mahasiswa'   => $this->kueri($f, $sort, $dir)
                ->with('dokumen.verifikator')
                ->paginate(self::PER_HALAMAN)
                ->withQueryString(),
            'f'           => $f,
            'sort'        => $sort,
            'dir'         => $dir,
            'syarat'      => Persyaratan::orderBy('urutan')->orderBy('id')->get(),
            'totalSyarat' => Persyaratan::count(),
            'angkatan'    => User::where('role', Role::Mahasiswa->value)
                ->whereNotNull('angkatan')->distinct()->orderBy('angkatan')->pluck('angkatan'),
        ]);
    }

    public function show(User $mahasiswa): View
    {
        abort_unless($mahasiswa->role === Role::Mahasiswa, 404);

        $syarat  = Persyaratan::orderBy('urutan')->orderBy('id')->get();
        $dokumen = $mahasiswa->dokumen()->get()->keyBy('persyaratan_id');

        return view('admin.dokumen.show', [
            'mahasiswa'  => $mahasiswa,
            'syarat'     => $syarat,
            'dokumen'    => $dokumen,
            'disetujui'  => $dokumen->where('status', DokumenMahasiswa::DISETUJUI)->count(),
            'menunggu'   => $dokumen->where('status', DokumenMahasiswa::MENUNGGU)->count(),
        ]);
    }

    /** Menampilkan berkas milik mahasiswa (hanya untuk admin, tidak lewat URL publik) */
    public function berkas(DokumenMahasiswa $dokumen): Response
    {
        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('local');

        abort_unless($disk->exists($dokumen->path), 404, 'Berkas tidak ditemukan.');

        return $disk->response(
            $dokumen->path,
            $dokumen->nama_asli,
            ['Content-Type' => $dokumen->mime, 'X-Content-Type-Options' => 'nosniff'],
            'inline'
        );
    }

    public function setuju(Request $request, DokumenMahasiswa $dokumen): RedirectResponse
    {
        $dokumen->update([
            'status'            => DokumenMahasiswa::DISETUJUI,
            'catatan'           => null,
            'diverifikasi_oleh' => $request->user()->id,
            'diverifikasi_at'   => now(),
        ]);

        return back()
            ->with('status', 'Dokumen ' . $dokumen->persyaratan->kode . ' disetujui.')
            ->with('modal', $dokumen->user_id);
    }

    public function tolak(Request $request, DokumenMahasiswa $dokumen): RedirectResponse
    {
        $data = $request->validate(
            ['catatan' => ['required', 'string', 'max:255']],
            ['catatan.required' => 'Alasan penolakan wajib diisi.', 'catatan.max' => 'Alasan penolakan maksimal 255 karakter.']
        );

        $dokumen->update([
            'status'            => DokumenMahasiswa::DITOLAK,
            'catatan'           => trim($data['catatan']),
            'diverifikasi_oleh' => $request->user()->id,
            'diverifikasi_at'   => now(),
        ]);

        return back()
            ->with('status', 'Dokumen ' . $dokumen->persyaratan->kode . ' ditolak.')
            ->with('modal', $dokumen->user_id);
    }

    public function setujuSemua(Request $request, User $mahasiswa): RedirectResponse
    {
        abort_unless($mahasiswa->role === Role::Mahasiswa, 404);

        $jumlah = $mahasiswa->dokumen()
            ->where('status', DokumenMahasiswa::MENUNGGU)
            ->update([
                'status'            => DokumenMahasiswa::DISETUJUI,
                'catatan'           => null,
                'diverifikasi_oleh' => $request->user()->id,
                'diverifikasi_at'   => now(),
            ]);

        return back()
            ->with('status', $jumlah > 0
                ? "{$jumlah} dokumen yang menunggu berhasil disetujui."
                : 'Tidak ada dokumen yang menunggu verifikasi.')
            ->with('modal', $mahasiswa->id);
    }

    /** Ekspor ke CSV (terbuka di Excel), mengikuti filter dan urutan yang aktif */
    public function ekspor(Request $request): StreamedResponse
    {
        [$f, $sort, $dir] = $this->parameter($request);
        $total    = Persyaratan::count();
        $namaFile = 'dokumen-mahasiswa-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($f, $sort, $dir, $total) {
            $out = fopen('php://output', 'w');

            fwrite($out, "\xEF\xBB\xBF");
            fwrite($out, "sep=;\r\n");

            fputcsv($out, ['No', 'Mahasiswa', 'NIM', 'Prodi', 'Angkatan', 'Kelas', 'Timestamp', 'Progress'], ';', '"', '\\');

            $no = 0;
            $this->kueri($f, $sort, $dir)->lazy(200)->each(function (User $m) use ($out, &$no, $total) {
                $waktu = $m->dokumen_max_diunggah_at
                    ? Carbon::parse($m->dokumen_max_diunggah_at)->format('d/m/Y H:i')
                    : '-';

                fputcsv($out, array_map([$this, 'aman'], [
                    ++$no, $m->name, $m->username, $m->program_studi, $m->angkatan, $m->kelas,
                    $waktu, "{$m->disetujui_count}/{$total}",
                ]), ';', '"', '\\');
            });

            fclose($out);
        }, $namaFile, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** @return array{0: array<string, mixed>, 1: string, 2: string} */
    private function parameter(Request $request): array
    {
        $angkatan = $request->query('angkatan');
        $prodi    = (string) $request->query('prodi');

        $f = [
            'q'        => trim((string) $request->query('q')),
            'prodi'    => in_array($prodi, User::PROGRAM_STUDI, true) ? $prodi : '',
            'angkatan' => (is_string($angkatan) && ctype_digit($angkatan)) ? (int) $angkatan : '',
        ];

        $sort = array_key_exists((string) $request->query('sort'), self::URUT)
            ? (string) $request->query('sort')
            : 'name';
        $dir = $request->query('dir') === 'desc' ? 'desc' : 'asc';

        return [$f, $sort, $dir];
    }

    private function kueri(array $f, string $sort, string $dir): Builder
    {
        $query = User::query()
            ->where('role', Role::Mahasiswa->value)
            ->withCount(['dokumen as disetujui_count' => fn ($w) => $w->where('status', DokumenMahasiswa::DISETUJUI)])
            ->withMax('dokumen', 'diunggah_at');

        if ($f['q'] !== '') {
            $like = '%' . addcslashes($f['q'], '%_\\') . '%';
            $query->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('username', 'like', $like));
        }
        if ($f['prodi'] !== '') {
            $query->where('program_studi', $f['prodi']);
        }
        if ($f['angkatan'] !== '') {
            $query->where('angkatan', $f['angkatan']);
        }
        $query->orderBy(self::URUT[$sort], $dir);

        return $query->orderBy('users.id');
    }

    /** Cegah rumus Excel dari data yang diketik pengguna (CSV injection) */
    private function aman(mixed $nilai): mixed
    {
        if (is_string($nilai) && $nilai !== '' && in_array($nilai[0], ['=', '+', '-', '@'], true)) {
            return "'" . $nilai;
        }

        return $nilai;
    }
}