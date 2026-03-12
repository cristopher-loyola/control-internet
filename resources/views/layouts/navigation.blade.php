@php
    $role = auth()->user()->role ?? null;
    $roleColors = config('role_colors.navbar');
    $defaultNavColor = config('role_colors.default_navbar');

    $dashboardRoute = match ($role) {
        'admin' => route('admin.index'),
        'tecnico' => route('tecnico.index'),
        'pagos' => route('pagos.index'),
        'contrataciones' => route('contrataciones.index'),
        default => route('dashboard'),
    };

    $clientesRoute = match ($role) {
        'admin' => route('admin.clientes.index'),
        'contrataciones' => route('contrataciones.clientes.index'),
        default => null,
    };

    $pagosRoute = match ($role) {
        'admin' => route('admin.pagos.index'),
        default => null,
    };

    $recibosRoute = match ($role) {
        'pagos' => route('pagos.recibos'),
        default => null,
    };

    $cortePagosRoute = match ($role) {
        'pagos' => route('pagos.corte'),
        default => null,
    };

    $clientesPagosRoute = match ($role) {
        'pagos' => route('pagos.clientes.index'),
        default => null,
    };

    $clientesActive =
        request()->routeIs('admin.clientes.*') ||
        request()->routeIs('contrataciones.clientes.*');

    $pagosActive = request()->routeIs('admin.pagos.*');
    $recibosActive = request()->routeIs('pagos.recibos*');
    $cortePagosActive = request()->routeIs('pagos.corte*');
    $clientesPagosActive = request()->routeIs('pagos.clientes.*');
    // historial link removido de navbar a petición del usuario
    $corteRoute = $role === 'admin' ? route('admin.corte.view') : null;
    $corteActive = request()->routeIs('admin.corte.*');

    $dashboardActive = (
        request()->routeIs('admin.*') ||
        request()->routeIs('tecnico.*') ||
        request()->routeIs('pagos.*') ||
        request()->routeIs('contrataciones.*') ||
        request()->routeIs('dashboard')
    ) && ! $clientesActive;
@endphp

<nav
    x-data="navbarRole({
        role: @js($role),
        roleColors: @js($roleColors),
        defaultColor: @js($defaultNavColor),
    })"
    x-init="init()"
    :class="navClass"
    class="border-b border-gray-100 dark:border-gray-700 {{ ($roleColors[$role] ?? $defaultNavColor) }}"
>
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ $dashboardRoute }}">
                        @if ($role === 'admin')
                            <img src="{{ asset('images/Clogo.png') }}" class="block h-9 w-auto object-contain" alt="Control Internet Logo" />
                        @elseif ($role === 'pagos')
                            <img src="{{ asset('images/Clogo4.png') }}" class="block h-9 w-auto object-contain" alt="Control Internet Logo" />
                        @elseif ($role === 'contrataciones')
                            <img src="{{ asset('images/Clogo3.png') }}" class="block h-9 w-auto object-contain" alt="Control Internet Logo" />
                        @else
                            <x-application-logo class="block h-9 w-auto fill-current text-gray-800 dark:text-gray-200" />
                        @endif
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden sm:-my-px sm:ms-12 sm:flex sm:items-center sm:gap-1">
                    <x-nav-link :href="$dashboardRoute" :active="$dashboardActive" class="group flex items-center gap-2 px-3 py-2 rounded-lg transition-all duration-200 hover:bg-white/10 {{ $dashboardActive ? 'bg-white/10' : '' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                        </svg>
                        <span class="text-sm font-medium">{{ __('Dashboard') }}</span>
                    </x-nav-link>
                    <span class="h-4 w-px bg-white/20 mx-1"></span>
                    @if ($clientesRoute)
                        <x-nav-link :href="$clientesRoute" :active="$clientesActive" class="group flex items-center gap-2 px-3 py-2 rounded-lg transition-all duration-200 hover:bg-white/10 {{ $clientesActive ? 'bg-white/10' : '' }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                            <span class="text-sm font-medium">{{ __('Clientes') }}</span>
                        </x-nav-link>
                        <span class="h-4 w-px bg-white/20 mx-1"></span>
                    @endif
                    @if ($pagosRoute)
                        <x-nav-link :href="$pagosRoute" :active="$pagosActive" class="group flex items-center gap-2 px-3 py-2 rounded-lg transition-all duration-200 hover:bg-white/10 {{ $pagosActive ? 'bg-white/10' : '' }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                            <span class="text-sm font-medium">{{ __('Pagos') }}</span>
                        </x-nav-link>
                        <span class="h-4 w-px bg-white/20 mx-1"></span>
                    @endif
                    @if ($recibosRoute)
                        <x-nav-link :href="$recibosRoute" :active="$recibosActive" class="group flex items-center gap-2 px-3 py-2 rounded-lg transition-all duration-200 hover:bg-white/10 {{ $recibosActive ? 'bg-white/10' : '' }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <span class="text-sm font-medium">{{ __('Recibos') }}</span>
                        </x-nav-link>
                        <span class="h-4 w-px bg-white/20 mx-1"></span>
                    @endif
                    @if ($cortePagosRoute)
                        <x-nav-link :href="$cortePagosRoute" :active="$cortePagosActive" class="group flex items-center gap-2 px-3 py-2 rounded-lg transition-all duration-200 hover:bg-white/10 {{ $cortePagosActive ? 'bg-white/10' : '' }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                            </svg>
                            <span class="text-sm font-medium">Corte</span>
                        </x-nav-link>
                        <span class="h-4 w-px bg-white/20 mx-1"></span>
                    @endif
                    @if ($clientesPagosRoute)
                        <x-nav-link :href="$clientesPagosRoute" :active="$clientesPagosActive" class="group flex items-center gap-2 px-3 py-2 rounded-lg transition-all duration-200 hover:bg-white/10 {{ $clientesPagosActive ? 'bg-white/10' : '' }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                            <span class="text-sm font-medium">Clientes</span>
                        </x-nav-link>
                        <span class="h-4 w-px bg-white/20 mx-1"></span>
                    @endif
                    @if ($corteRoute)
                        <x-nav-link :href="$corteRoute" :active="$corteActive" class="group flex items-center gap-2 px-3 py-2 rounded-lg transition-all duration-200 hover:bg-white/10 {{ $corteActive ? 'bg-white/10' : '' }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                            </svg>
                            <span class="text-sm font-medium">Corte</span>
                        </x-nav-link>
                    @endif
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-10">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border-0 text-sm leading-4 font-medium rounded-md text-white bg-transparent hover:text-white focus:outline-none transition ease-in-out duration-150">
                            <div class="flex flex-col items-start">
                                <span class="text-white">{{ Auth::user()->name }}</span>
                                <span class="text-[11px] font-semibold text-white/80 uppercase tracking-wide">
                                    {{ Auth::user()->role_label }}
                                </span>
                            </div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-white hover:text-white hover:bg-black/20 focus:outline-none focus:bg-black/20 focus:text-white transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="$dashboardRoute" :active="$dashboardActive" class="flex items-center gap-3 px-4 py-3">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                </svg>
                {{ __('Dashboard') }}
            </x-responsive-nav-link>
            @if ($clientesRoute)
                <x-responsive-nav-link :href="$clientesRoute" :active="$clientesActive" class="flex items-center gap-3 px-4 py-3">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                    {{ __('Clientes') }}
                </x-responsive-nav-link>
            @endif
            @if ($pagosRoute)
                <x-responsive-nav-link :href="$pagosRoute" :active="$pagosActive" class="flex items-center gap-3 px-4 py-3">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    {{ __('Pagos') }}
                </x-responsive-nav-link>
            @endif
            @if ($recibosRoute)
                <x-responsive-nav-link :href="$recibosRoute" :active="$recibosActive" class="flex items-center gap-3 px-4 py-3">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    {{ __('Recibos') }}
                </x-responsive-nav-link>
            @endif
            @if ($cortePagosRoute)
                <x-responsive-nav-link :href="$cortePagosRoute" :active="$cortePagosActive" class="flex items-center gap-3 px-4 py-3">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                    </svg>
                    Corte
                </x-responsive-nav-link>
            @endif
            @if ($clientesPagosRoute)
                <x-responsive-nav-link :href="$clientesPagosRoute" :active="$clientesPagosActive" class="flex items-center gap-3 px-4 py-3">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                    Clientes
                </x-responsive-nav-link>
            @endif
            @if ($corteRoute)
                <x-responsive-nav-link :href="$corteRoute" :active="$corteActive" class="flex items-center gap-3 px-4 py-3">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                    </svg>
                    Corte
                </x-responsive-nav-link>
            @endif
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200 dark:border-gray-600">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800 dark:text-gray-200">{{ Auth::user()->name }}</div>
                <div class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wide">
                    {{ Auth::user()->role_label }}
                </div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
