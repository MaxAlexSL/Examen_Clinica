<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Paciente <span class="font-mono">{{ $patient->affiliation_code }}</span>
            </h2>

            <div class="flex items-center gap-4 text-sm">
                @include('patients.partials.status-toggle')
                @can('pacientes.editar')
                    <a href="{{ route('patients.edit', $patient) }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 rounded-md font-medium text-white hover:bg-indigo-700">
                        Editar
                    </a>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Ficha del paciente -->
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                    <div>
                        <dt class="text-gray-500">Código de afiliación</dt>
                        <dd class="mt-1 font-mono text-gray-900">{{ $patient->affiliation_code }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Estado</dt>
                        <dd class="mt-1">@include('patients.partials.status-badge')</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Documento</dt>
                        <dd class="mt-1 text-gray-900">{{ $patient->document_type }} {{ $patient->document_number }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Fecha de afiliación</dt>
                        <dd class="mt-1 text-gray-900">{{ $patient->affiliation_date->format('d/m/Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Nombres</dt>
                        <dd class="mt-1 text-gray-900">{{ $patient->first_names }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Apellidos</dt>
                        <dd class="mt-1 text-gray-900">{{ $patient->last_names }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Fecha de nacimiento</dt>
                        <dd class="mt-1 text-gray-900">
                            {{ $patient->birth_date->format('d/m/Y') }}
                            <span class="text-gray-500">({{ (int) $patient->birth_date->diffInYears(now()) }} años)</span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Teléfono</dt>
                        <dd class="mt-1 text-gray-900">{{ $patient->phone ?? '—' }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-gray-500">Correo electrónico</dt>
                        <dd class="mt-1 text-gray-900">{{ $patient->email ?? '—' }}</dd>
                    </div>
                </dl>
            </div>

            <!-- Historial de ventas (se completa con el módulo de ventas) -->
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-800">Historial de ventas</h3>
                <p class="mt-2 text-sm text-gray-500">Disponible cuando exista el módulo de ventas.</p>
            </div>

            <a href="{{ route('patients.index') }}" class="inline-block text-sm text-gray-600 hover:text-gray-900">&larr; Volver al listado</a>
        </div>
    </div>
</x-app-layout>
