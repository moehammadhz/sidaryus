@extends('layouts.auth')

@section('title', 'Daftar')
@section('stage_class', 'stage--compact')
@section('mobile_title', 'Buat Akun')

@section('form')
@if (session('error'))
    <p class="error" role="alert" style="text-align:center;margin-top:12px">{{ session('error') }}</p>
@endif
<form method="POST" action="{{ route('register.store') }}" style="display:contents" novalidate>
    @csrf

    <div class="fields fields--compact">
        <x-field name="name" label="Nama lengkap" placeholder="Nama Lengkap" autocomplete="name" :autofocus="true" />
        <x-field name="username" label="NIM (username)" placeholder="NIM ({{ \App\Models\User::NIM_PANJANG }} digit angka)"
                 autocomplete="username" :digits="\App\Models\User::NIM_PANJANG" />
        <x-field name="no_wa" label="Nomor WhatsApp" type="tel" placeholder="08xxxxxxxxxx" autocomplete="tel" />

        <x-field name="program_studi" label="Program studi" as="select"
                 placeholder="Program Studi" :options="\App\Models\User::PROGRAM_STUDI" />

        <div class="fields-row">
            <x-field name="kelas" label="Kelas" as="select"
                     placeholder="Kelas" :options="\App\Models\User::KELAS" />
            <x-field name="angkatan" label="Angkatan" as="combo"
                     placeholder="Angkatan"
                     :options="range(\App\Models\User::ANGKATAN_MIN, \App\Models\User::ANGKATAN_MAX)" />
        </div>

        <x-field name="password" label="Password" type="password" placeholder="Password (min. 8 karakter)"
                 autocomplete="new-password" :toggle="true" />
        <x-field name="password_confirmation" label="Konfirmasi password" type="password"
                 placeholder="Konfirmasi Password" autocomplete="new-password" />
    </div>

    <button class="btn btn--tight" type="submit">Kirim kode verifikasi</button>
    <p class="alt" style="margin-top:8px">Kode OTP akan dikirim ke WhatsApp Anda untuk mengaktifkan akun.</p>
    <p class="alt">Sudah punya akun? <a href="{{ route('login') }}">Login</a></p>
</form>
@endsection