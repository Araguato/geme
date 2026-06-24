@extends('layouts.public')

@section('title', 'Términos y condiciones - ' . \App\Models\Setting::get('business_name', config('app.name')))

@section('content')
<section class="py-5">
    <div class="container">
        <h1 class="mb-4">Términos y condiciones</h1>

        <p class="text-muted">Última actualización: {{ date('d/m/Y') }}</p>

        <p>Bienvenido a nuestra plataforma de pedidos en línea. Al usar este sitio y realizar pedidos, aceptas los siguientes términos y condiciones.</p>

        <h5 class="mt-4">1. Uso del sitio</h5>
        <p>El sitio está destinado a consultar nuestro catálogo de productos y realizar pedidos. Queda prohibido el uso del sitio para fines ilegales o no autorizados.</p>

        <h5 class="mt-4">2. Cuentas de usuario</h5>
        <p>Para realizar pedidos es necesario crear una cuenta de cliente. Eres responsable de mantener la confidencialidad de tu contraseña y de toda actividad que ocurra bajo tu cuenta.</p>

        <h5 class="mt-4">3. Pedidos y pagos</h5>
        <p>Los pedidos realizados a través del sitio no incluyen pago en línea. El pago se realiza al momento de retirar el pedido en el local o al recibir la entrega. Nos reservamos el derecho de cancelar pedidos por falta de disponibilidad de productos.</p>

        <h5 class="mt-4">4. Precios y disponibilidad</h5>
        <p>Los precios publicados pueden cambiar sin previo aviso. La disponibilidad de productos está sujeta a existencias en el momento de la entrega.</p>

        <h5 class="mt-4">5. Entregas</h5>
        <p>La entrega se realiza en la dirección indicada por el cliente durante el proceso de compra. El tiempo de entrega puede variar según la zona y la disponibilidad.</p>

        <h5 class="mt-4">6. Limitación de responsabilidad</h5>
        <p>No nos hacemos responsables por daños indirectos derivados del uso del sitio o de la imposibilidad de realizar un pedido por causas técnicas.</p>

        <h5 class="mt-4">7. Modificaciones</h5>
        <p>Podemos actualizar estos términos en cualquier momento. El uso continuo del sitio implica la aceptación de los términos vigentes.</p>

        <p class="mt-4">Si tienes dudas, contáctanos a través de los datos publicados en el sitio.</p>
    </div>
</section>
@endsection
