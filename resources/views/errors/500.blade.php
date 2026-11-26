@extends('errors.layout')
@section('title', 'Algo salió mal')
@section('heading', 'Algo salió mal de nuestro lado')
@section('body')
<p>No se pudo completar la operación. Lo que ya estaba guardado no se afectó.</p>
@isset($errorCode)
<p>Si el problema se repite, avisa a soporte con este código:</p>
<div class="code">{{ $errorCode }}</div>
@endisset
@endsection
