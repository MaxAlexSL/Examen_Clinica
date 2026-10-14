<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Roles</h2>

            @can('roles.crear')
                <a href="{{ route('roles.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 rounded-md text-sm font-medium text-white hover:bg-indigo-700">
                    Nuevo rol
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                        <tr>
                            <th class="px-6 py-3">Nombre</th>
                            <th class="px-6 py-3">Permisos</th>
                            <th class="px-6 py-3">Usuarios</th>
                            <th class="px-6 py-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($roles as $role)
                            <tr>
                                <td class="px-6 py-3 font-medium text-gray-900">{{ $role->name }}</td>
                                <td class="px-6 py-3 text-gray-600">
                                    {{ $role->permissions_count }}
                                    @if ($role->permissions_count === 0)
                                        <span class="ms-2 inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800">Sin permisos</span>
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-gray-600">{{ $role->users_count }}</td>
                                <td class="px-6 py-3 text-right">
                                    @can('roles.editar')
                                        <a href="{{ route('roles.edit', $role) }}" class="text-indigo-600 hover:text-indigo-800 font-medium">Editar</a>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-6 text-center text-gray-500">No hay roles registrados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
