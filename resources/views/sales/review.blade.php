<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Revisar venta</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <p class="px-4 sm:px-0 text-sm text-gray-600">
                Verifica los datos. Los precios son los vigentes en el sistema; la venta aún no se ha registrado.
            </p>

            <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-6">
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="text-gray-500">Paciente</dt>
                        <dd class="mt-1 text-gray-900">
                            {{ $quote['patient']->last_names }}, {{ $quote['patient']->first_names }}
                            <span class="block text-gray-500">
                                {{ $quote['patient']->affiliation_code }} · {{ $quote['patient']->document_type }} {{ $quote['patient']->document_number }}
                            </span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Medio de pago</dt>
                        <dd class="mt-1 text-gray-900">{{ $paymentMethod }}</dd>
                    </div>
                </dl>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            <tr>
                                <th class="px-4 py-2">Producto</th>
                                <th class="px-4 py-2 text-right">Precio unitario</th>
                                <th class="px-4 py-2 text-right">Cantidad</th>
                                <th class="px-4 py-2 text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($quote['items'] as $item)
                                <tr>
                                    <td class="px-4 py-2 text-gray-900">
                                        <span class="font-mono text-gray-500">{{ $item['product']->code }}</span> {{ $item['product']->name }}
                                    </td>
                                    <td class="px-4 py-2 text-right whitespace-nowrap">S/ {{ $item['unit_price'] }}</td>
                                    <td class="px-4 py-2 text-right">{{ $item['quantity'] }}</td>
                                    <td class="px-4 py-2 text-right whitespace-nowrap">S/ {{ $item['subtotal'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="border-t-2 border-gray-200">
                                <td colspan="3" class="px-4 py-3 text-right font-semibold text-gray-700">Total</td>
                                <td class="px-4 py-3 text-right font-semibold text-gray-900 whitespace-nowrap">S/ {{ $quote['total'] }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            {{-- Solo se reenvían ids, cantidades, medio de pago y token. Precio, total y vendedor los pone el servidor. --}}
            <div class="flex items-center justify-end gap-3 px-4 sm:px-0">
                <a href="{{ route('sales.create') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                    Modificar
                </a>

                <form method="POST" action="{{ route('sales.store') }}" x-data="{ sending: false }" @submit="sending = true">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">
                    <input type="hidden" name="patient_id" value="{{ $quote['patient']->id }}">
                    <input type="hidden" name="payment_method" value="{{ $paymentMethod }}">
                    @foreach ($quote['items'] as $index => $item)
                        <input type="hidden" name="lines[{{ $index }}][product_id]" value="{{ $item['product']->id }}">
                        <input type="hidden" name="lines[{{ $index }}][quantity]" value="{{ $item['quantity'] }}">
                    @endforeach

                    {{-- Se deshabilita al primer clic; el token de un solo uso protege también en el servidor. --}}
                    <x-primary-button x-bind:disabled="sending" x-text="sending ? 'Registrando…' : 'Confirmar venta'" class="disabled:opacity-50">
                        Confirmar venta
                    </x-primary-button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
