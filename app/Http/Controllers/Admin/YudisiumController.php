<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\JadwalPendadaran;
use App\Models\Periode;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Daftar mahasiswa yang sudah lulus pendadaran, terisi otomatis dari hasil ujian. */
class YudisiumController extends Controller
{
    private const PER_HALAMAN = 15;

    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q'));
        $periodeParam = (string) $request->query('periode');
        $filter = $periodeParam === 'belum' || ctype_digit($periodeParam) ? $periodeParam : '';

        $query = JadwalPendadaran::query()
            ->where('hasil', JadwalPendadaran::LULUS)
            ->whereHas('user', fn (Builder $u) => $u->where('role', Role::Mahasiswa->value))
            ->with('user.periode')
            ->cari($q);

        if ($filter === 'belum') {
            $query->whereHas('user', fn (Builder $u) => $u->whereNull('periode_id'));
        } elseif ($filter !== '') {
            $query->whereHas('user', fn (Builder $u) => $u->where('periode_id', (int) $filter));
        }

        $semua = JadwalPendadaran::where('hasil', JadwalPendadaran::LULUS)->whereNotNull('user_id');

        return view('admin.yudisium', [
            'daftar'  => $query->orderByDesc('hasil_at')->orderByDesc('id')->paginate(self::PER_HALAMAN)->withQueryString(),
            'periode' => Periode::orderByDesc('dibuka')->orderBy('id')->get(),
            'q'       => $q,
            'filter'  => $filter,
            'ringkas' => [
                'lulus' => (clone $semua)->count(),
                'belum' => (clone $semua)->whereHas('user', fn (Builder $u) => $u->whereNull('periode_id'))->count(),
            ],
        ]);
    }

    /** Tetapkan atau pindahkan periode yudisium mahasiswa (termasuk ke periode yang sudah ditutup) */
    public function ubahPeriode(Request $request, User $mahasiswa): RedirectResponse
    {
        abort_unless($mahasiswa->role === Role::Mahasiswa, 404);

        if (! $mahasiswa->jadwalLulus()) {
            return back()->with('error', "{$mahasiswa->name} belum lulus ujian pendadaran.");
        }

        $data = $request->validate(
            ['periode_id' => ['required', 'integer', 'exists:periode,id']],
            ['periode_id.required' => 'Pilih periode yudisium.', 'periode_id.exists' => 'Periode tidak ditemukan.'],
        );
        $periode = Periode::findOrFail($data['periode_id']);

        if ($mahasiswa->periode_id === $periode->id) {
            return back()->with('status', "{$mahasiswa->name} sudah terdaftar di {$periode->nama}.");
        }

        $lama = $mahasiswa->periode?->nama;
        $mahasiswa->forceFill(['periode_id' => $periode->id, 'periode_daftar_at' => $mahasiswa->periode_daftar_at ?? now()])->save();

        return back()->with('status', $lama
            ? "{$mahasiswa->name} dipindahkan dari {$lama} ke {$periode->nama}."
            : "{$mahasiswa->name} didaftarkan ke {$periode->nama}.");
    }

    /** Lepas pendaftaran periode, misalnya karena salah daftar atau nilai perlu diralat */
    public function lepas(User $mahasiswa): RedirectResponse
    {
        abort_unless($mahasiswa->role === Role::Mahasiswa, 404);

        if (! $mahasiswa->periode_id) {
            return back()->with('error', "{$mahasiswa->name} belum terdaftar di periode mana pun.");
        }

        $nama = $mahasiswa->periode?->nama;
        $mahasiswa->forceFill(['periode_id' => null, 'periode_daftar_at' => null])->save();

        return back()->with('status', "{$mahasiswa->name} dilepas dari {$nama}. Mahasiswa bisa mendaftar lagi ke periode yang dibuka dari dashboard.");
    }
}
