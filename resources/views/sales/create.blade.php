@php
    // Datos para Alpine.js. Precio y stock se muestran solo como referencia:
    // el servidor vuelve a leerlos de la BD al revisar y al confirmar (RN-10).
    $patientOptions = $patients->map(fn ($p) => [
        'id' => $p->id,
        'name' => "{$p->last_names}, {$p->first_names}",
        'detail' => "{$p->affiliation_code} · {$p->document_type} {$p->document_number}",
    ])->values();

    $productOptions = $products->map(fn ($p) => [
        'id' => $p->id,
        'name' => $p->name,
        'code' => $p->code,
        'price' => (float) $p->price,
        'stock' => $p->stock,
    ])->values();

    $oldLines = collect(old('lines', [['product_id' => '', 'quantity' => 1]]))
        ->map(fn ($line) => ['product_id' => (string) ($line['product_id'] ?? ''), 'quantity' => $line['quantity'] ?? 1])
        ->values();
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Nueva venta</h2>
            <x-back-button :href="route('sales.index')" />
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <!-- Errores de validación y de negocio (stock, paciente inactivo, etc.) -->
            @if ($errors->any())
                <div class="mb-4 rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">
                    <p class="font-medium">No se pudo revisar la venta:</p>
                    <ul class="mt-1 list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('sales.review') }}"
                  class="bg-white shadow-sm sm:rounded-lg p-6 space-y-6"
                  x-data="saleForm(@js($patientOptions), @js($productOptions), @js($oldLines), @js((string) old('patient_id', '')))">
                @csrf

                <!-- Paciente (solo ACTIVOS), con búsqueda simple -->
                <div>
                    <x-input-label for="patient_search" value="Paciente" />
                    <x-text-input id="patient_search" type="search" x-model="patientSearch" x-on:keydown.enter.prevent="" placeholder="Escribe código, documento o nombre para buscar" class="mt-1 block w-full sm:w-96" />

                    {{-- Valor que se envía. La lista de abajo solo sirve para elegir. --}}
                    <input type="hidden" name="patient_id" :value="patientId">

                    {{-- Resultados: aparecen solo al escribir y la altura se ajusta a la cantidad (sin scroll). --}}
                    <ul x-show="hasSearch() && matchingPatients().length > 0" role="listbox" aria-label="Pacientes encontrados"
                        class="mt-2 w-full sm:w-[32rem] rounded-md border border-gray-200 bg-white shadow-sm divide-y divide-gray-100 overflow-hidden">
                        <template x-for="patient in visiblePatients()" :key="patient.id">
                            <li>
                                <button type="button" @click="selectPatient(patient)" role="option" :aria-selected="isSelected(patient)"
                                        class="flex w-full items-center justify-between gap-3 px-3 py-2 text-left text-sm transition-colors"
                                        :class="isSelected(patient) ? 'bg-indigo-50' : 'hover:bg-gray-50'">
                                    <span>
                                        <span class="block font-medium" :class="isSelected(patient) ? 'text-indigo-700' : 'text-gray-900'" x-text="patient.name"></span>
                                        <span class="block text-xs text-gray-500" x-text="patient.detail"></span>
                                    </span>
                                    <span x-show="isSelected(patient)" class="text-xs font-medium text-indigo-600">Seleccionado</span>
                                </button>
                            </li>
                        </template>
                        <li x-show="matchingPatients().length > maxResults" class="px-3 py-2 text-xs text-gray-500"
                            x-text="`y ${matchingPatients().length - maxResults} más… sigue escribiendo para afinar la búsqueda.`"></li>
                    </ul>

                    {{-- Sin coincidencias: el paciente debe afiliarse antes de poder comprar. --}}
                    <div x-show="hasSearch() && matchingPatients().length === 0"
                         class="mt-2 w-full sm:w-[32rem] rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800">
                        No hay coincidencias. Solicite la afiliación del paciente.
                    </div>

                    {{-- Paciente elegido --}}
                    <div x-show="selectedPatient() && ! hasSearch()"
                         class="mt-2 w-full sm:w-[32rem] flex items-center justify-between gap-3 rounded-md border border-indigo-200 bg-indigo-50 px-3 py-2 text-sm">
                        <span>
                            <span class="block font-medium text-indigo-800" x-text="selectedPatient()?.name"></span>
                            <span class="block text-xs text-indigo-700" x-text="selectedPatient()?.detail"></span>
                        </span>
                        <button type="button" @click="patientId = ''" class="text-xs font-medium text-indigo-700 hover:text-indigo-900">Cambiar</button>
                    </div>
                    <p x-show="! selectedPatient() && ! hasSearch()" class="mt-2 text-sm text-gray-500">
                        Escribe el código, documento o nombre del paciente para buscarlo.
                    </p>
                    <x-input-error :messages="$errors->get('patient_id')" class="mt-2" />
                </div>

                <!-- Líneas de productos -->
                <div>
                    <div class="flex items-center justify-between">
                        <x-input-label value="Productos" />
                        <button type="button" @click="addLine()" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">+ Agregar producto</button>
                    </div>

                    <div class="mt-2 space-y-3">
                        <template x-for="(line, index) in lines" :key="line.key">
                            <div class="grid grid-cols-12 gap-3 items-center">
                                <select :name="`lines[${index}][product_id]`" x-model="line.product_id" required
                                        class="col-span-12 sm:col-span-6 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                                    <option value="">Selecciona un producto</option>
                                    <template x-for="product in products" :key="product.id">
                                        <option :value="product.id" :disabled="product.stock === 0"
                                                :selected="String(product.id) === line.product_id"
                                                x-text="`${product.code} · ${product.name} · S/ ${product.price.toFixed(2)} · stock ${product.stock}` + (product.stock === 0 ? ' (sin stock)' : '')"></option>
                                    </template>
                                </select>

                                <input type="number" :name="`lines[${index}][quantity]`" x-model="line.quantity" min="1" step="1" required
                                       onfocus="this.select()" aria-label="Cantidad"
                                       class="col-span-4 sm:col-span-2 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">

                                <span class="col-span-6 sm:col-span-3 text-right text-sm text-gray-700" x-text="'S/ ' + subtotal(line).toFixed(2)"></span>

                                <button type="button" @click="removeLine(index)" x-show="lines.length > 1"
                                        class="col-span-2 sm:col-span-1 text-sm text-red-600 hover:text-red-800" aria-label="Quitar línea">Quitar</button>
                            </div>
                        </template>
                    </div>

                    <p class="mt-3 text-right text-sm text-gray-600">
                        Total referencial: <span class="font-semibold text-gray-900" x-text="'S/ ' + total().toFixed(2)"></span>
                    </p>
                    <p class="text-right text-xs text-gray-500">El total definitivo se calcula en el servidor con los precios vigentes.</p>
                </div>

                <!-- Medio de pago -->
                <div>
                    <x-input-label value="Medio de pago" />
                    <div class="mt-2 flex gap-6">
                        @foreach (\App\Services\SaleService::PAYMENT_METHODS as $method)
                            <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                <input type="radio" name="payment_method" value="{{ $method }}" required
                                       @checked(old('payment_method', 'EFECTIVO') === $method)
                                       class="border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                {{ $method }}
                            </label>
                        @endforeach
                    </div>
                    <x-input-error :messages="$errors->get('payment_method')" class="mt-2" />
                </div>

                <div class="flex justify-end gap-3">
                    <a href="{{ route('sales.index') }}" class="self-center text-sm text-gray-600 hover:text-gray-900">Cancelar</a>
                    <x-primary-button>Revisar venta</x-primary-button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Formulario de venta: filtro de pacientes y líneas dinámicas (solo interfaz).
        // Minúsculas y sin tildes, para que "perez" encuentre "Pérez".
        function normalize(text) {
            return text.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
        }

        // Partes buscables: "AF-000003 · CE 001234567 · Rojas Pérez" → ["af", "000003", "ce", "001234567", "rojas", "perez"].
        function words(text) {
            return normalize(text).split(/[^a-z0-9]+/).filter(Boolean);
        }

        function saleForm(patients, products, oldLines, oldPatientId) {
            let nextKey = 0;

            return {
                patients: patients.map(p => ({ ...p, words: words(`${p.detail} ${p.name}`) })),
                products,
                patientSearch: '',
                patientId: oldPatientId,
                maxResults: 8,
                lines: oldLines.map(line => ({ ...line, key: nextKey++ })),

                init() {
                    this.$watch('patientSearch', () => {
                        const matches = this.matchingPatients();
                        if (! this.hasSearch()) {
                            return;
                        }
                        // Un solo resultado: se selecciona solo. Si el elegido ya no coincide, se quita.
                        if (matches.length === 1) {
                            this.patientId = String(matches[0].id);
                        } else if (! matches.some(p => this.isSelected(p))) {
                            this.patientId = '';
                        }
                    });
                },
                hasSearch() {
                    return this.patientSearch.trim() !== '';
                },
                // Cada palabra escrita debe ser el INICIO de alguna palabra del paciente:
                // "per" encuentra "Pérez", pero "z" o "za" no (evita coincidencias en medio de una palabra).
                matchingPatients() {
                    const terms = words(this.patientSearch);
                    if (terms.length === 0) {
                        return [];
                    }
                    return this.patients.filter(p => terms.every(term => p.words.some(word => word.startsWith(term))));
                },
                visiblePatients() {
                    return this.matchingPatients().slice(0, this.maxResults);
                },
                selectPatient(patient) {
                    this.patientId = String(patient.id);
                    this.patientSearch = '';
                },
                isSelected(patient) {
                    return String(patient.id) === String(this.patientId);
                },
                selectedPatient() {
                    return this.patients.find(p => this.isSelected(p)) ?? null;
                },
                addLine() {
                    this.lines.push({ product_id: '', quantity: 1, key: nextKey++ });
                },
                removeLine(index) {
                    this.lines.splice(index, 1);
                },
                subtotal(line) {
                    const product = this.products.find(p => String(p.id) === String(line.product_id));
                    const quantity = parseInt(line.quantity, 10);
                    return product && quantity > 0 ? product.price * quantity : 0;
                },
                total() {
                    return this.lines.reduce((sum, line) => sum + this.subtotal(line), 0);
                },
            };
        }
    </script>
</x-app-layout>
