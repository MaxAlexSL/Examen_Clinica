@php
    // Opciones del menú: ruta, texto, permiso requerido y rutas que la marcan como activa.
    // Cada opción aparece solo si la ruta ya existe y el usuario tiene el permiso.
    // Ocultar el enlace es solo estética: el servidor valida el permiso en cada ruta.
    $menu = [
        ['route' => 'users.index', 'label' => 'Usuarios', 'permission' => 'usuarios.ver', 'active' => 'users.*'],
        ['route' => 'roles.index', 'label' => 'Roles', 'permission' => 'roles.ver', 'active' => 'roles.*'],
        ['route' => 'patients.index', 'label' => 'Pacientes', 'permission' => 'pacientes.ver', 'active' => 'patients.*'],
        ['route' => 'products.index', 'label' => 'Productos', 'permission' => 'productos.ver', 'active' => 'products.*'],
        ['route' => 'sales.index', 'label' => 'Ventas', 'permission' => 'ventas.ver', 'active' => ['sales.index', 'sales.show']],
        ['route' => 'sales.create', 'label' => 'Nueva venta', 'permission' => 'ventas.crear', 'active' => 'sales.create'],
    ];

    $user = Auth::user();
    $roleName = $user->getRoleNames()->first() ?? 'Sin rol';
@endphp

<nav x-data="{ open: false }" class="bg-white border-b border-gray-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Nombre del sistema -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
                        <x-application-logo class="block h-8 w-auto fill-current text-indigo-600" />
                        <span class="font-semibold text-gray-800">{{ config('app.name') }}</span>
                    </a>
                </div>

                <!-- Menú principal -->
                <div class="hidden space-x-6 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        Dashboard
                    </x-nav-link>

                    @foreach ($menu as $item)
                        @if (Route::has($item['route']))
                            @can($item['permission'])
                                <x-nav-link :href="route($item['route'])" :active="request()->routeIs($item['active'])">
                                    {{ $item['label'] }}
                                </x-nav-link>
                            @endcan
                        @endif
                    @endforeach
                </div>
            </div>

            <!-- Usuario y cierre de sesión -->
            <div class="hidden sm:flex sm:items-center sm:gap-4">
                <div class="text-right leading-tight">
                    <div class="text-sm font-medium text-gray-800">{{ $user->name }}</div>
                    <div class="text-xs text-gray-500">{{ $roleName }}</div>
                </div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition">
                        Cerrar sesión
                    </button>
                </form>
            </div>

            <!-- Botón de menú (móvil) -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out" aria-label="Abrir menú">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Menú móvil -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                Dashboard
            </x-responsive-nav-link>

            @foreach ($menu as $item)
                @if (Route::has($item['route']))
                    @can($item['permission'])
                        <x-responsive-nav-link :href="route($item['route'])" :active="request()->routeIs($item['active'])">
                            {{ $item['label'] }}
                        </x-responsive-nav-link>
                    @endcan
                @endif
            @endforeach
        </div>

        <div class="pt-4 pb-3 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">{{ $user->name }}</div>
                <div class="text-sm text-gray-500">{{ $roleName }}</div>
            </div>

            <form method="POST" action="{{ route('logout') }}" class="mt-3 px-4">
                @csrf
                <button type="submit" class="w-full inline-flex justify-center px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    Cerrar sesión
                </button>
            </form>
        </div>
    </div>
</nav>
