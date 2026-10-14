{{-- Botón para desactivar/activar (solo cambia status; el servidor exige pacientes.desactivar). Variable: $patient --}}
@can('pacientes.desactivar')
    @if ($patient->status === 'ACTIVO')
        <form method="POST" action="{{ route('patients.deactivate', $patient) }}" class="inline"
              onsubmit="return confirm('¿Desactivar a este paciente? El registro no se elimina.');">
            @csrf
            @method('PATCH')
            <button type="submit" class="text-red-600 hover:text-red-800 font-medium">Desactivar</button>
        </form>
    @else
        <form method="POST" action="{{ route('patients.activate', $patient) }}" class="inline">
            @csrf
            @method('PATCH')
            <button type="submit" class="text-green-700 hover:text-green-900 font-medium">Activar</button>
        </form>
    @endif
@endcan
