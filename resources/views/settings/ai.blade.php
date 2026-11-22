@extends('layout')

@section('content')
<div class="geme-panel">
@php
    $modeLabels = ['off' => 'Desactivada', 'own' => 'Clave propia', 'managed' => 'Gestionada por GEME'];
    $levelLabels = ['aggregate' => 'Solo totales', 'detailed' => 'Con detalle'];
    $selectedMode = old('mode', $storedMode);
    $selectedLevel = old('data_level', $dataLevel);
@endphp

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h3 mb-0"><i class="bi bi-stars"></i> Inteligencia artificial</h1>
        <div class="text-muted small">Opcional. Mientras esté desactivada, GEME no envía ningún dato a ningún servicio de IA.</div>
    </div>
    <span class="badge rounded-pill fs-6 {{ $enabled ? 'bg-success' : 'bg-secondary' }}">
        <i class="bi {{ $enabled ? 'bi-toggle-on' : 'bi-toggle-off' }}"></i>
        {{ $enabled ? 'Activa · ' . $modeLabels[$mode] : 'Desactivada' }}
    </span>
</div>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
@if($errors->any())
    <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
@endif
@if($mode !== 'off' && !$consentValid)
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle"></i> La IA está configurada pero <strong>falta la autorización</strong> (o cambió el nivel de datos).
        No se enviarán datos del negocio hasta que la confirmes abajo.
    </div>
@endif

<div class="row g-3">
    <div class="col-lg-7">
        <form method="POST" action="{{ route('settings.ai.update') }}" class="card" id="aiForm">
            @csrf
            <div class="card-header">Configuración</div>
            <div class="card-body">
                {{-- Modo --}}
                <label class="form-label fw-semibold">Modo</label>
                <div class="d-grid gap-2 mb-3">
                    <label class="border rounded p-2 d-flex gap-2 align-items-start">
                        <input class="form-check-input mt-1" type="radio" name="mode" value="off" @checked($selectedMode === 'off')>
                        <span><strong>Desactivada</strong> <span class="text-muted small">(recomendado si no quieres compartir datos)</span><br>
                        <span class="small text-muted">No se envía nada. Las estadísticas y los informes de GEME funcionan igual, porque no usan IA.</span></span>
                    </label>
                    <label class="border rounded p-2 d-flex gap-2 align-items-start">
                        <input class="form-check-input mt-1" type="radio" name="mode" value="own" @checked($selectedMode === 'own')>
                        <span><strong>Clave propia</strong><br>
                        <span class="small text-muted">Usas tu propia cuenta de un proveedor de IA y pagas a ese proveedor. Sirve cualquier API compatible con OpenAI (OpenAI, Mistral, DeepSeek, Qwen, OpenRouter, Ollama…).</span></span>
                    </label>
                    @if($managedAvailable)
                        <label class="border rounded p-2 d-flex gap-2 align-items-start">
                            <input class="form-check-input mt-1" type="radio" name="mode" value="managed" @checked($selectedMode === 'managed')>
                            <span><strong>Gestionada por GEME</strong><br>
                            <span class="small text-muted">Usa el servicio contratado por GEME (modelo {{ $managedModel }}), con un límite de {{ number_format($managedLimit, 0, ',', '.') }} tokens al mes.</span></span>
                        </label>
                    @endif
                </div>

                {{-- Proveedor propio --}}
                <div id="ownFields" class="border rounded p-3 mb-3">
                    <div class="mb-2">
                        <label class="form-label" for="base_url">URL de la API</label>
                        <input type="url" name="base_url" id="base_url" class="form-control" value="{{ old('base_url', $baseUrl) }}" placeholder="https://api.proveedor.com/v1" autocomplete="off">
                        <div class="form-text">Dirección base, sin <code>/chat/completions</code>. Ejemplos: <code>https://api.openai.com/v1</code>, <code>https://api.mistral.ai/v1</code>, <code>https://openrouter.ai/api/v1</code>.</div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label" for="model">Modelo</label>
                        <input type="text" name="model" id="model" class="form-control" value="{{ old('model', $model) }}" placeholder="p. ej. mistral-small-latest" autocomplete="off">
                        <div class="form-text">Un modelo pequeño es suficiente y mucho más barato.</div>
                    </div>
                    <div>
                        <label class="form-label" for="api_key">Clave de API</label>
                        <input type="password" name="api_key" id="api_key" class="form-control" autocomplete="new-password"
                               placeholder="{{ $keyHint ? 'Guardada (' . $keyHint . ') — deja vacío para mantenerla' : 'sk-…' }}">
                        <div class="form-text"><i class="bi bi-shield-lock"></i> Se guarda cifrada y nunca se vuelve a mostrar. Solo los administradores pueden cambiarla.</div>
                    </div>
                </div>

                {{-- Nivel de datos --}}
                <label class="form-label fw-semibold">Datos que puede recibir la IA</label>
                <div class="d-grid gap-2 mb-3">
                    @foreach($levelLabels as $key => $label)
                        <label class="border rounded p-2 d-flex gap-2 align-items-start">
                            <input class="form-check-input mt-1" type="radio" name="data_level" value="{{ $key }}" @checked($selectedLevel === $key)>
                            <span><strong>{{ $label }}</strong><br>
                                <span class="small text-muted">
                                    @if($key === 'aggregate')
                                        Totales del mes, ventas por día y por categoría, tasa BCV y conteos de stock.
                                    @else
                                        Lo anterior más los productos más vendidos y las ventas por vendedor (con seudónimo: "Vendedor 1", "Vendedor 2"…).
                                    @endif
                                </span>
                            </span>
                        </label>
                    @endforeach
                    <div class="small"><i class="bi bi-person-x"></i> <strong>Nunca</strong> se envían datos de clientes (nombre, RIF, cédula, teléfono, correo) ni nombres de empleados.</div>
                </div>

                {{-- Consentimiento --}}
                <div class="border rounded p-3 mb-3 {{ $consentValid ? 'border-success' : 'border-warning' }}">
                    @if($consentValid)
                        <div class="small mb-2 text-success"><i class="bi bi-check-circle"></i>
                            Autorizado por {{ $consentUser?->name ?? 'usuario #' . $consent['user_id'] }} el {{ \Carbon\Carbon::parse($consent['at'])->format('d/m/Y H:i') }}
                            (nivel: {{ $levelLabels[$consent['data_level']] ?? $consent['data_level'] }}).
                        </div>
                    @endif
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="consent" value="1" id="consent" @checked(old('consent'))>
                        <label class="form-check-label" for="consent">
                            He revisado el <strong>ejemplo de datos</strong> de la derecha y autorizo que GEME envíe datos de este tipo
                            al proveedor de IA elegido para generar análisis. Puedo desactivarlo en cualquier momento.
                        </label>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Guardar</button>
                </div>
            </div>
        </form>

        @if($storedMode !== 'off')
            <div class="d-flex flex-wrap gap-2 mt-3">
                <form method="POST" action="{{ route('settings.ai.test') }}">
                    @csrf
                    <button class="btn btn-outline-secondary"><i class="bi bi-plug"></i> Probar conexión</button>
                </form>
                <form method="POST" action="{{ route('settings.ai.disable') }}" onsubmit="return confirm('¿Desactivar la IA? Se borrarán la clave, la URL, el modelo y el consentimiento.');">
                    @csrf
                    <button class="btn btn-outline-danger"><i class="bi bi-power"></i> Desactivar y borrar clave</button>
                </form>
            </div>
            <div class="form-text">"Probar conexión" solo envía un mensaje de prueba; no incluye datos del negocio.</div>
        @endif
    </div>

    <div class="col-lg-5">
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-eye"></i> Ejemplo real de lo que se enviaría</span>
                <span class="badge bg-secondary" id="previewBadge">{{ $levelLabels[$selectedLevel] }}</span>
            </div>
            <div class="card-body">
                <p class="small text-muted mb-2">Estos son los datos de hoy con el nivel elegido. Es exactamente lo que recibiría la IA junto con tu pregunta.</p>
                @foreach($previews as $key => $preview)
                    <pre class="small mb-0 p-2 rounded ai-preview" data-level="{{ $key }}" style="max-height: 420px; overflow: auto; background: rgba(148,163,184,.12); white-space: pre-wrap; {{ $key === $selectedLevel ? '' : 'display:none;' }}">{{ json_encode($preview, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                @endforeach
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-clock-history"></i> Uso de la IA</span>
                <span class="small text-muted fw-normal">Este mes: {{ $monthCalls }} llamadas · {{ number_format($monthTokens, 0, ',', '.') }} tokens</span>
            </div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light"><tr><th>Fecha</th><th>Usuario</th><th>Uso</th><th>Modelo</th><th class="text-end">Tokens</th><th>Estado</th></tr></thead>
                    <tbody>
                        @forelse($logs as $l)
                            <tr>
                                <td class="small text-nowrap">{{ $l->created_at->format('d/m H:i') }}</td>
                                <td class="small">{{ $l->user?->name ?? '—' }}</td>
                                <td class="small">{{ $l->purpose === 'test' ? 'Prueba' : $l->purpose }}</td>
                                <td class="small">{{ $l->model }}<div class="text-muted">{{ $l->provider_host }}</div></td>
                                <td class="small text-end">{{ $l->input_tokens + $l->output_tokens }}</td>
                                <td class="small">
                                    @if($l->status === 'ok')<span class="badge bg-success">OK</span>
                                    @else<span class="badge bg-danger" title="{{ $l->error }}">Error</span>@endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-muted small">Todavía no se ha enviado nada a ninguna IA.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</div>

<script>
    (function () {
        const own = document.getElementById('ownFields');
        const sync = () => {
            const mode = (document.querySelector('input[name=mode]:checked') || {}).value;
            own.style.display = mode === 'own' ? '' : 'none';
        };
        document.querySelectorAll('input[name=mode]').forEach(r => r.addEventListener('change', sync));
        sync();

        // Vista previa según el nivel de datos elegido
        const labels = @json($levelLabels);
        const syncPreview = () => {
            const level = (document.querySelector('input[name=data_level]:checked') || {}).value;
            document.querySelectorAll('.ai-preview').forEach(p => p.style.display = p.dataset.level === level ? '' : 'none');
            document.getElementById('previewBadge').textContent = labels[level] || '';
        };
        document.querySelectorAll('input[name=data_level]').forEach(r => r.addEventListener('change', syncPreview));
    })();
</script>
@endsection
