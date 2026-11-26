<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') · {{ config('app.name', 'GEME') }}</title>
    <style>
        body{margin:0;font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif;background:#f3f4f6;color:#1f2937;display:flex;min-height:100vh;align-items:center;justify-content:center}
        .card{background:#fff;max-width:480px;width:calc(100% - 32px);padding:32px;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,.08);text-align:center}
        h1{font-size:1.4rem;margin:0 0 12px}
        p{line-height:1.5;margin:0 0 12px;color:#4b5563}
        .code{display:inline-block;margin:8px 0 16px;padding:6px 12px;background:#f3f4f6;border-radius:6px;font-family:ui-monospace,Consolas,monospace;font-size:1.05rem;color:#111827}
        .actions{display:flex;gap:8px;justify-content:center;flex-wrap:wrap;margin-top:8px}
        a.btn{padding:10px 18px;border-radius:8px;text-decoration:none;font-weight:600;background:#2563eb;color:#fff}
        a.btn.alt{background:#e5e7eb;color:#111827}
        small{color:#6b7280}
    </style>
</head>
<body>
    <div class="card">
        <h1>@yield('heading')</h1>
        @yield('body')
        <div class="actions">
            <a class="btn" href="{{ url('/') }}">Ir al inicio</a>
            <a class="btn alt" href="javascript:history.back()">Volver</a>
        </div>
    </div>
</body>
</html>
