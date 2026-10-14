<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Venta <span class="font-mono">{{ $sale->code }}</span>
            </h2>
            {{-- RN-11: una venta confirmada no se edita ni se elimina (sin botones). --}}
            <x-back-button :href="route('sales.index')" />
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-6">
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                    <div>
                        <dt class="text-gray-500">Código</dt>
                        <dd class="mt-1 font-mono text-gray-900">{{ $sale->code }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Fecha y hora</dt>
                        <dd class="mt-1 text-gray-900">{{ $sale->sold_at->format('d/m/Y H:i') }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Paciente</dt>
                        <dd class="mt-1 text-gray-900">
                            @can('pacientes.ver')
                                <a href="{{ route('patients.show', $sale->patient) }}" class="text-indigo-600 hover:text-indigo-800">
                                    {{ $sale->patient->last_names }}, {{ $sale->patient->first_names }}
                                </a>
                            @else
                                {{ $sale->patient->last_names }}, {{ $sale->patient->first_names }}
                            @endcan
                            <span class="block text-gray-500">{{ $sale->patient->affiliation_code }} · {{ $sale->patient->document_type }} {{ $sale->patient->document_number }}</span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Vendedor</dt>
                        <dd class="mt-1 text-gray-900">{{ $sale->user->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Medio de pago</dt>
                        <dd class="mt-1 text-gray-900">{{ $sale->payment_method }}</dd>
                    </div>
                </dl>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            <tr>
                                <th class="px-4 py-2">Producto</th>
                                <th class="px-4 py-2 text-right">Cantidad</th>
                                <th class="px-4 py-2 text-right">Precio unitario</th>
                                <th class="px-4 py-2 text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($sale->items as $item)
                                <tr>
                                    <td class="px-4 py-2 text-gray-900">
                                        <span class="font-mono text-gray-500">{{ $item->product->code }}</span> {{ $item->product->name }}
                                    </td>
                                    <td class="px-4 py-2 text-right">{{ $item->quantity }}</td>
                                    {{-- Precio aplicado en la venta (no el precio actual del producto). --}}
                                    <td class="px-4 py-2 text-right whitespace-nowrap">S/ {{ $item->unit_price }}</td>
                                    <td class="px-4 py-2 text-right whitespace-nowrap">S/ {{ $item->subtotal }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="border-t-2 border-gray-200">
                                <td colspan="3" class="px-4 py-3 text-right font-semibold text-gray-700">Total</td>
                                <td class="px-4 py-3 text-right font-semibold text-gray-900 whitespace-nowrap">S/ {{ $sale->total }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
