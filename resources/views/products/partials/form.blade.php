{{-- Campos compartidos por crear y editar producto. Variable: $product --}}
<div class="space-y-5">
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
        <div>
            <x-input-label for="code" value="Código" />
            <x-text-input id="code" name="code" type="text" maxlength="20" class="mt-1 block w-full font-mono uppercase" :value="old('code', $product->code)" required autofocus />
            <p class="mt-1 text-xs text-gray-500">Letras, números y guiones (ej. PRD-001).</p>
            <x-input-error :messages="$errors->get('code')" class="mt-2" />
        </div>

        <div class="sm:col-span-2">
            <x-input-label for="name" value="Nombre" />
            <x-text-input id="name" name="name" type="text" maxlength="150" class="mt-1 block w-full" :value="old('name', $product->name)" required />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>
    </div>

    <div>
        <x-input-label for="description" value="Descripción (opcional)" />
        <textarea id="description" name="description" rows="3" maxlength="1000" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('description', $product->description) }}</textarea>
        <x-input-error :messages="$errors->get('description')" class="mt-2" />
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
            <x-input-label for="price" value="Precio (S/)" />
            <x-text-input id="price" name="price" type="number" step="0.01" min="0.01" class="mt-1 block w-full" :value="old('price', $product->price)" required />
            <x-input-error :messages="$errors->get('price')" class="mt-2" />
        </div>

        <div>
            @if ($product->exists)
                {{-- RN-07: tras el alta el stock solo cambia por ventas; no se envía en el formulario. --}}
                <x-input-label value="Stock actual" />
                <p class="mt-1 px-3 py-2 bg-gray-100 border border-gray-200 rounded-md text-gray-700">{{ $product->stock }}</p>
                <p class="mt-1 text-xs text-gray-500">El stock solo cambia con las ventas.</p>
            @else
                <x-input-label for="stock" value="Stock inicial" />
                <x-text-input id="stock" name="stock" type="number" step="1" min="0" class="mt-1 block w-full" :value="old('stock', 0)" required />
                <x-input-error :messages="$errors->get('stock')" class="mt-2" />
            @endif
        </div>
    </div>
</div>
