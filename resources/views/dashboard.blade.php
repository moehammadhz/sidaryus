<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard {{ auth()->user()->role->label() }} - SIDARYUS</title>
    <style>
        body { font-family: system-ui, sans-serif; background:#f3f6fa; color:#111827; margin:0; padding:48px; }
        .badge { display:inline-block; padding:4px 12px; border-radius:999px; background:#dbeafe; color:#1d4ed8; font-weight:600; font-size:14px; }
        button { background:#2b72d5; color:#fff; border:0; border-radius:10px; padding:10px 20px; font-size:15px; cursor:pointer; }
    </style>
</head>
<body>
    <h1>Halo, {{ auth()->user()->name }}</h1>
    <p>Anda masuk sebagai <span class="badge">{{ auth()->user()->role->label() }}</span></p>
    <p>Fitur untuk role ini akan dibangun pada tahap berikutnya.</p>
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit">Logout</button>
    </form>
</body>
</html>