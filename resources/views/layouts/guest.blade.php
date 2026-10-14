<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,800&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        {{-- logo y credenciales --}}
        <div class="min-h-screen grid lg:grid-cols-5">
            <div class="flex flex-col items-center justify-center bg-gray-50 px-6 py-12 lg:col-span-2">
                <div class="w-full max-w-md">
                    <x-clinic-logo class="mb-8" />

                    <div class="bg-white px-6 py-8 shadow-md ring-1 ring-gray-100 sm:rounded-lg sm:px-8">
                        {{ $slot }}
                    </div>
                </div>
            </div>

            {{-- Foto de la clínica: public/images/clinica.jpg  --}}
            <div class="relative hidden lg:col-span-3 lg:block bg-indigo-700">
                <img src="{{ asset('images/clinica.jpg') }}" alt="Instalaciones de la clínica"
                     class="absolute inset-0 h-full w-full object-cover">
                <div class="absolute inset-0 bg-gradient-to-t from-indigo-950/70 via-indigo-900/20 to-transparent"></div>

                <div class="absolute inset-x-0 bottom-0 p-12 text-white">
                    <p class="text-2xl font-semibold">Clínica Peruano Suiza</p>
                    <p class="mt-2 max-w-md text-sm text-white/80">Gestión de pacientes, productos y ventas.</p>
                </div>
            </div>
        </div>
    </body>
</html>
