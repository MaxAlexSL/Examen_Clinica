<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- Si el navegador restaura la página desde su caché al pulsar "Atrás", se recarga
             para que el servidor verifique la sesión (tras cerrar sesión, redirige al login). -->
        <script>
            window.addEventListener('pageshow', e => {
                if (e.persisted) location.reload();
            });
        </script>
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100">
            @auth
                @include('layouts.navigation')
            @endauth

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                <!-- Mensaje de éxito tras guardar -->
                @if (session('status'))
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-6">
                        <div class="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
                            {{ session('status') }}
                        </div>
                    </div>
                @endif

                <!-- Mensaje de error de una acción (ej. venta ya procesada) -->
                @if (session('error'))
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-6">
                        <div class="rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">
                            {{ session('error') }}
                        </div>
                    </div>
                @endif

                {{ $slot }}
            </main>
        </div>
    </body>
</html>
