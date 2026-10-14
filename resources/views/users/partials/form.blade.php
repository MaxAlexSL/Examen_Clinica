{{-- Campos compartidos por crear y editar usuario. Variables: $user, $roles, $isEdit --}}
@php
    $currentRole = old('role', $user->exists ? $user->getRoleNames()->first() : null);
    $currentActive = old('is_active', $user->is_active ? '1' : '0');
    $isSelf = $isEdit && auth()->user()->is($user);
@endphp

<div class="space-y-5">
    <div>
        <x-input-label for="name" value="Nombre" />
        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="email" value="Correo electrónico" />
        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required />
        <x-input-error :messages="$errors->get('email')" class="mt-2" />
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
            <x-input-label for="password" :value="$isEdit ? 'Nueva contraseña (opcional)' : 'Contraseña'" />
            <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" autocomplete="new-password" :required="! $isEdit" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
            @if ($isEdit)
                <p class="mt-1 text-xs text-gray-500">Déjala en blanco para mantener la actual.</p>
            @endif
        </div>

        <div>
            <x-input-label for="password_confirmation" value="Confirmar contraseña" />
            <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" autocomplete="new-password" :required="! $isEdit" />
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
            <x-input-label for="role" value="Rol" />
            <select id="role" name="role" required class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                <option value="" disabled @selected($currentRole === null)>Selecciona un rol</option>
                @foreach ($roles as $roleName)
                    <option value="{{ $roleName }}" @selected($currentRole === $roleName)>{{ $roleName }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('role')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="is_active" value="Estado" />
            <select id="is_active" name="is_active" required class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                <option value="1" @selected((string) $currentActive === '1')>Activo</option>
                <option value="0" @selected((string) $currentActive === '0')>Inactivo</option>
            </select>
            <x-input-error :messages="$errors->get('is_active')" class="mt-2" />
        </div>
    </div>

    @if ($isSelf)
        <p class="text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded-md px-3 py-2">
            Estás editando tu propio usuario: no puedes cambiar tu rol ni desactivarte.
        </p>
    @endif
</div>
