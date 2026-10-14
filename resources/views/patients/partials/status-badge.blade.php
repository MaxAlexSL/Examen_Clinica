{{-- Badge del estado del paciente. Variable: $patient --}}
@if ($patient->status === 'ACTIVO')
    <span class="inline-flex rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">ACTIVO</span>
@else
    <span class="inline-flex rounded-full bg-gray-200 px-2.5 py-0.5 text-xs font-medium text-gray-700">INACTIVO</span>
@endif
