@extends('layouts.public')

@section('title', 'Pedido confirmado - ' . \App\Models\Setting::get('business_name', config('app.name')))

@section('content')
<section class="py-5">
    <div class="container text-center">
        <div class="py-5">
            <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
            <h1 class="display-5 fw-bold mt-4 mb-3">¡Pedido recibido!</h1>
            <p class="lead mb-4">Gracias por tu compra. Te contactaremos pronto para confirmar la disponibilidad y coordinar la entrega o el retiro.</p>
            <p class="text-muted">El pago se realizará al momento de retirar o recibir tu pedido.</p>
            <a href="{{ route('public.order.index') }}" class="btn btn-success btn-lg mt-3">Hacer otro pedido</a>
        </div>
    </div>
</section>
@endsection
