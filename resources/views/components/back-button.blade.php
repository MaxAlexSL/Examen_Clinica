{{-- Botón para volver al listado. Uso: <x-back-button :href="route('patients.index')" /> --}}
@props(['href'])

<a href="{{ $href }}" {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 px-4 py-2 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50']) }}>
    &larr; {{ $slot->isEmpty() ? 'Volver al listado' : $slot }}
</a>
