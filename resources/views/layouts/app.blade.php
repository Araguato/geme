<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'geme') }}</title>
        <link rel="icon" type="image/jpeg" href="{{ asset('logo-quorisk.jpg') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    @php
        $storedPrimary = \App\Models\Setting::get('theme_primary_color', '#0f172a');
        $storedAccent = \App\Models\Setting::get('theme_accent_color', '#22c55e');
        $themeVariant = \App\Models\Setting::get('theme_variant', 'classic');
        $themePrimaryColor = ($themeVariant === 'light') ? '#1f2937' : ($storedPrimary ?: '#0f172a');
        $themeAccentColor = ($themeVariant === 'light') ? '#38bdf8' : ($storedAccent ?: '#22c55e');
        $isLight = ($themeVariant === 'light');
        $bgClass = $isLight ? 'bg-gray-100' : 'bg-slate-900';
        $headerBg = $isLight ? 'bg-white' : 'bg-slate-950';
        $headerText = $isLight ? 'text-gray-900' : 'text-gray-100';
        $borderColor = $isLight ? 'border-gray-200' : 'border-slate-800';
    @endphp
    <body class="font-sans antialiased {{ $bgClass }} {{ $headerText }}">
        <div class="min-h-screen {{ $bgClass }}" style="background-color: {{ $isLight ? '#f3f4f6' : '#020617' }};">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="{{ $headerBg }} shadow border-b {{ $borderColor }}" style="background-color: {{ $isLight ? '#ffffff' : '#0b1220' }};">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8 {{ $headerText }}" style="color: {{ $isLight ? '#111827' : '#f3f4f6' }};">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/intro.js@7.2.0/minified/intro.min.js"></script>
    </body>
</html>
