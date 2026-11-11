@use('App\Support\Akses')
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') - SIDARYUS</title>
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #f3f6fa; --ink: #111827; --muted: #6b7280; --navy: #0a2563;
            --primary: #2b72d5; --primary-dark: #2360b8; --line: #e5eaf2; --danger: #dc2626;
            --font: 'Plus Jakarta Sans', system-ui, sans-serif;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: var(--font); background: var(--bg); color: var(--ink); }
        a { color: inherit; }

        /* Pop-up selalu di tengah layar. Reset margin di atas menghapus posisi tengah bawaan <dialog>. */
        dialog { margin: auto; max-width: calc(100vw - 32px); max-height: calc(100vh - 32px); }

        .app { display: flex; min-height: 100vh; }

        /* ---------- Sidebar ---------- */
        .sidebar {
            flex: 0 0 264px; width: 264px; height: 100vh; position: sticky; top: 0; overflow-y: auto;
            display: flex; flex-direction: column; padding: 24px 16px;
            background: linear-gradient(180deg, #0a2563 0%, #0b1f55 100%); color: #c7d6f5;
        }
        .brand { display: flex; align-items: center; gap: 12px; padding: 0 8px 24px; border-bottom: 1px solid rgba(255,255,255,.12); }
        .brand__logo { width: 44px; height: 44px; border-radius: 50%; flex: none; }
        .brand__logo img { display: block; width: 100%; height: 100%; object-fit: contain; border-radius: 50%; }
        .brand__name { font-size: 18px; font-weight: 800; color: #fff; letter-spacing: -.02em; }
        .brand__sub  { font-size: 11px; color: #9fb4e0; }

        .menu { margin-top: 20px; display: flex; flex-direction: column; gap: 4px; font-size: 14px; font-weight: 500; }
        .menu__group { padding: 16px 14px 6px; font-size: 11px; font-weight: 700; letter-spacing: .08em; color: #7f98cf; }
        .menu__group:first-child { padding-top: 0; }
        .menu a {
            display: flex; align-items: center; gap: 12px; height: 42px; padding: 0 14px;
            border-radius: 10px; color: #c7d6f5; text-decoration: none; transition: background .15s;
        }
        .menu a:hover { background: rgba(255,255,255,.08); }
        .menu a.is-active { background: rgba(255,255,255,.16); color: #fff; font-weight: 600; }
        .menu a.is-soon { opacity: .6; cursor: default; }

        .profile { margin-top: auto; padding-top: 16px; border-top: 1px solid rgba(255,255,255,.12); display: flex; align-items: center; gap: 12px; }
        .profile__avatar { width: 40px; height: 40px; border-radius: 50%; background: var(--primary); color: #fff; display: grid; place-items: center; font-weight: 700; flex: none; }
        .profile__info { flex: 1; min-width: 0; }
        .profile__name { font-size: 14px; font-weight: 600; color: #fff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .profile__role { font-size: 12px; color: #9fb4e0; }
        .profile button { border: 0; background: transparent; color: #c7d6f5; padding: 8px; cursor: pointer; display: flex; border-radius: 8px; }
        .profile button:hover { background: rgba(255,255,255,.1); color: #fff; }

        /* ---------- Konten utama ---------- */
        .main { flex: 1; min-width: 0; padding: 0 32px 32px; }
        .topbar { min-height: 72px; display: flex; align-items: center; gap: 16px; }
        .topbar__title { flex: 1; min-width: 0; }
        .crumbs { font-size: 12px; color: var(--muted); }
        .crumbs b { color: var(--primary); font-weight: 600; }
        .topbar h1 { margin-top: 2px; font-size: 22px; font-weight: 800; letter-spacing: -.02em; color: var(--navy); }
        .burger { display: none; background: #fff; border: 1px solid var(--line); border-radius: 10px; width: 40px; height: 40px; place-items: center; cursor: pointer; color: var(--navy); }

        .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            height: 40px; padding: 0 16px; border: 0; border-radius: 10px;
            font: 600 13px var(--font); text-decoration: none; cursor: pointer; transition: background .15s;
        }
        .btn--primary { background: var(--primary); color: #fff; }
        .btn--primary:hover { background: var(--primary-dark); }
        .btn--ghost { background: #fff; color: var(--navy); border: 1px solid var(--line); }
        .btn--danger { background: var(--danger); color: #fff; }

        .overlay { display: none; }

        @media (max-width: 1000px) {
            .sidebar { position: fixed; left: 0; top: 0; z-index: 50; transform: translateX(-100%); transition: transform .2s; }
            .nav-open .sidebar { transform: none; }
            .overlay { position: fixed; inset: 0; z-index: 40; background: rgba(10,37,99,.45); }
            .nav-open .overlay { display: block; }
            .burger { display: grid; }
            .main { padding: 0 16px 24px; }
        }
        @media (prefers-reduced-motion: reduce) { * { transition: none !important; } }
    </style>
    @stack('styles')
</head>
<body>
@php
    $pengguna = request()->user();
    $adalahAdmin = $pengguna->role === \App\Enums\Role::Admin;

    // Hanya menu yang boleh dibuka role ini. Menu tanpa route (belum jadi) hanya untuk admin.
    // Grup yang semua isinya tersembunyi ikut dibuang.
    $menu = collect(config('admin_menu'))
        ->map(fn ($items) => collect($items)
            ->filter(fn ($item) => $item[2] === null ? $adalahAdmin : Akses::bisa($pengguna, $item[2]))
            // BAAK tidak punya dashboard sendiri; halaman awalnya adalah Kelengkapan Mahasiswa
            ->reject(fn ($item) => $item[2] === 'dashboard' && $pengguna->role === \App\Enums\Role::Baak)
            ->values()
            ->all())
        ->filter()
        ->all();
@endphp

<div class="app" id="app">
    <aside class="sidebar" id="sidebar">
        <div class="brand">
            <div class="brand__logo"><img src="{{ asset('images/logo-ftujb.png') }}" alt="Logo FT UJB" width="44" height="44"></div>
            <div>
                <div class="brand__name">SIDARYUS</div>
                <div class="brand__sub">Fakultas Teknik UJB</div>
            </div>
        </div>

        <nav class="menu" aria-label="Menu utama">
            @foreach ($menu as $grup => $items)
                <div class="menu__group">{{ strtoupper($grup) }}</div>
                @foreach ($items as [$label, $ikon, $rute])
                    @if ($rute)
                        @php
                            // "dashboard" aktif di dashboard role mana pun (admin, petugas, dst.)
                            $aktif = $rute === 'dashboard' ? request()->routeIs('*.dashboard') : request()->routeIs($rute);
                        @endphp
                        <a href="{{ route($rute) }}" @class(['is-active' => $aktif])>
                            <x-icon :name="$ikon" /> {{ $label }}
                        </a>
                    @else
                        <a href="#" class="is-soon" title="Segera hadir" aria-disabled="true">
                            <x-icon :name="$ikon" /> {{ $label }}
                        </a>
                    @endif
                @endforeach
            @endforeach
        </nav>

        <div class="profile">
            <div class="profile__avatar">{{ mb_strtoupper(mb_substr($pengguna->name, 0, 1)) }}</div>
            <div class="profile__info">
                <div class="profile__name">{{ $pengguna->name }}</div>
                <div class="profile__role">{{ $pengguna->role->label() }}</div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" aria-label="Logout" title="Logout"><x-icon name="logout" /></button>
            </form>
        </div>
    </aside>

    <div class="overlay" id="overlay"></div>

    <div class="main">
        <header class="topbar">
            <button class="burger" id="burger" type="button" aria-label="Buka menu"><x-icon name="menu" :size="22" /></button>
            <div class="topbar__title">
                <div class="crumbs">Beranda &nbsp;/&nbsp; <b>@yield('crumb')</b></div>
                <h1>@yield('page_title')</h1>
            </div>
            @yield('actions')
        </header>

        @yield('content')
    </div>
</div>

<script>
    var app = document.getElementById('app');
    document.getElementById('burger').addEventListener('click', function () { app.classList.toggle('nav-open'); });
    document.getElementById('overlay').addEventListener('click', function () { app.classList.remove('nav-open'); });
    document.querySelectorAll('.is-soon').forEach(function (a) {
        a.addEventListener('click', function (e) { e.preventDefault(); });
    });
</script>
@stack('scripts')
</body>
</html>