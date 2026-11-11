@extends('layouts.auth')

@section('title', 'Login')

@section('form')
<form method="POST" action="{{ route('login.store') }}" style="display:contents" novalidate>
    @csrf

    @if (session('error'))
        <p class="error" role="alert" style="text-align:center">{{ session('error') }}</p>
    @endif

    <div class="fields">
        <x-field name="username" label="Username" placeholder="Username" autocomplete="username" icon="user" :autofocus="true" />
        <x-field name="password" label="Password" type="password" placeholder="Password" autocomplete="current-password" icon="lock" :toggle="true" />
    </div>

    <p class="forgot" style="cursor: default; text-decoration: none;" title="Reset password dilakukan oleh admin">Lupa password? Hubungi admin fakultas.</p>

    <button class="btn" type="submit">Login</button>
    <p class="alt">Belum punya akun? <a href="{{ route('register') }}">Daftar</a></p>
</form>
@endsection