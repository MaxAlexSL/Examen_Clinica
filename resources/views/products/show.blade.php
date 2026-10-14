<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Producto <span class="font-mono">{{ $product->code }}</span>
            </h2>

            <div class="flex items-center gap-4 text-sm">
                <x-back-button :href="route('products.index')" />
                @include('products.partials.status-toggle')
                @can('productos.editar')
                    <a href="{{ route('products.edit', $product) }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 rounded-md font-medium text-white hover:bg-indigo-700">
                        Editar
                    </a>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <!-- Ficha del producto -->
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                    <div>
                        <dt class="text-gray-500">Código</dt>
                        <dd class="mt-1 font-mono text-gray-900">{{ $product->code }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Estado</dt>
                        <dd class="mt-1">
                            @if ($product->status === 'ACTIVO')
                                <span class="inline-flex rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">ACTIVO</span>
                            @else
                                <span class="inline-flex rounded-full bg-gray-200 px-2.5 py-0.5 text-xs font-medium text-gray-700">INACTIVO</span>
                            @endif
                        </dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-gray-500">Nombre</dt>
                        <dd class="mt-1 text-gray-900">{{ $product->name }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-gray-500">Descripción</dt>
                        <dd class="mt-1 text-gray-900 whitespace-pre-line">{{ $product->description ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Precio</dt>
                        <dd class="mt-1 text-gray-900">S/ {{ number_format($product->price, 2) }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Stock</dt>
                        <dd class="mt-1">
                            @if ($product->stock === 0)
                                <span class="inline-flex rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800">Sin stock</span>
                            @else
                                <span class="text-gray-900">{{ $product->stock }}</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Fecha de registro</dt>
                        <dd class="mt-1 text-gray-900">{{ $product->created_at?->format('d/m/Y H:i') ?? '—' }}</dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>
</x-app-layout>
