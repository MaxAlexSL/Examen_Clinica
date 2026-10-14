{{-- Campos compartidos por crear y editar rol. Variables: $role, $permissionsByModule, $selected --}}
@php
    $checked = old('permissions', $selected);
@endphp

<div class="space-y-6">
    <div>
        <x-input-label for="name" value="Nombre del rol" />
        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full sm:w-96" :value="old('name', $role->name)" required autofocus />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div>
        <h3 class="text-sm font-medium text-gray-700">Permisos</h3>
        <p class="text-xs text-gray-500">Un rol sin permisos no puede operar en el sistema.</p>
        <x-input-error :messages="$errors->get('permissions')" class="mt-2" />
        <x-input-error :messages="$errors->get('permissions.*')" class="mt-2" />

        <div class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach ($permissionsByModule as $module => $permissions)
                <fieldset class="rounded-md border border-gray-200 p-4">
                    <legend class="px-1 text-sm font-semibold text-gray-800">{{ $module }}</legend>

                    <div class="space-y-2">
                        @foreach ($permissions as $permission)
                            <label class="flex items-start gap-2">
                                <input type="checkbox" name="permissions[]" value="{{ $permission->name }}"
                                       class="mt-0.5 rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                       @checked(in_array($permission->name, $checked, true))>
                                <span class="text-sm">
                                    <span class="text-gray-800">{{ $permission->display_name }}</span>
                                    <code class="ms-1 text-xs text-gray-500">{{ $permission->name }}</code>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>
            @endforeach
        </div>
    </div>
</div>
