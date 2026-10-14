{{-- Botón para desactivar/activar (solo cambia status; el servidor exige productos.desactivar). Variable: $product --}}
@can('productos.desactivar')
    @if ($product->status === 'ACTIVO')
        <form method="POST" action="{{ route('products.deactivate', $product) }}" class="inline"
              onsubmit="return confirm('¿Desactivar este producto? El registro no se elimina.');">
            @csrf
            @method('PATCH')
            <button type="submit" class="text-red-600 hover:text-red-800 font-medium">Desactivar</button>
        </form>
    @else
        <form method="POST" action="{{ route('products.activate', $product) }}" class="inline">
            @csrf
            @method('PATCH')
            <button type="submit" class="text-green-700 hover:text-green-900 font-medium">Activar</button>
        </form>
    @endif
@endcan
