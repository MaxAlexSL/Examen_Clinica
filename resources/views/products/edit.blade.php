<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Editar producto <span class="font-mono">{{ $product->code }}</span>
            </h2>
            <x-back-button :href="route('products.index')" />
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('products.update', $product) }}">
                    @csrf
                    @method('PUT')

                    @include('products.partials.form')

                    <div class="mt-6 flex items-center justify-end gap-3">
                        <a href="{{ route('products.index') }}" class="text-sm text-gray-600 hover:text-gray-900">Cancelar</a>
                        <x-primary-button>Guardar cambios</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
