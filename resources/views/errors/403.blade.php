<x-app-layout>
    <div class="py-16">
        <div class="max-w-xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-8 text-center">
                <p class="text-5xl font-bold text-gray-300">403</p>
                <h1 class="mt-4 text-xl font-semibold text-gray-800">No tienes permiso para esta acción</h1>
                <p class="mt-2 text-sm text-gray-600">
                    Si crees que es un error, comunícate con el administrador del sistema.
                </p>

                <a href="{{ route('dashboard') }}" class="mt-6 inline-flex items-center px-4 py-2 bg-indigo-600 rounded-md text-sm font-medium text-white hover:bg-indigo-700">
                    Volver al dashboard
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
