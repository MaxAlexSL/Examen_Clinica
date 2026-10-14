{{-- Campos compartidos por afiliar y editar paciente. Variable: $patient --}}
@php
    $currentType = old('document_type', $patient->document_type);
@endphp

<div class="space-y-5">
    @if ($patient->exists)
        <div>
            <x-input-label value="Código de afiliación" />
            <p class="mt-1 font-mono text-gray-900">{{ $patient->affiliation_code }}</p>
            <p class="text-xs text-gray-500">Generado por el sistema; no se puede modificar.</p>
        </div>
    @endif

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
        <div>
            <x-input-label for="document_type" value="Tipo de documento" />
            <select id="document_type" name="document_type" required class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                <option value="" disabled @selected($currentType === null)>Selecciona</option>
                @foreach (\App\Http\Requests\StorePatientRequest::DOCUMENT_TYPES as $type)
                    <option value="{{ $type }}" @selected($currentType === $type)>{{ $type }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('document_type')" class="mt-2" />
        </div>

        <div class="sm:col-span-2">
            <x-input-label for="document_number" value="Número de documento" />
            <x-text-input id="document_number" name="document_number" type="text" maxlength="12" class="mt-1 block w-full" :value="old('document_number', $patient->document_number)" required />
            <p class="mt-1 text-xs text-gray-500">DNI: 8 dígitos. CE / Pasaporte: 6 a 12 caracteres alfanuméricos.</p>
            <x-input-error :messages="$errors->get('document_number')" class="mt-2" />
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
            <x-input-label for="first_names" value="Nombres" />
            <x-text-input id="first_names" name="first_names" type="text" maxlength="100" class="mt-1 block w-full" :value="old('first_names', $patient->first_names)" required />
            <x-input-error :messages="$errors->get('first_names')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="last_names" value="Apellidos" />
            <x-text-input id="last_names" name="last_names" type="text" maxlength="100" class="mt-1 block w-full" :value="old('last_names', $patient->last_names)" required />
            <x-input-error :messages="$errors->get('last_names')" class="mt-2" />
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
        <div>
            <x-input-label for="birth_date" value="Fecha de nacimiento" />
            <x-text-input id="birth_date" name="birth_date" type="date" max="{{ now()->toDateString() }}" class="mt-1 block w-full" :value="old('birth_date', $patient->birth_date?->toDateString())" required />
            <x-input-error :messages="$errors->get('birth_date')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="phone" value="Teléfono (opcional)" />
            <x-text-input id="phone" name="phone" type="tel" maxlength="16" class="mt-1 block w-full" :value="old('phone', $patient->phone)" />
            <x-input-error :messages="$errors->get('phone')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="email" value="Correo (opcional)" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $patient->email)" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>
    </div>

    @unless ($patient->exists)
        <p class="text-sm text-gray-500">La fecha de afiliación será la de hoy ({{ now()->format('d/m/Y') }}) y el paciente quedará ACTIVO.</p>
    @endunless
</div>
