<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?? config('app.name', 'Control Internet') }}</title>
        <link rel="icon" type="image/png" href="{{ asset('images/clogo2.png') }}">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
        <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <style>[x-cloak]{display:none!important} aside a { text-decoration: none !important; }</style>
    </head>
    <body class="font-sans antialiased bg-white" x-data="{ sidebarOpen: false }">

        <!-- Header móvil solo -->
        <header class="bg-green-800 text-white sticky top-0 z-50 md:hidden">
            <div class="flex items-center justify-between px-4 py-3">
                <div class="flex items-center gap-2">
                    <button @click="sidebarOpen = !sidebarOpen" class="p-2 rounded-lg hover:bg-green-700 -ml-2" aria-label="Menu">
                        <svg x-show="!sidebarOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                        <svg x-show="sidebarOpen" x-cloak class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                    <img src="{{ asset('images/Clogo6.png') }}" class="h-8 w-auto object-contain" alt="Control Internet Logo">
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="p-2 rounded-lg hover:bg-green-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                    </button>
                </form>
            </div>
        </header>

        <div class="flex min-h-screen" :class="sidebarOpen ? 'overflow-hidden' : ''">

            <!-- Sidebar MÓVIL -->
            <aside x-cloak
                   x-show="sidebarOpen"
                   class="fixed top-0 bottom-0 left-0 z-[100] w-64 bg-green-800 text-white flex-shrink-0 md:hidden h-screen"
                   x-transition:enter="transition-transform duration-300 ease-out"
                   x-transition:enter-start="-translate-x-full"
                   x-transition:enter-end="translate-x-0"
                   x-transition:leave="transition-transform duration-300 ease-in"
                   x-transition:leave-start="translate-x-0"
                   x-transition:leave-end="-translate-x-full">
                <div class="flex flex-col h-screen overflow-hidden">
                    <div class="flex items-center justify-between px-4 py-3 border-b border-green-700 bg-green-800">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-green-600 flex items-center justify-center font-bold text-lg">
                                {{ substr(auth()->user()->name, 0, 1) }}
                            </div>
                            <div>
                                <p class="font-medium text-sm">{{ auth()->user()->name }}</p>
                                <p class="text-xs text-green-200 capitalize">{{ auth()->user()->role }}</p>
                            </div>
                        </div>
                        <button @click="sidebarOpen = false" class="p-2 rounded-lg hover:bg-green-700 text-green-200 hover:text-white">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
                        <a href="{{ route('tecnico.index') }}"
                           class="flex items-center gap-3 px-3 py-3 rounded-lg {{ request()->routeIs('tecnico.index') ? 'bg-green-600 text-white' : 'text-white hover:bg-green-700' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                            </svg>
                            <span class="text-sm font-medium">Dashboard</span>
                        </a>

                        <a href="{{ route('tecnico.clientes.index') }}"
                           class="flex items-center gap-3 px-3 py-3 rounded-lg {{ request()->routeIs('tecnico.clientes.*') ? 'bg-green-600 text-white' : 'text-white hover:bg-green-700' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                            <span class="text-sm font-medium">Clientes</span>
                        </a>

                        <a href="{{ route('tecnico.cortes.index') }}"
                           class="flex items-center gap-3 px-3 py-3 rounded-lg {{ request()->routeIs('tecnico.cortes.*') ? 'bg-green-600 text-white' : 'text-white hover:bg-green-700' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.121 14.121L19 19m-7-7l7 7m-7-7l-2.879 2.879M12 12L9.121 9.121m0 0L5 5m4.121 4.121L5 19m10.879-10.879L19 5m-4.121 4.121l-2.879-2.879M12 12l2.879-2.879"/>
                            </svg>
                            <span class="text-sm font-medium">Cortes</span>
                        </a>

                        <a href="{{ route('tecnico.reactivaciones.index') }}"
                           class="flex items-center gap-3 px-3 py-3 rounded-lg {{ request()->routeIs('tecnico.reactivaciones.*') ? 'bg-green-600 text-white' : 'text-white hover:bg-green-700' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span class="text-sm font-medium">Reactivaciones</span>
                        </a>

                        <a href="{{ route('profile.edit') }}"
                           class="flex items-center gap-3 px-3 py-3 rounded-lg {{ request()->routeIs('profile.*') ? 'bg-green-600 text-white' : 'text-white hover:bg-green-700' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            <span class="text-sm font-medium">Perfil</span>
                        </a>
                    </nav>

                    <div class="p-3 border-t border-green-700">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="flex items-center gap-3 px-3 py-3 rounded-lg text-white hover:bg-green-700 w-full">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                </svg>
                                <span class="text-sm font-medium">Cerrar sesión</span>
                            </button>
                        </form>
                    </div>
                </div>
            </aside>

            <!-- Sidebar DESKTOP: siempre visible -->
            <aside class="hidden md:flex flex-col w-64 bg-green-800 text-white flex-shrink-0 sticky top-0 h-screen">
                <div class="flex flex-col h-full">
                    <div class="flex items-center gap-3 px-4 py-4 border-b border-green-700">
                        <div class="w-10 h-10 rounded-full bg-green-600 flex items-center justify-center font-bold text-lg">
                            {{ substr(auth()->user()->name, 0, 1) }}
                        </div>
                        <div>
                            <p class="font-medium text-sm">{{ auth()->user()->name }}</p>
                            <p class="text-xs text-green-200 capitalize">{{ auth()->user()->role }}</p>
                        </div>
                    </div>

                    <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
                        <a href="{{ route('tecnico.index') }}"
                           class="flex items-center gap-3 px-3 py-3 rounded-lg {{ request()->routeIs('tecnico.index') ? 'bg-green-600 text-white' : 'text-white hover:bg-green-700' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                            </svg>
                            <span class="text-sm font-medium">Dashboard</span>
                        </a>

                        <a href="{{ route('tecnico.clientes.index') }}"
                           class="flex items-center gap-3 px-3 py-3 rounded-lg {{ request()->routeIs('tecnico.clientes.*') ? 'bg-green-600 text-white' : 'text-white hover:bg-green-700' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                            <span class="text-sm font-medium">Clientes</span>
                        </a>

                        <a href="{{ route('tecnico.cortes.index') }}"
                           class="flex items-center gap-3 px-3 py-3 rounded-lg {{ request()->routeIs('tecnico.cortes.*') ? 'bg-green-600 text-white' : 'text-white hover:bg-green-700' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.121 14.121L19 19m-7-7l7 7m-7-7l-2.879 2.879M12 12L9.121 9.121m0 0L5 5m4.121 4.121L5 19m10.879-10.879L19 5m-4.121 4.121l-2.879-2.879M12 12l2.879-2.879"/>
                            </svg>
                            <span class="text-sm font-medium">Cortes</span>
                        </a>

                        <a href="{{ route('tecnico.reactivaciones.index') }}"
                           class="flex items-center gap-3 px-3 py-3 rounded-lg {{ request()->routeIs('tecnico.reactivaciones.*') ? 'bg-green-600 text-white' : 'text-white hover:bg-green-700' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span class="text-sm font-medium">Reactivaciones</span>
                        </a>

                        <a href="{{ route('profile.edit') }}"
                           class="flex items-center gap-3 px-3 py-3 rounded-lg {{ request()->routeIs('profile.*') ? 'bg-green-600 text-white' : 'text-white hover:bg-green-700' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            <span class="text-sm font-medium">Perfil</span>
                        </a>
                    </nav>

                    <div class="p-3 border-t border-green-700">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="flex items-center gap-3 px-3 py-3 rounded-lg text-white hover:bg-green-700 w-full">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                </svg>
                                <span class="text-sm font-medium">Cerrar sesión</span>
                            </button>
                        </form>
                    </div>
                </div>
            </aside>

            <main class="flex-1 overflow-y-auto">
                {{ $slot }}
            </main>

        </div>
    </body>
</html>
