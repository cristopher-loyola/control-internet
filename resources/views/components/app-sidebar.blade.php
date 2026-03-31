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
        <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <style>
            [x-cloak] { display: none !important; }
        </style>
    </head>
    <body class="font-sans antialiased bg-white" x-data="{ sidebarOpen: false }">

        <header class="bg-gray-900 text-white sticky top-0 z-50">
            <div class="flex items-center justify-between px-4 py-3">
                <div class="flex items-center gap-3">
                    <button @click="sidebarOpen = !sidebarOpen" class="md:hidden p-2 rounded-lg hover:bg-gray-800">
                        <svg x-show="!sidebarOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                        <svg x-show="sidebarOpen" x-cloak class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                    <img src="{{ asset('images/Clogo.png') }}" class="h-8 w-auto object-contain" alt="Control Internet Logo">
                    <span class="font-semibold text-lg">{{ $headerTitle ?? 'Panel' }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-sm text-gray-400 hidden sm:block">{{ auth()->user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="p-2 rounded-lg hover:bg-gray-800">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        </header>

        <div class="flex min-h-[calc(100vh-3.5rem)]">

            <!-- Sidebar MOBILE: fixed, se muestra/oculta con Alpine -->
            <!-- Sidebar DESKTOP: sticky, siempre visible -->
            <aside x-cloak
                   x-show="sidebarOpen"
                   class="fixed top-0 bottom-0 left-0 z-50 w-64 bg-gray-900 text-white flex-shrink-0 md:hidden h-screen"
                   x-transition:enter="transition-transform duration-300"
                   x-transition:enter-start="-translate-x-full"
                   x-transition:enter-end="translate-x-0"
                   x-transition:leave="transition-transform duration-300"
                   x-transition:leave-start="translate-x-0"
                   x-transition:leave-end="-translate-x-full">
                <div class="flex flex-col h-screen">
                    <div class="flex items-center gap-3 px-4 py-4 border-b border-gray-800">
                        <div class="w-10 h-10 rounded-full bg-indigo-600 flex items-center justify-center font-bold text-lg">
                            {{ substr(auth()->user()->name, 0, 1) }}
                        </div>
                        <div>
                            <p class="font-medium text-sm">{{ auth()->user()->name }}</p>
                            <p class="text-xs text-gray-400 capitalize">{{ auth()->user()->role }}</p>
                        </div>
                    </div>
                    <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
                        <a href="{{ route(auth()->user()->role . '.index') }}"
                           @click="sidebarOpen = false"
                           class="flex items-center gap-3 px-3 py-3 rounded-lg {{ request()->routeIs(auth()->user()->role . '.index') ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                            </svg>
                            <span class="text-sm font-medium">Dashboard</span>
                        </a>
                        <a href="{{ route('profile.edit') }}"
                           @click="sidebarOpen = false"
                           class="flex items-center gap-3 px-3 py-3 rounded-lg {{ request()->routeIs('profile.*') ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            <span class="text-sm font-medium">Perfil</span>
                        </a>

                        @if(in_array(auth()->user()->role, ['chivato', 'pozo_hondo', 'rosalito']))
                        <a href="{{ route(auth()->user()->role . '.pagos') }}"
                           @click="sidebarOpen = false"
                           class="flex items-center gap-3 px-3 py-3 rounded-lg {{ request()->routeIs(auth()->user()->role . '.pagos') ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span class="text-sm font-medium">Pagos</span>
                        </a>

                        <a href="{{ route(auth()->user()->role . '.corte') }}"
                           @click="sidebarOpen = false"
                           class="flex items-center gap-3 px-3 py-3 rounded-lg {{ request()->routeIs(auth()->user()->role . '.corte') ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                            </svg>
                            <span class="text-sm font-medium">Corte</span>
                        </a>

                        <a href="{{ route(auth()->user()->role . '.historial') }}"
                           @click="sidebarOpen = false"
                           class="flex items-center gap-3 px-3 py-3 rounded-lg {{ request()->routeIs(auth()->user()->role . '.historial') ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span class="text-sm font-medium">Historial de Pagos</span>
                        </a>
                    </nav>
                    <div class="p-3 border-t border-gray-800">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="flex items-center gap-3 px-3 py-3 rounded-lg text-gray-300 hover:bg-gray-800 w-full">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                </svg>
                                <span class="text-sm font-medium">Cerrar sesión</span>
                            </button>
                        </form>
                    </div>
                </div>
            </aside>

            <!-- Sidebar DESKTOP: siempre visible, no depende de Alpine -->
            <aside class="hidden md:flex flex-col w-64 bg-gray-900 text-white flex-shrink-0 sticky top-0 h-screen">
                <div class="flex flex-col h-full">
                    <div class="flex items-center gap-3 px-4 py-4 border-b border-gray-800">
                        <div class="w-10 h-10 rounded-full bg-indigo-600 flex items-center justify-center font-bold text-lg">
                            {{ substr(auth()->user()->name, 0, 1) }}
                        </div>
                        <div>
                            <p class="font-medium text-sm">{{ auth()->user()->name }}</p>
                            <p class="text-xs text-gray-400 capitalize">{{ auth()->user()->role }}</p>
                        </div>
                    </div>
                    <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
                        <a href="{{ route(auth()->user()->role . '.index') }}"
                           class="flex items-center gap-3 px-3 py-3 rounded-lg {{ request()->routeIs(auth()->user()->role . '.index') ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                            </svg>
                            <span class="text-sm font-medium">Dashboard</span>
                        </a>
                        <a href="{{ route('profile.edit') }}"
                           class="flex items-center gap-3 px-3 py-3 rounded-lg {{ request()->routeIs('profile.*') ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            <span class="text-sm font-medium">Perfil</span>
                        </a>

                        <a href="{{ route(auth()->user()->role . '.pagos') }}"
                           class="flex items-center gap-3 px-3 py-3 rounded-lg {{ request()->routeIs(auth()->user()->role . '.pagos') ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span class="text-sm font-medium">Pagos</span>
                        </a>

                        <a href="{{ route(auth()->user()->role . '.corte') }}"
                           class="flex items-center gap-3 px-3 py-3 rounded-lg {{ request()->routeIs(auth()->user()->role . '.corte') ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                            </svg>
                            <span class="text-sm font-medium">Corte</span>
                        </a>

                        <a href="{{ route(auth()->user()->role . '.historial') }}"
                           class="flex items-center gap-3 px-3 py-3 rounded-lg {{ request()->routeIs(auth()->user()->role . '.historial') ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span class="text-sm font-medium">Historial de Pagos</span>
                        </a>
                        @endif
                    </nav>
                    <div class="p-3 border-t border-gray-800">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="flex items-center gap-3 px-3 py-3 rounded-lg text-gray-300 hover:bg-gray-800 w-full">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                </svg>
                                <span class="text-sm font-medium">Cerrar sesión</span>
                            </button>
                        </form>
                    </div>
                </div>
            </aside>

            <!-- Overlay mobile -->
            <div x-show="sidebarOpen"
                 x-cloak
                 @click="sidebarOpen = false"
                 class="fixed inset-0 bg-black/50 z-30 md:hidden h-screen"
                 x-transition.opacity></div>

            <main class="flex-1 overflow-y-auto">
                {{ $slot }}
            </main>

        </div>
    </body>
</html>
