@extends('layout')

@section('content')
<div class="geme-panel">
<div class="mb-3">
    <h1 class="h3 mb-0"><i class="bi bi-receipt"></i> Medio de emisión fiscal</h1>
    <div class="text-muted small">Providencia SNAT/2026/00093 (vigente desde el 19-04-2027). Mientras elijas «Ninguno», GEME funciona exactamente como hasta ahora.</div>
</div>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())
    <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
@endif

<div class="row g-3">
    <div class="col-lg-7">
        <form method="POST" action="{{ route('settings.fiscal.update') }}" class="card">
            @csrf
            <div class="card-header">Medio de emisión</div>
            <div class="card-body">
                <div class="d-grid gap-2 mb-3">
                    @foreach($providers as $key => $label)
                        <label class="border rounded p-2 d-flex gap-2 align-items-start">
                            <input class="form-check-input mt-1" type="radio" name="provider" value="{{ $key }}" @checked(old('provider', $current) === $key)>
                            <span>{{ $label }}</span>
                        </label>
                    @endforeach
                    <div class="border rounded p-2 text-muted small d-flex gap-2">
                        <i class="bi bi-hourglass-split"></i>
                        <span><strong>Imprenta digital autorizada</strong> — próximamente. Se añadirá cuando se elija proveedor y se disponga de su API.</span>
                    </div>
                </div>
                <div class="alert alert-warning small mb-3">
                    El proveedor de pruebas solo sirve para ensayar: sus números llevan el prefijo «PRUEBA-» y los tickets indican que no tienen validez fiscal.
                    No lo uses para facturar de verdad.
                </div>
                <button class="btn btn-primary">Guardar</button>
            </div>
        </form>
    </div>
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header">Cómo funciona</div>
            <div class="card-body small">
                <ul class="mb-2">
                    <li>La venta se guarda siempre. La emisión fiscal se hace justo después y <strong>nunca bloquea el TPV</strong>.</li>
                    <li>Si el medio falla, el documento queda <em>pendiente</em> y se reintenta; también puedes registrarlo como <em>contingencia</em> con su factura de talonario.</li>
                    <li>Cada venta guarda la <strong>tasa BCV</strong> aplicada para mostrar el equivalente en Bs.</li>
                </ul>
                <a href="{{ route('fiscal-documents.index') }}" class="btn btn-sm btn-outline-secondary">
                    Ver documentos @if($pending)<span class="badge bg-warning text-dark">{{ $pending }} sin emitir</span>@endif
                </a>
            </div>
        </div>
    </div>
</div>
</div>
@endsection
