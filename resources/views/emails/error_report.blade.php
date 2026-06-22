<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de problema</title>
</head>
<body>
<h2>Nuevo reporte de problema del sistema</h2>

<p><strong>Título:</strong> {{ $payload['subject'] }}</p>

<p><strong>Descripción:</strong></p>
<p>{!! nl2br(e($payload['description'])) !!}</p>

<hr>
<h3>Información técnica</h3>
<ul>
    <li><strong>URL:</strong> {{ $payload['url'] }}</li>
    <li><strong>Usuario:</strong> {{ $payload['user_name'] ?? 'N/A' }} ({{ $payload['user_email'] ?? 'sin correo' }})</li>
    <li><strong>IP:</strong> {{ $payload['ip'] }}</li>
    <li><strong>Navegador:</strong> {{ $payload['user_agent'] }}</li>
</ul>

@if(!empty($payload['log_excerpt']))
    <h4>Últimas líneas de laravel.log (máx. 150 líneas)</h4>
    <pre style="background:#f5f5f5; padding:10px; border:1px solid #ddd; font-size:12px; white-space:pre-wrap;">{{ $payload['log_excerpt'] }}</pre>
@else
    <p><em>No se pudo adjuntar un extracto de logs (archivo no accesible o vacío).</em></p>
@endif

</body>
</html>
