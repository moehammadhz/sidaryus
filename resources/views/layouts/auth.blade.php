<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#4ea5e2">
    <title>@yield('title') - SIDARYUS</title>
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* Desktop: 1 unit = 1px pada kanvas desain 1440x1024, diskalakan mengikuti layar */
        :root {
            --u: min(calc(100vh / 1024), calc(100vw / 1440));
            --f: var(--u);
            --bg: #fafbfc;
            --ink: #1f2937;
            --field: #e6e7ea;
            --field-text: #92949a;
            --primary: #2b72d5;
            --primary-dark: #2360b8;
            --danger: #c62828;
            --font: 'Inter', system-ui, sans-serif;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { min-height: 100%; }
        body {
            font-family: var(--font);
            background: var(--bg);
            color: var(--ink);
            min-height: 100vh;
            display: grid;
            place-items: center;
        }
        .stage { position: relative; width: calc(1440 * var(--u)); height: calc(1024 * var(--u)); }

        /* ---------- Panel kiri (694 x 900) ---------- */
        .hero {
            position: absolute;
            left: calc(80 * var(--u)); top: calc(66 * var(--u));
            width: calc(694 * var(--u)); height: calc(900 * var(--u));
            border-radius: calc(32 * var(--u));
            overflow: hidden; color: #fff;
            background:
                radial-gradient(ellipse 62% 34% at 4% 4%,  #d9e8ef 0%, rgba(217,232,239,0) 100%),
                radial-gradient(ellipse 60% 30% at 38% 52%, rgba(112,190,234,.95) 0%, rgba(112,190,234,0) 100%),
                radial-gradient(ellipse 50% 22% at 96% 12%, rgba(86,160,222,.85) 0%, rgba(86,160,222,0) 100%),
                radial-gradient(ellipse 48% 26% at 100% 58%, #0f78d8 0%, rgba(15,120,216,0) 100%),
                radial-gradient(ellipse 60% 26% at 92% 80%, #0a56cf 0%, rgba(10,86,207,0) 100%),
                radial-gradient(ellipse 85% 24% at 85% 100%, #0b1f55 0%, rgba(11,31,85,0) 100%),
                radial-gradient(ellipse 40% 18% at 5% 96%, #3f9fe0 0%, rgba(63,159,224,0) 100%),
                linear-gradient(172deg, #9fd0ef 0%, #6ab7e8 28%, #4ba3e0 52%, #2a82d6 72%, #0c3f9c 90%, #0a2260 100%);
        }
        .hero__logo {
            position: absolute; top: calc(32 * var(--u)); left: calc(34 * var(--u));
            width: calc(66 * var(--u)); height: calc(66 * var(--u));
            border-radius: 50%; overflow: hidden;
        }
        .hero__logo img { display: block; width: 100%; height: 100%; object-fit: contain; }
        .hero__inst {
            position: absolute; top: calc(42 * var(--u)); right: calc(54 * var(--u));
            text-align: right; line-height: 1.28; font-size: calc(14.5 * var(--u));
        }
        .hero__text { position: absolute; left: calc(43 * var(--u)); right: calc(43 * var(--u)); bottom: calc(134 * var(--u)); }
        .hero__welcome { font-size: calc(46 * var(--u)); font-weight: 300; letter-spacing: -.02em; line-height: 1.05; }
        .hero__title   { font-size: calc(68 * var(--u)); font-weight: 500; letter-spacing: -.04em; line-height: 1.02; }
        .hero__sub     { margin-top: calc(46 * var(--u)); font-size: calc(14.5 * var(--u)); line-height: 1.25; }
        .hero__mtitle  { display: none; }
        .foot { display: none; }

        /* ---------- Panel kanan ---------- */
        .panel {
            position: absolute; left: calc(890 * var(--f)); top: 0;
            width: calc(440 * var(--f)); height: 100%;
            display: flex; flex-direction: column; justify-content: center;
            text-align: center; padding-bottom: calc(10 * var(--f));
        }
        .panel h1 { font-size: calc(40 * var(--f)); font-weight: 700; letter-spacing: -.045em; line-height: 1; }
        .tagline { margin-top: calc(6 * var(--f)); font-size: max(11px, calc(19 * var(--f))); letter-spacing: .11em; white-space: nowrap; }
        .fields { margin: calc(50 * var(--f)) calc(18 * var(--f)) 0; display: grid; gap: calc(23 * var(--f)); }
        .fields--compact { margin-top: calc(36 * var(--f)); gap: calc(16 * var(--f)); }
        .fields-row { display: grid; grid-template-columns: 1fr 1fr; gap: calc(16 * var(--f)); }

        /* ---------- Field ---------- */
        .field { min-width: 0; }
        .field__label {
            position: absolute; width: 1px; height: 1px; overflow: hidden;
            clip: rect(0 0 0 0); white-space: nowrap;
        }
        .field__box {
            position: relative; display: flex; align-items: center;
            height: calc(63 * var(--f));
            border: 2px solid transparent; border-radius: calc(8 * var(--f));
            background: var(--field);
            transition: border-color .15s, background .15s;
        }
        .field__box:focus-within { border-color: var(--primary); background: #fff; }
        .field--invalid .field__box { border-color: var(--danger); }
        .field__input {
            flex: 1; min-width: 0; width: 100%; height: 100%;
            border: 0; outline: 0; background: transparent; color: var(--ink);
            font: 400 max(14px, calc(18 * var(--f))) var(--font);
            text-align: center;
        }
        .field__input::placeholder { color: var(--field-text); }
        .field__input:-webkit-autofill,
        .field__input:-webkit-autofill:focus {
            -webkit-text-fill-color: var(--ink);
            box-shadow: 0 0 0 1000px var(--field) inset;
            transition: background-color 9999s ease-in-out 0s;
        }
        .field__icon, .field__toggle { display: none; }

        /* Dropdown dan combobox */
        .field__select {
            appearance: none; -webkit-appearance: none; -moz-appearance: none;
            cursor: pointer; padding: 0 calc(34 * var(--f));
            text-align-last: center;
        }
        .field__select:invalid { color: var(--field-text); }
        .field__select option { color: #111827; }
        .field__chevron {
            position: absolute; right: calc(14 * var(--f));
            display: flex; pointer-events: none; color: var(--field-text);
        }
        /* Combobox: panah bawaan browser disembunyikan, seluruh kolom bisa diklik untuk membuka daftar */
        .field__combo { cursor: text; }
        .field__combo::-webkit-calendar-picker-indicator {
            position: absolute; inset: 0; width: 100%; height: 100%;
            opacity: 0; cursor: pointer;
        }

        .error { color: var(--danger); font-size: max(12px, calc(14 * var(--f))); margin-top: calc(8 * var(--f)); }

        .forgot {
            align-self: center; margin-top: calc(22 * var(--f));
            color: var(--ink); text-decoration: none; font-size: max(13px, calc(19 * var(--f)));
        }
        .forgot:hover, .alt a:hover { text-decoration: underline; }
        .btn {
            margin: calc(93 * var(--f)) calc(18 * var(--f)) 0; height: calc(63 * var(--f));
            border: 0; border-radius: calc(8 * var(--f)); cursor: pointer;
            background: var(--primary); color: #fff;
            font: 600 max(15px, calc(22 * var(--f))) var(--font); transition: background .15s;
        }
        .btn--tight { margin-top: calc(34 * var(--f)); }
        .btn:hover { background: var(--primary-dark); }
        .btn:focus-visible { outline: 3px solid #9fc2f2; outline-offset: 2px; }
        .alt { margin-top: calc(20 * var(--f)); font-size: max(13px, calc(17 * var(--f))); }
        .alt a { color: var(--primary); font-weight: 600; text-decoration: none; }

        /* ====================== MOBILE (< 900px) ====================== */
        @media (max-width: 900px) {
            :root {
                --u: 1px; --f: 1px;
                --bg: #f3f6fa;
                --field: #f6f8fb;
                --font: 'Plus Jakarta Sans', system-ui, sans-serif;
            }
            body { display: block; place-items: unset; }
            .stage { width: 100%; height: auto; min-height: 100vh; display: flex; flex-direction: column; padding-bottom: 28px; }

            .hero {
                position: relative; left: 0; top: 0; width: 100%; height: 300px;
                border-radius: 0 0 32px 32px;
                background:
                    radial-gradient(ellipse 80% 40% at 0% 0%, #d6e8f2 0%, rgba(214,232,242,0) 100%),
                    radial-gradient(ellipse 70% 40% at 100% 70%, #0f78d8 0%, rgba(15,120,216,0) 100%),
                    linear-gradient(170deg, #8cc6ec 0%, #4ea5e2 38%, #1d62c8 70%, #0a2563 100%);
            }
            .stage--compact .hero { height: 200px; }
            .hero__logo { top: 24px; left: 24px; width: 48px; height: 48px; }
            .hero__inst { top: 26px; right: 24px; font-size: 12px; line-height: 1.35; font-weight: 500; }
            .hero__text { left: 24px; right: 24px; bottom: 62px; }
            .stage--compact .hero__text { bottom: 56px; }
            .hero__welcome { font-size: 20px; letter-spacing: -.01em; }
            .hero__title   { font-size: 36px; font-weight: 700; letter-spacing: -.03em; line-height: 1.1; }
            .hero__sub { display: none; }
            .stage--compact .hero__welcome,
            .stage--compact .hero__title { display: none; }
            .stage--compact .hero__mtitle { display: block; font-size: 32px; font-weight: 700; letter-spacing: -.03em; line-height: 1.1; }

            .panel {
                position: relative; z-index: 1; left: 0; width: auto; height: auto;
                margin: -36px 16px 0; padding: 28px 24px 24px;
                background: #fff; border-radius: 24px;
                box-shadow: 0 12px 32px rgba(10,37,99,.10);
                text-align: left; justify-content: flex-start;
            }
            .panel h1 { font-size: 26px; font-weight: 800; letter-spacing: -.03em; color: #0a2563; }
            .tagline { margin-top: 4px; font-size: 13px; line-height: 1.4; letter-spacing: 0; white-space: normal; color: #4b5563; }
            .fields { margin: 24px 0 0; gap: 16px; }
            .fields--compact { margin-top: 22px; gap: 14px; }
            .fields-row { gap: 12px; }

            .field__label {
                position: static; width: auto; height: auto; overflow: visible; clip: auto;
                display: block; margin-bottom: 6px;
                font-size: 13px; font-weight: 600; color: #1f2937;
            }
            .field__box { height: 52px; gap: 10px; padding: 0 14px; border: 1.5px solid #d5dbe5; border-radius: 12px; }
            .field__input { text-align: left; font-size: 16px; font-weight: 500; } /* 16px mencegah zoom otomatis di iOS */
            .field__select { text-align-last: left; padding: 0 28px 0 0; }
            .field__chevron { right: 14px; }
            .field__icon, .field__toggle { display: flex; color: #6b7280; }
            .field__toggle { border: 0; background: transparent; padding: 8px; margin: -8px; cursor: pointer; }
            .error { font-size: 12.5px; margin-top: 6px; }

            .forgot { align-self: flex-end; margin-top: 6px; padding: 8px 0; font-size: 13px; font-weight: 600; color: var(--primary); }
            .btn, .btn--tight { margin: 8px 0 0; height: 52px; border-radius: 12px; font-size: 16px; font-weight: 700; }
            .btn--tight { margin-top: 24px; }
            .alt { margin-top: 20px; text-align: center; font-size: 14px; color: #4b5563; }
            .alt a { font-weight: 700; }
            .foot { display: block; margin-top: 20px; text-align: center; font-size: 11px; color: #6b7280; }
        }
        @media (prefers-reduced-motion: reduce) { * { transition: none !important; } }
    </style>
</head>
<body>
<main class="stage @yield('stage_class')">

    <section class="hero" aria-hidden="true">
        <div class="hero__logo">
            <img src="{{ asset('images/logo-ftujb.png') }}" alt="" width="66" height="66">
        </div>
        <p class="hero__inst">Fakultas Teknik<br>Universitas Janabadra</p>

        <div class="hero__text">
            <p class="hero__welcome">Selamat Datang</p>
            <p class="hero__title">Calon Sarjana</p>
            <p class="hero__mtitle">@yield('mobile_title', 'Calon Sarjana')</p>
            <p class="hero__sub">Satu langkah lagi menuju gelar Sarjana Teknik<br>Wujudkan langkah terakhir perjalanan akademik Anda.</p>
        </div>
    </section>

    <section class="panel">
        <h1>SIDARYUS</h1>
        <p class="tagline">Sistem Informasi Pendadaran dan Yudisium</p>
        @yield('form')
    </section>

    <p class="foot">Fakultas Teknik Universitas Janabadra</p>

</main>

<script>
    document.querySelectorAll('[data-toggle]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var input = document.getElementById(btn.dataset.toggle);
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.setAttribute('aria-label', show ? 'Sembunyikan password' : 'Tampilkan password');
        });
    });

    /* Kolom khusus angka (NIM, angkatan): huruf atau simbol yang diketik/ditempel langsung dibuang */
    document.querySelectorAll('[data-digits]').forEach(function (input) {
        input.addEventListener('input', function () {
            var bersih = input.value.replace(/\D+/g, '');
            if (input.maxLength > 0) bersih = bersih.slice(0, input.maxLength);
            if (bersih !== input.value) input.value = bersih;
        });
    });
</script>
</body>
</html>