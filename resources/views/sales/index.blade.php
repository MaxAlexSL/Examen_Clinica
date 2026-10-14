<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Ventas</h2>

            @can('ventas.crear')
                <a href="{{ route('sales.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 rounded-md text-sm font-medium text-white hover:bg-indigo-700">
                    Nueva venta
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <!-- Filtros: rango de fechas y paciente -->
            <form method="GET" action="{{ route('sales.index') }}" class="flex flex-wrap items-end gap-3 px-4 sm:px-0">
                <div>
                    <x-input-label for="desde" value="Desde" />
                    <x-text-input id="desde" name="desde" type="date" :value="$filters['desde']" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="hasta" value="Hasta" />
                    <x-text-input id="hasta" name="hasta" type="date" :value="$filters['hasta']" class="mt-1" />
                </div>
                <div class="grow sm:grow-0">
                    <x-input-label for="paciente" value="Paciente" />
                    <x-text-input id="paciente" name="paciente" type="search" :value="$filters['paciente']" placeholder="Documento o nombre" class="mt-1 w-full sm:w-72" />
                </div>
                @if ($showSeller)
                    <div>
                        <x-input-label for="vendedor" value="Vendedor" />
                        <select id="vendedor" name="vendedor" class="mt-1 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="">Todos</option>
                            @foreach ($sellers as $seller)
                                <option value="{{ $seller->id }}" @selected($filters['vendedor'] === (string) $seller->id)>{{ $seller->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <button type="submit" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    Filtrar
                </button>
                @if (array_filter($filters))
                    <a href="{{ route('sales.index') }}" class="px-2 py-2 text-sm text-gray-600 hover:text-gray-900">Limpiar</a>
                @endif
            </form>
            @if ($errors->any())
                <ul class="px-4 sm:px-0 text-sm text-red-600">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                        <tr>
                            <th class="px-6 py-3">Código</th>
                            <th class="px-6 py-3">Fecha y hora</th>
                            <th class="px-6 py-3">Paciente</th>
                            @if ($showSeller)
                                <th class="px-6 py-3">Vendedor</th>
                            @endif
                            <th class="px-6 py-3">Medio de pago</th>
                            <th class="px-6 py-3 text-right">Total</th>
                            <th class="px-6 py-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($sales as $sale)
                            <tr>
                                <td class="px-6 py-3 font-mono text-gray-900">{{ $sale->code }}</td>
                                <td class="px-6 py-3 text-gray-600 whitespace-nowrap">{{ $sale->sold_at->format('d/m/Y H:i') }}</td>
                                <td class="px-6 py-3 text-gray-900">{{ $sale->patient->last_names }}, {{ $sale->patient->first_names }}</td>
                                @if ($showSeller)
                                    <td class="px-6 py-3 text-gray-600">{{ $sale->user->name }}</td>
                                @endif
                                <td class="px-6 py-3 text-gray-600">{{ $sale->payment_method }}</td>
                                <td class="px-6 py-3 text-right text-gray-900 whitespace-nowrap">S/ {{ number_format($sale->total, 2) }}</td>
                                <td class="px-6 py-3 text-right">
                                    <a href="{{ route('sales.show', $sale) }}" class="text-gray-700 hover:text-gray-900 font-medium">Ver</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $showSeller ? 7 : 6 }}" class="px-6 py-6 text-center text-gray-500">No se encontraron ventas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="bg-gray-50">
                        <tr>
                            <td colspan="{{ $showSeller ? 5 : 4 }}" class="px-6 py-3 text-right font-semibold text-gray-700">
                                Total del resultado ({{ $sales->total() }} {{ $sales->total() === 1 ? 'venta' : 'ventas' }})
                            </td>
                            <td class="px-6 py-3 text-right font-semibold text-gray-900 whitespace-nowrap">S/ {{ number_format((float) $filteredTotal, 2) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="px-4 sm:px-0">
                {{ $sales->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
