<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Pacientes</h2>

            @can('pacientes.crear')
                <a href="{{ route('patients.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 rounded-md text-sm font-medium text-white hover:bg-indigo-700">
                    Afiliar paciente
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <!-- Búsqueda por documento, nombres o apellidos -->
            <form method="GET" action="{{ route('patients.index') }}" class="flex gap-2 px-4 sm:px-0">
                <x-text-input name="q" type="search" :value="$search" placeholder="Buscar por documento, nombres o apellidos" class="w-full sm:w-96" />
                <button type="submit" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    Buscar
                </button>
                @if ($search !== '')
                    <a href="{{ route('patients.index') }}" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-900 self-center">Limpiar</a>
                @endif
            </form>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                        <tr>
                            <th class="px-6 py-3">Código</th>
                            <th class="px-6 py-3">Documento</th>
                            <th class="px-6 py-3">Nombres</th>
                            <th class="px-6 py-3">Apellidos</th>
                            <th class="px-6 py-3">Estado</th>
                            <th class="px-6 py-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($patients as $patient)
                            <tr>
                                <td class="px-6 py-3 font-mono text-gray-900">{{ $patient->affiliation_code }}</td>
                                <td class="px-6 py-3 text-gray-600">{{ $patient->document_type }} {{ $patient->document_number }}</td>
                                <td class="px-6 py-3 text-gray-900">{{ $patient->first_names }}</td>
                                <td class="px-6 py-3 text-gray-900">{{ $patient->last_names }}</td>
                                <td class="px-6 py-3">@include('patients.partials.status-badge')</td>
                                <td class="px-6 py-3 text-right whitespace-nowrap space-x-3">
                                    <a href="{{ route('patients.show', $patient) }}" class="text-gray-700 hover:text-gray-900 font-medium">Ver</a>
                                    @can('pacientes.editar')
                                        <a href="{{ route('patients.edit', $patient) }}" class="text-indigo-600 hover:text-indigo-800 font-medium">Editar</a>
                                    @endcan
                                    @include('patients.partials.status-toggle')
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-6 text-center text-gray-500">No se encontraron pacientes.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="px-4 sm:px-0">
                {{ $patients->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
