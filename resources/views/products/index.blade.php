<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Productos</h2>

            @can('productos.crear')
                <a href="{{ route('products.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 rounded-md text-sm font-medium text-white hover:bg-indigo-700">
                    Nuevo producto
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <!-- Búsqueda por código o nombre -->
            <form method="GET" action="{{ route('products.index') }}" class="flex gap-2 px-4 sm:px-0">
                <x-text-input name="q" type="search" :value="$search" placeholder="Buscar por código o nombre" class="w-full sm:w-96" />
                <button type="submit" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    Buscar
                </button>
                @if ($search !== '')
                    <a href="{{ route('products.index') }}" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-900 self-center">Limpiar</a>
                @endif
            </form>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                        <tr>
                            <th class="px-6 py-3">Código</th>
                            <th class="px-6 py-3">Nombre</th>
                            <th class="px-6 py-3 text-right">Precio</th>
                            <th class="px-6 py-3 text-right">Stock</th>
                            <th class="px-6 py-3">Estado</th>
                            <th class="px-6 py-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($products as $product)
                            <tr>
                                <td class="px-6 py-3 font-mono text-gray-900">{{ $product->code }}</td>
                                <td class="px-6 py-3 text-gray-900">{{ $product->name }}</td>
                                <td class="px-6 py-3 text-right text-gray-900 whitespace-nowrap">S/ {{ number_format($product->price, 2) }}</td>
                                <td class="px-6 py-3 text-right whitespace-nowrap">
                                    @if ($product->stock === 0)
                                        <span class="inline-flex rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800">Sin stock</span>
                                    @else
                                        <span class="text-gray-900">{{ $product->stock }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-3">
                                    @if ($product->status === 'ACTIVO')
                                        <span class="inline-flex rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">ACTIVO</span>
                                    @else
                                        <span class="inline-flex rounded-full bg-gray-200 px-2.5 py-0.5 text-xs font-medium text-gray-700">INACTIVO</span>
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-right whitespace-nowrap space-x-3">
                                    <a href="{{ route('products.show', $product) }}" class="text-gray-700 hover:text-gray-900 font-medium">Ver</a>
                                    @can('productos.editar')
                                        <a href="{{ route('products.edit', $product) }}" class="text-indigo-600 hover:text-indigo-800 font-medium">Editar</a>
                                    @endcan
                                    @include('products.partials.status-toggle')
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-6 text-center text-gray-500">No se encontraron productos.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="px-4 sm:px-0">
                {{ $products->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
