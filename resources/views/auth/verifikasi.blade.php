@extends('layouts.auth')

@section('title', 'Verifikasi WhatsApp')
@section('stage_class', 'stage--compact')
@section('mobile_title', 'Verifikasi')

@section('form')
<div class="otp">
    <p class="otp__info">Masukkan 6 digit kode yang kami kirim ke WhatsApp <b>{{ $nomor }}</b>. Kode berlaku {{ \App\Models\OtpCode::MASA_BERLAKU_MENIT }} menit.</p>

    @if (session('error'))
        <p class="error" role="alert" style="text-align:center">{{ session('error') }}</p>
    @endif

    @if (session('warning'))
        <p class="otp__warn" role="alert">{{ session('warning') }}</p>
    @endif

    @if (session('status'))
        <p class="otp__ok" role="status">{{ session('status') }}</p>
    @endif

    @if ($kodeUji)
        {{-- Hanya muncul jika environment local / mode uji coba --}}
        <div class="otp__uji" role="note">
            <b>Mode uji coba</b> · Kode OTP Anda: <span class="otp__kode">{{ $kodeUji }}</span>
        </div>
    @endif

    <div id="otp-status-banner" class="otp__status-banner" style="display:none;">
        <span class="spinner-dot" aria-hidden="true"></span>
        <span>OTP sedang dikirim (<span id="hitung-mundur-teks">60</span> dtk)</span>
    </div>

    <form method="POST" action="{{ route('register.konfirmasi') }}" style="display:contents" novalidate>
        @csrf
        <div class="fields fields--compact">
            <x-field name="kode" label="Kode verifikasi" placeholder="Kode 6 digit" autocomplete="one-time-code" :digits="6" :autofocus="true" />
        </div>
        <button class="btn btn--tight" type="submit">Verifikasi</button>
    </form>

    <div class="otp__actions">
        <form method="POST" action="{{ route('register.kirim-ulang') }}" class="otp__ulang">
            @csrf
            <span>Tidak menerima kode?</span>
            <button type="submit" id="btn-ulang" data-jeda="{{ $cooldown ?? 60 }}">Kirim ulang kode</button>
        </form>

        <p class="alt" style="margin-top:10px">
            Nomor salah? <a href="#ganti-nomor" id="toggle-ganti-nomor">Ganti nomor WhatsApp</a>
        </p>

        {{-- Form Ganti Nomor WhatsApp (khusus akun pending) --}}
        <div id="box-ganti-nomor" class="otp__ganti-box" style="display:none;">
            <form method="POST" action="{{ route('register.ganti-nomor') }}">
                @csrf
                <div class="fields fields--compact">
                    <x-field name="no_wa_baru" label="Nomor WhatsApp baru" type="tel" placeholder="08xxxxxxxxxx" autocomplete="tel" :value="old('no_wa_baru')" />
                </div>
                <div style="display:flex; gap:8px; margin-top:8px;">
                    <button class="btn btn--tight" type="submit" style="flex:1;">Simpan & kirim OTP</button>
                    <button class="btn btn--ghost" type="button" id="batal-ganti-nomor" style="flex:0 0 auto;">Batal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .otp { display: contents; }
    .otp__info { margin-top: 18px; text-align: center; font-size: 14px; line-height: 1.5; color: #374151; }
    .otp__ok { margin-top: 10px; text-align: center; font-size: 13.5px; font-weight: 600; color: #166534; }
    .otp__warn { margin-top: 10px; padding: 10px 14px; border-radius: 12px; background: #fffbeb; color: #b45309; font-size: 13px; text-align: center; border: 1px solid #fef3c7; }
    .otp__uji { margin-top: 12px; padding: 10px 14px; border-radius: 12px; background: #fef3c7; color: #92400e; font-size: 13px; text-align: center; }
    .otp__kode { font-family: ui-monospace, Consolas, monospace; font-size: 18px; font-weight: 800; letter-spacing: .2em; }
    .otp__status-banner { margin-top: 12px; padding: 8px 14px; border-radius: 10px; background: #eff6ff; color: #1e40af; font-size: 13px; font-weight: 600; display: flex; align-items: center; justify-content: center; gap: 8px; border: 1px solid #dbeafe; }
    .spinner-dot { width: 8px; height: 8px; border-radius: 50%; background: #2563eb; display: inline-block; animation: pulse-dot 1.2s infinite ease-in-out; }
    @keyframes pulse-dot { 0%, 100% { opacity: 0.3; transform: scale(0.8); } 50% { opacity: 1; transform: scale(1.2); } }
    .otp__actions { margin-top: 16px; text-align: center; }
    .otp__ulang { display: flex; gap: 6px; justify-content: center; align-items: center; flex-wrap: wrap; font-size: 14px; }
    .otp__ulang button { border: 0; background: none; padding: 0; color: var(--primary); font: 600 14px var(--font); cursor: pointer; }
    .otp__ulang button[disabled] { color: #9ca3af; cursor: default; }
    .otp__ganti-box { margin-top: 14px; padding: 14px; border-radius: 12px; background: #f8fafc; border: 1.5px solid #e2e8f0; text-align: left; }
</style>
<script>
(function () {
    var b = document.getElementById('btn-ulang');
    var banner = document.getElementById('otp-status-banner');
    var bannerText = document.getElementById('hitung-mundur-teks');
    var s = parseInt(b.dataset.jeda, 10) || 0;
    var teks = b.textContent;

    function tik() {
        if (s <= 0) {
            b.disabled = false;
            b.textContent = teks;
            if (banner) banner.style.display = 'none';
            return;
        }
        b.disabled = true;
        b.textContent = teks + ' (' + s + ' dtk)';
        if (banner) {
            banner.style.display = 'flex';
            if (bannerText) bannerText.textContent = s;
        }
        s--;
        setTimeout(tik, 1000);
    }
    tik();

    var toggleBtn = document.getElementById('toggle-ganti-nomor');
    var box = document.getElementById('box-ganti-nomor');
    var batalBtn = document.getElementById('batal-ganti-nomor');

    if (toggleBtn && box) {
        toggleBtn.addEventListener('click', function (e) {
            e.preventDefault();
            box.style.display = (box.style.display === 'none' || box.style.display === '') ? 'block' : 'none';
        });
    }
    if (batalBtn && box) {
        batalBtn.addEventListener('click', function () {
            box.style.display = 'none';
        });
    }
})();
</script>
@endsection
