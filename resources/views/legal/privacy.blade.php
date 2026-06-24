@extends('layouts.public')

@section('title', 'Política de privacidad - ' . \App\Models\Setting::get('business_name', config('app.name')))

@section('content')
<section class="py-5">
    <div class="container">
        <h1 class="mb-4">Política de privacidad</h1>

        <p class="text-muted">Última actualización: {{ date('d/m/Y') }}</p>

        <p>Esta política describe cómo recopilamos, usamos y protegemos la información personal de los usuarios de nuestro sitio.</p>

        <h5 class="mt-4">1. Información que recopilamos</h5>
        <p>Al registrarte como cliente, podemos recopilar: nombre, correo electrónico, teléfono, dirección de entrega y dirección IP. También recopilamos información sobre los pedidos que realizas.</p>

        <h5 class="mt-4">2. Uso de la información</h5>
        <p>Usamos tus datos para:</p>
        <ul>
            <li>Procesar y gestionar tus pedidos.</li>
            <li>Contactarte para confirmar entregas o resolver dudas.</li>
            <li>Mejorar nuestros productos y servicios.</li>
            <li>Cumplir con obligaciones legales y fiscales.</li>
        </ul>

        <h5 class="mt-4">3. Protección de datos</h5>
        <p>Implementamos medidas de seguridad razonables para proteger tu información contra acceso no autorizado, alteración o divulgación. Sin embargo, ningún sistema es completamente seguro.</p>

        <h5 class="mt-4">4. Compartir información</h5>
        <p>No vendemos ni compartimos tu información personal con terceros, excepto cuando sea necesario para cumplir con la ley o para procesar una entrega.</p>

        <h5 class="mt-4">5. Cookies y tecnologías similares</h5>
        <p>Utilizamos cookies para mantener tu sesión y mejorar la experiencia de navegación. También usamos servicios de seguridad como Cloudflare Turnstile para proteger el sitio contra bots.</p>

        <h5 class="mt-4">6. Tus derechos</h5>
        <p>Puedes solicitar la actualización, corrección o eliminación de tus datos personales contactándonos a través de los datos del sitio.</p>

        <h5 class="mt-4">7. Cambios a esta política</h5>
        <p>Podemos actualizar esta política ocasionalmente. Te recomendamos revisarla periódicamente.</p>

        <p class="mt-4">Si tienes preguntas sobre esta política, contáctanos.</p>
    </div>
</section>
@endsection
