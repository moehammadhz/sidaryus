@extends('layouts.admin')

@php
    use App\Models\JadwalPendadaran as J;
    $j = $jadwal;
    $t = $j->tahap();
    $labelTahap = [
        'menunggu_bayar' => 'Menunggu bukti bayar', 'cek_bayar' => 'Periksa bukti bayar', 'bayar_ditolak' => 'Bukti bayar ditolak', 'pending' => 'Verifikasi jadwal', 'ditolak' => 'Ditolak', 'perlu_undangan' => 'Perlu undangan',
        'menunggu_ujian' => 'Menunggu ujian', 'perlu_nilai' => 'Perlu nilai', 'lulus' => 'Lulus', 'tidak_lulus' => 'Tidak Lulus',
    ];
    $gambar = $j->bukti_bayar_mime && str_starts_with($j->bukti_bayar_mime, 'image/');
    $dosen = array_filter([
        'nilai_pembimbing_1' => ['Pembimbing Utama', $j->pembimbing_1],
        'nilai_pembimbing_2' => $j->pembimbing_2 ? ['Pembimbing Pendamping', $j->pembimbing_2] : null,
        'nilai_penguji'      => ['Penguji', $j->penguji],
    ]);
@endphp

@section('title', 'Jadwal ' . $j->nama)
@section('crumb', 'Jadwal Pendadaran')
@section('page_title', 'Jadwal Pendadaran: ' . $j->nama)

@section('actions')
    <a class="btn btn--ghost" href="{{ route('admin.jadwal.index') }}">Kembali ke daftar</a>
@endsection

@push('styles')
<style>
    .alert { margin-bottom: 16px; padding: 12px 16px; border-radius: 12px; background: #dcfce7; color: #166534; font-size: 14px; font-weight: 600; }
    .alert--error { background: #fee2e2; color: #991b1b; }
    .alert--warn { background: #fef3c7; color: #92400e; font-weight: 500; }
    .grid { display: grid; grid-template-columns: minmax(0, 1.1fr) minmax(0, 1fr); gap: 18px; align-items: start; }
    .col { display: flex; flex-direction: column; gap: 18px; min-width: 0; }
    .panel { background: #fff; border: 1px solid var(--line); border-radius: 16px; padding: 22px; }
    .panel h2 { font-size: 16px; font-weight: 800; color: var(--navy); display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap; }
    .panel .sub { margin-top: 4px; font-size: 13px; color: var(--muted); }
    .step { display: inline-grid; place-items: center; width: 24px; height: 24px; margin-right: 8px; border-radius: 50%; background: #e3eefc; color: var(--primary); font-size: 12px; font-weight: 800; }
    .dl { margin-top: 14px; display: grid; grid-template-columns: 150px minmax(0, 1fr); gap: 10px 16px; font-size: 14px; }
    .dl dt { color: var(--muted); font-weight: 500; }
    .dl dd { margin: 0; color: var(--ink); font-weight: 600; }
    .judul { font-style: italic; }
    .pill { display: inline-block; padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 700; }
    .pill--ok { background: #dcfce7; color: #166534; } .pill--warn { background: #fef3c7; color: #92400e; }
    .pill--bad { background: #fee2e2; color: #991b1b; } .pill--info { background: #e0f2fe; color: #0369a1; }
    .pill--mute { background: #f1f4f9; color: #4b5563; } .pill--ungu { background: #ede9fe; color: #5b21b6; }
    .file { margin-top: 14px; display: flex; align-items: center; gap: 12px; padding: 12px 14px; border: 1px solid var(--line); border-radius: 12px; background: #f8fafc; }
    .file__name { flex: 1; min-width: 0; font-size: 13.5px; font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .file__meta { font-size: 12px; color: var(--muted); font-weight: 500; }
    .preview { margin-top: 12px; max-height: 320px; width: 100%; object-fit: contain; border: 1px solid var(--line); border-radius: 12px; background: #f8fafc; }
    .actions { margin-top: 16px; display: flex; flex-wrap: wrap; gap: 10px; align-items: flex-start; }
    .field { margin-top: 14px; }
    .field label { display: block; margin-bottom: 6px; font-size: 13px; font-weight: 600; color: #1f2937; }
    .field .hint { margin-top: 4px; font-size: 12px; color: var(--muted); }
    .input, .textarea {
        width: 100%; padding: 10px 14px; border: 1.5px solid #d5dbe5; border-radius: 12px; background: #f6f8fb;
        font: 500 14px var(--font); color: var(--ink); outline: 0;
    }
    .input { height: 46px; }
    .input:focus, .textarea:focus { border-color: var(--primary); background: #fff; box-shadow: 0 0 0 3px rgba(43,114,213,.15); }
    .is-invalid { border-color: var(--danger) !important; }
    .field-error { margin-top: 6px; font-size: 12.5px; color: var(--danger); }
    .nilai-grid { margin-top: 6px; display: grid; gap: 12px; }
    .nilai-row { display: grid; grid-template-columns: minmax(0, 1fr) 120px; gap: 12px; align-items: center; }
    .nilai-row .who { font-size: 13.5px; font-weight: 600; color: var(--ink); }
    .nilai-row .who span { display: block; font-size: 11.5px; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: .04em; }
    .ringkas { margin-top: 16px; padding: 14px 16px; border-radius: 12px; background: #f6f8fb; display: flex; flex-wrap: wrap; gap: 8px 24px; align-items: center; font-size: 14px; }
    .ringkas b { font-size: 22px; color: var(--navy); font-variant-numeric: tabular-nums; }
    .huruf { display: inline-grid; place-items: center; width: 30px; height: 30px; border-radius: 8px; background: #e3eefc; color: var(--primary); font-weight: 800; }
    .riwayat { margin-top: 10px; font-size: 13.5px; display: flex; flex-direction: column; gap: 6px; }
    .riwayat a { color: var(--primary); font-weight: 600; }
    .cek { margin-top: 12px; padding: 10px 14px; border-radius: 12px; font-size: 13.5px; }
    .cek--ok { background: #dcfce7; color: #166534; } .cek--bad { background: #fee2e2; color: #991b1b; }
    .cek ul { margin: 6px 0 0 18px; }
    .riwayat-bukti { margin-top: 16px; padding-top: 14px; border-top: 1px dashed var(--line); }
    .riwayat-bukti h3 { font-size: 13.5px; font-weight: 800; color: var(--navy); }
    .riwayat-bukti ol { margin: 8px 0 0 18px; display: flex; flex-direction: column; gap: 10px; font-size: 13px; color: #374151; }
    .riwayat-bukti a { color: var(--primary); font-weight: 600; word-break: break-all; }
    @media (max-width: 960px) { .grid { grid-template-columns: 1fr; } }
    @media (max-width: 520px) { .dl { grid-template-columns: 1fr; gap: 2px; } .dl dd { margin-bottom: 8px; } .panel { padding: 16px; } }
</style>
@endpush

@section('content')
    @if (session('status'))
        <div class="alert" role="status">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert--error" role="alert">{{ session('error') }}</div>
    @endif

    <div class="grid">
        <div class="col">
            <section class="panel">
                <h2>Data jadwal
                    <span class="pill pill--{{ ['lulus' => 'ok', 'tidak_lulus' => 'bad', 'ditolak' => 'bad', 'pending' => 'warn', 'cek_bayar' => 'warn', 'menunggu_bayar' => 'bad', 'bayar_ditolak' => 'bad', 'perlu_undangan' => 'info', 'perlu_nilai' => 'ungu'][$t] ?? 'mute' }}">{{ $labelTahap[$t] ?? $t }}</span>
                </h2>
                <dl class="dl">
                    <dt>Mahasiswa</dt><dd>{{ $j->nama }} · {{ $j->nim }}</dd>
                    @if ($j->user)
                        <dt>Program studi</dt><dd>{{ $j->user->program_studi ?? '-' }}</dd>
                    @endif
                    <dt>Judul</dt><dd class="judul">{{ $j->judul }}</dd>
                    <dt>Pembimbing Utama</dt><dd>{{ $j->pembimbing_1 }}</dd>
                    <dt>Pembimbing Pendamping</dt><dd>{{ $j->pembimbing_2 ?? '-' }}</dd>
                    <dt>Penguji</dt><dd>{{ $j->penguji }}</dd>
                    <dt>Waktu ujian</dt><dd>{{ $j->hariIndo() }}, {{ $j->tanggalIndo() }} · {{ $j->jamWib() }}</dd>
                </dl>
                @if ($riwayat->isNotEmpty())
                    <div class="riwayat">
                        <span class="sub">Ujian lain mahasiswa ini:</span>
                        @foreach ($riwayat as $r)
                            <span><a href="{{ route('admin.jadwal.show', $r) }}">{{ $r->tanggalIndo() }}</a> · {{ ucfirst($r->status) }}{{ $r->alasan_tolak ? ' (' . $r->alasan_tolak . ')' : '' }}{{ $r->hasil ? ' · Hasil ujian: ' . $r->labelHasil() . ' (' . J::formatNilai($r->nilai_akhir) . ')' : '' }}</span>
                        @endforeach
                    </div>
                @endif
            </section>

            {{-- 1a. Bukti pembayaran --}}
            @php
                $statusBukti = ! $j->bukti_bayar_path ? ['Belum diunggah', 'bad']
                    : ['menunggu' => ['Menunggu pemeriksaan', 'warn'], 'ditolak' => ['Ditolak, menunggu unggah ulang', 'bad'], 'disetujui' => ['Disetujui', 'ok']][$j->bukti_bayar_status ?? 'menunggu'];
                $bisaPeriksaBukti = $j->status !== J::DISETUJUI && $j->buktiMenunggu();
            @endphp
            <section class="panel" id="bukti">
                <h2><span><span class="step">1</span>Bukti pembayaran</span>
                    <span class="pill pill--{{ $statusBukti[1] }}">{{ $statusBukti[0] }}</span>
                </h2>

                @if ($j->bukti_bayar_path)
                    <div class="file">
                        <x-icon name="file" />
                        <div class="file__name" title="{{ $j->bukti_bayar_nama }}">{{ $j->bukti_bayar_nama }}
                            <div class="file__meta">{{ $j->buktiDitolak() ? 'Bukti yang ditolak' : 'Bukti terbaru' }} · {{ $gambar ? 'Gambar' : 'PDF' }}</div></div>
                        <a class="btn btn--ghost btn--sm" href="{{ route('admin.jadwal.bukti-bayar', $j) }}" target="_blank" rel="noopener">Buka</a>
                    </div>
                    @if ($gambar)
                        <img class="preview" src="{{ route('admin.jadwal.bukti-bayar', $j) }}" alt="Bukti bayar {{ $j->nama }}">
                    @endif
                @else
                    <div class="cek cek--bad">Bukti pembayaran belum diunggah{{ $j->user ? ' oleh mahasiswa (menu Upload Bukti Pembayaran)' : ' (jadwal lama tanpa akun mahasiswa)' }}.</div>
                @endif

                @if ($j->buktiDitolak())
                    <div class="cek cek--bad">Alasan: {{ $j->bukti_bayar_alasan }}<br>Mahasiswa sudah diberi tahu untuk mengunggah ulang. Jadwal tetap menunggu.</div>
                @endif

                @if ($bisaPeriksaBukti)
                    <div class="actions" style="margin-top:12px">
                        <form method="POST" action="{{ route('admin.jadwal.setuju-bukti', $j) }}">
                            @csrf @method('PATCH')
                            <button class="btn btn--primary" type="submit">Setujui bukti bayar</button>
                        </form>
                    </div>
                    <form method="POST" action="{{ route('admin.jadwal.tolak-bukti', $j) }}" novalidate>
                        @csrf @method('PATCH')
                        <div class="field">
                            <label for="alasan_bukti">Bukti tidak sah? Tolak bukti bayar</label>
                            <textarea class="textarea @if ($errors->tolakBukti->has('alasan_bukti')) is-invalid @endif" id="alasan_bukti" name="alasan_bukti" rows="2" maxlength="500"
                                      placeholder="Contoh: Nominal tidak sesuai / gambar buram, mohon unggah ulang.">{{ old('alasan_bukti') }}</textarea>
                            <p class="hint">Jadwal tidak ikut ditolak; mahasiswa cukup mengunggah ulang bukti bayar.</p>
                            @if ($errors->tolakBukti->has('alasan_bukti')) <p class="field-error" role="alert">{{ $errors->tolakBukti->first('alasan_bukti') }}</p> @endif
                        </div>
                        <div class="actions" style="margin-top:10px"><button class="btn btn--danger" type="submit">Tolak bukti bayar</button></div>
                    </form>
                @endif

                @if ($j->riwayatBuktiBayar->isNotEmpty())
                    <div class="riwayat-bukti">
                        <h3>Riwayat penolakan bukti bayar</h3>
                        <ol>
                            @foreach ($j->riwayatBuktiBayar as $r)
                                <li>
                                    <div><b>{{ $r->ditolak_at->locale('id')->translatedFormat('d M Y H:i') }}</b> · {{ $r->penolak?->name ?? '-' }}</div>
                                    <div>{{ $r->alasan }}</div>
                                    <a href="{{ route('admin.jadwal.riwayat-bukti', $r) }}" target="_blank" rel="noopener">{{ $r->nama }}</a>
                                </li>
                            @endforeach
                        </ol>
                    </div>
                @endif
            </section>

            {{-- 1b. Verifikasi jadwal --}}
            <section class="panel" id="verifikasi">
                <h2><span><span class="step">2</span>Verifikasi jadwal</span></h2>
                <p class="sub">Tombol setujui jadwal muncul setelah bukti pembayaran disetujui dan dokumen lengkap.</p>

                @if ($j->user)
                    @if ($belumLengkap->isEmpty())
                        <div class="cek cek--ok">Semua dokumen persyaratan sudah disetujui.</div>
                    @else
                        <div class="cek cek--bad">Dokumen yang belum disetujui:
                            <ul>@foreach ($belumLengkap as $s) <li>{{ $s->nama }}</li> @endforeach</ul>
                        </div>
                    @endif
                @endif

                @if ($j->status === J::PENDING)
                    @if ($j->user && ! $j->buktiDisetujui())
                        <div class="cek cek--bad">Belum bisa disetujui: {{ ! $j->bukti_bayar_path ? 'bukti pembayaran belum diunggah' : ($j->buktiDitolak() ? 'bukti pembayaran ditolak dan menunggu unggah ulang' : 'setujui bukti pembayaran di langkah 1 terlebih dahulu') }}.</div>
                    @else
                        <div class="actions">
                            <form method="POST" action="{{ route('admin.jadwal.setuju', $j) }}">
                                @csrf @method('PATCH')
                                <button class="btn btn--primary" type="submit">Setujui jadwal</button>
                            </form>
                        </div>
                    @endif
                    <form method="POST" action="{{ route('admin.jadwal.tolak', $j) }}" novalidate>
                        @csrf @method('PATCH')
                        <div class="field">
                            <label for="alasan_tolak">Masalah pada jadwal? Tolak jadwal</label>
                            <textarea class="textarea @if ($errors->tolak->has('alasan_tolak')) is-invalid @endif" id="alasan_tolak" name="alasan_tolak" rows="2" maxlength="500"
                                      placeholder="Contoh: Tanggal bertepatan dengan rapat fakultas, pilih tanggal lain.">{{ old('alasan_tolak') }}</textarea>
                            <p class="hint">Mahasiswa memperbaiki pengajuan yang sama (tidak membuat data baru); bukti bayar tetap dipakai.</p>
                            @if ($errors->tolak->has('alasan_tolak')) <p class="field-error" role="alert">{{ $errors->tolak->first('alasan_tolak') }}</p> @endif
                        </div>
                        <div class="actions" style="margin-top:10px"><button class="btn btn--danger" type="submit">Tolak jadwal</button></div>
                    </form>
                @else
                    <dl class="dl">
                        <dt>Status</dt><dd>{{ ucfirst($j->status) }}</dd>
                        @if ($j->alasan_tolak) <dt>Alasan</dt><dd>{{ $j->alasan_tolak }}</dd> @endif
                        @if ($j->diverifikasi_at)
                            <dt>Diverifikasi</dt><dd>{{ $j->verifikator?->name ?? '-' }}, {{ $j->diverifikasi_at->locale('id')->translatedFormat('d F Y H:i') }}</dd>
                        @endif
                    </dl>
                @endif

                @if ($j->riwayatPenolakan->isNotEmpty())
                    <div class="riwayat-bukti">
                        <h3>Riwayat penolakan jadwal</h3>
                        <ol>
                            @foreach ($j->riwayatPenolakan as $rp)
                                <li>
                                    <div><b>{{ $rp->ditolak_at->locale('id')->translatedFormat('d M Y H:i') }}</b> · {{ $rp->penolak?->name ?? '-' }}</div>
                                    <div>{{ $rp->alasan ?? '-' }}</div>
                                    <div style="color:var(--muted)">Diajukan: {{ $rp->waktu() }} · {{ $rp->pembimbing_1 }}, {{ $rp->pembimbing_2 ?? '-' }}, {{ $rp->penguji }}</div>
                                </li>
                            @endforeach
                        </ol>
                    </div>
                @endif
            </section>
        </div>

        <div class="col">
            {{-- 2. Undangan --}}
            <section class="panel" id="undangan">
                <h2><span><span class="step">3</span>Undangan jadwal ujian</span>
                    @if ($j->undangan_path) <span class="pill pill--ok">Sudah diunggah</span> @endif
                </h2>
                @if ($j->status !== J::DISETUJUI)
                    <p class="sub">Undangan bisa diunggah setelah jadwal disetujui.</p>
                @else
                    <p class="sub">Unggah undangan resmi (PDF yang sudah ditandatangani). Mahasiswa mengunduhnya di menu Undangan Jadwal Ujian.</p>
                    @if ($j->undangan_path)
                        <div class="file">
                            <x-icon name="file" />
                            <div class="file__name" title="{{ $j->undangan_nama }}">{{ $j->undangan_nama }}
                                <div class="file__meta">Diunggah {{ $j->undangan_at?->locale('id')->translatedFormat('d F Y H:i') }}</div></div>
                            <a class="btn btn--ghost btn--sm" href="{{ route('admin.jadwal.undangan', $j) }}" target="_blank" rel="noopener">Buka</a>
                        </div>
                    @endif
                    <form method="POST" action="{{ route('admin.jadwal.undangan.store', $j) }}" enctype="multipart/form-data" novalidate>
                        @csrf
                        <div class="field">
                            <label for="undangan">{{ $j->undangan_path ? 'Ganti undangan (PDF)' : 'Berkas undangan (PDF)' }}</label>
                            <input class="input @if ($errors->undangan->has('undangan')) is-invalid @endif" style="padding-top:10px" id="undangan" name="undangan" type="file" accept=".pdf,application/pdf" required>
                            <p class="hint">Maksimal 5 MB.</p>
                            @if ($errors->undangan->has('undangan')) <p class="field-error" role="alert">{{ $errors->undangan->first('undangan') }}</p> @endif
                        </div>
                        <div class="actions"><button class="btn btn--primary" type="submit"><x-icon name="upload" :size="16" /> {{ $j->undangan_path ? 'Ganti undangan' : 'Unggah undangan' }}</button></div>
                    </form>
                @endif
            </section>

            {{-- 3. Nilai --}}
            <section class="panel" id="nilai">
                <h2><span><span class="step">4</span>Nilai & hasil ujian</span>
                    @if ($j->hasil) <span class="pill pill--{{ $j->lulus() ? 'ok' : 'bad' }}">{{ $j->labelHasil() }}</span> @endif
                </h2>
                @if ($j->status !== J::DISETUJUI)
                    <p class="sub">Nilai bisa diinput setelah jadwal disetujui dan ujian berlangsung.</p>
                @elseif (! $j->sudahUjian())
                    <p class="sub">Ujian dijadwalkan {{ $j->hariIndo() }}, {{ $j->tanggalIndo() }}. Nilai bisa diinput mulai hari itu.</p>
                @else
                    <p class="sub">Masukkan nilai yang diserahkan setiap dosen. Nilai akhir adalah rata-rata; lulus jika minimal {{ J::BATAS_LULUS }}.</p>

                    @if ($j->hasil)
                        <div class="ringkas">
                            <span>Nilai akhir <b>{{ J::formatNilai($j->nilai_akhir) }}</b></span>
                            <span class="huruf">{{ $j->hurufAkhir() }}</span>
                            <span>{{ $j->labelHasil() }}</span>
                            <span class="sub" style="margin:0">Diinput {{ $j->penginputHasil?->name ?? '-' }}, {{ $j->hasil_at?->locale('id')->translatedFormat('d F Y H:i') }}</span>
                        </div>
                    @endif

                    @if ($j->hasilTerkunci())
                        <div class="cek cek--ok">Nilai terkunci: mahasiswa sudah terdaftar di {{ $j->user->periode?->nama }}. Lepas pendaftarannya di menu Data Yudisium jika nilai perlu diralat.</div>
                    @else
                        <form method="POST" action="{{ route('admin.jadwal.nilai', $j) }}" novalidate id="form-nilai">
                            @csrf @method('PUT')
                            <div class="nilai-grid field">
                                @foreach ($dosen as $kolom => [$peran, $nama])
                                    <div>
                                        <div class="nilai-row">
                                            <label class="who" for="{{ $kolom }}"><span>{{ $peran }}</span>{{ $nama }}</label>
                                            <input class="input @if ($errors->nilai->has($kolom)) is-invalid @endif" id="{{ $kolom }}" name="{{ $kolom }}" type="text" inputmode="decimal"
                                                   value="{{ old($kolom, $j->$kolom !== null ? J::formatNilai($j->$kolom) : '') }}" placeholder="0–100" data-nilai required>
                                        </div>
                                        @if ($errors->nilai->has($kolom)) <p class="field-error" role="alert">{{ $errors->nilai->first($kolom) }}</p> @endif
                                    </div>
                                @endforeach
                            </div>
                            <div class="ringkas" aria-live="polite">
                                <span>Pratinjau nilai akhir <b id="pv-nilai">-</b></span>
                                <span class="huruf" id="pv-huruf">-</span>
                                <span id="pv-hasil" class="pill pill--mute">-</span>
                            </div>
                            <div class="actions"><button class="btn btn--primary" type="submit">{{ $j->hasil ? 'Simpan ralat nilai' : 'Simpan nilai' }}</button></div>
                        </form>
                    @endif
                @endif
            </section>
        </div>
    </div>
@endsection

@push('scripts')
<script>
(function () {
    var form = document.getElementById('form-nilai');
    if (!form) return;
    var batas = {{ J::BATAS_LULUS }};
    var konversi = @json(J::KONVERSI_HURUF);
    var inputs = form.querySelectorAll('[data-nilai]');

    function huruf(n) { for (var i = 0; i < konversi.length; i++) if (n >= konversi[i][0]) return konversi[i][1]; return 'E'; }

    /* Pratinjau rata-rata saat petugas mengetik; perhitungan resmi tetap di server */
    function hitung() {
        var total = 0, n = 0, lengkap = true;
        inputs.forEach(function (el) {
            var v = parseFloat(el.value.replace(',', '.'));
            if (isNaN(v) || v < 0 || v > 100) { lengkap = false; return; }
            total += v; n++;
        });
        var pv = document.getElementById('pv-nilai'), ph = document.getElementById('pv-huruf'), ps = document.getElementById('pv-hasil');
        if (!lengkap || n === 0) { pv.textContent = '-'; ph.textContent = '-'; ps.textContent = 'Lengkapi semua nilai'; ps.className = 'pill pill--mute'; return; }
        var akhir = Math.round(total / n * 100) / 100;
        pv.textContent = akhir.toLocaleString('id-ID');
        ph.textContent = huruf(akhir);
        ps.textContent = akhir >= batas ? 'Lulus' : 'Tidak Lulus';
        ps.className = 'pill ' + (akhir >= batas ? 'pill--ok' : 'pill--bad');
    }
    inputs.forEach(function (el) { el.addEventListener('input', hitung); });
    hitung();
})();
</script>
@endpush
