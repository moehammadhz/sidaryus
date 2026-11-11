<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KontenDashboard;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class KontenController extends Controller
{
    public function edit(): View
    {
        return view('admin.konten', ['konten' => KontenDashboard::semua()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $rules = [];
        foreach (KontenDashboard::KUNCI as $kunci) {
            $rules[$kunci] = ['required', 'string', 'max:4000'];
        }

        $request->validate($rules, ['required' => 'Isi :attribute tidak boleh kosong.'], KontenDashboard::JUDUL);

        foreach (KontenDashboard::KUNCI as $kunci) {
            // satu poin per baris; baris kosong dibuang
            $items = collect(preg_split('/\R/', (string) $request->input($kunci)))
                ->map(fn ($baris) => trim($baris))
                ->filter()
                ->values()
                ->all();

            KontenDashboard::updateOrCreate(['kunci' => $kunci], ['items' => $items]);
        }

        return redirect()->route('admin.konten')->with('status', 'Konten berhasil disimpan.');
    }
}