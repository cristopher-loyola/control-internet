@php
    $logoUrl = null;
    foreach (['images/control-internet-logo.png', 'images/control-internet-logo.jpg', 'images/control-internet-logo.jpeg'] as $candidate) {
        if (file_exists(public_path($candidate))) {
            $logoUrl = $candidate;
            break;
        }
    }
@endphp

<x-guest-layout :wide="true">
    <div class="min-h-screen flex flex-col lg:grid lg:grid-cols-2">

        {{-- Panel izquierdo (hero) --}}
        <div class="relative flex flex-col items-center justify-center bg-gray-900 dark:bg-black overflow-hidden px-6 py-14 lg:py-0 lg:min-h-screen">
            <div class="absolute inset-0 bg-gradient-to-br from-red-900/40 via-black to-black opacity-90 z-0"></div>
            <div class="absolute -top-20 -left-16 w-72 h-72 bg-red-600 rounded-full mix-blend-multiply blur-3xl opacity-20 animate-blob"></div>
            <div class="absolute -bottom-20 -right-16 w-72 h-72 bg-red-800 rounded-full mix-blend-multiply blur-3xl opacity-20 animate-blob animation-delay-2000"></div>

            <div class="relative z-10 text-center px-4">
                <div class="mb-6 flex justify-center">
                    <img
                        src="{{ asset('images/Clogo.png') }}"
                        alt="Control Internet"
                        class="h-20 md:h-24 w-auto object-contain drop-shadow-2xl"
                    >
                </div>

                <h1 class="text-3xl md:text-4xl font-bold text-white tracking-wide mb-3 drop-shadow-lg">
                    Bienvenido
                </h1>
                <!-- <p class="text-gray-400 text-sm md:text-base max-w-xs mx-auto leading-relaxed">
                    Control Internet -->
                </p>

                {{-- Decoración extra (solo desktop) --}}
                <div class="hidden lg:flex mt-12 gap-6 justify-center">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-red-400">99.9%</div>
                        <div class="text-gray-500 text-xs mt-1">Uptime</div>
                    </div>
                    <div class="w-px bg-gray-700"></div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-red-400">24/7</div>
                        <div class="text-gray-500 text-xs mt-1">Soporte</div>
                    </div>
                    <div class="w-px bg-gray-700"></div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-red-400">SSL</div>
                        <div class="text-gray-500 text-xs mt-1">Seguro</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Panel derecho (formulario) --}}
        <div class="flex items-center justify-center bg-white dark:bg-gray-900 px-6 py-12 sm:px-10 lg:min-h-screen">
            <div class="w-full max-w-sm">

                <div class="mb-2 text-center lg:text-left">
                    <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">
                        Iniciar Sesión
                    </h2>
                    <p class="mt-1.5 text-sm text-gray-500 dark:text-gray-400">
                        Ingresa tus credenciales para acceder al panel
                    </p>
                </div>

                <x-auth-session-status class="mb-4 text-sm" :status="session('status')" />

                <form method="POST" action="{{ route('login') }}" class="space-y-3">
                    @csrf

                    {{-- Email --}}
                    <div>
                        <x-input-label for="email" :value="__('Correo Electrónico')" class="block mb-1 text-[11px] font-medium text-gray-600 dark:text-gray-400 text-center" />
                        <x-text-input
                            id="email"
                            class="block w-56 mx-auto rounded-md border-gray-300 dark:border-gray-700 focus:border-red-500 focus:ring-red-500 dark:bg-gray-800 dark:text-white text-xs py-1.5 px-2.5 transition duration-200"
                            type="email"
                            name="email"
                            :value="old('email')"
                            required autofocus autocomplete="username"
                            placeholder="Ingrese el usuario"
                        />
                        <x-input-error :messages="$errors->get('email')" class="mt-1 text-[10px]" />
                    </div>

                    {{-- Password --}}
                    <div>
                        <x-input-label for="password" :value="__('Contraseña')" class="block mb-1 text-[11px] font-medium text-gray-600 dark:text-gray-400 text-center" />
                        <x-text-input
                            id="password"
                            class="block w-56 mx-auto rounded-md border-gray-300 dark:border-gray-700 focus:border-red-500 focus:ring-red-500 dark:bg-gray-800 dark:text-white text-xs py-1.5 px-2.5 transition duration-200"
                            type="password"
                            name="password"
                            required autocomplete="current-password"
                            placeholder="••••••••"
                        />
                        <x-input-error :messages="$errors->get('password')" class="mt-1 text-[10px]" />
                    </div>

                    {{-- Recordarme --}}
                    <div class="w-56 mx-auto pt-0.5 flex justify-center">
                        <label for="remember_me" class="inline-flex items-center gap-1.5 cursor-pointer">
                            <input
                                id="remember_me"
                                type="checkbox"
                                class="h-3 w-3 rounded border-gray-300 dark:border-gray-700 text-red-600 shadow-sm focus:ring-red-500 dark:bg-gray-900 transition duration-150"
                                name="remember"
                            >
                            <span class="text-[11px] text-gray-500 dark:text-gray-400">{{ __('Recordarme') }}</span>
                        </label>
                    </div>

                    {{-- Submit --}}
                    <div class="w-56 mx-auto flex justify-center mt-1">
                        <x-primary-button class="w-10 flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-xs font-semibold text-white bg-gradient-to-r from-red-600 to-red-800 hover:from-red-700 hover:to-red-900 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transform hover:-translate-y-0.5 transition duration-200 tracking-wide">
                            {{ __('Ingresar') }}
                        </x-primary-button>
                    </div>

                    {{-- Olvidaste tu contraseña --}}
                    @if (Route::has('password.request'))
                        <div class="w-56 mx-auto flex justify-center mt-1">
                            <a class="text-[11px] font-medium text-red-600 hover:text-red-500 dark:text-red-400 dark:hover:text-red-300 transition duration-150 whitespace-nowrap" href="{{ route('password.request') }}">
                                {{ __('¿Olvidaste tu contraseña?') }}
                            </a>
                        </div>
                    @endif
                </form>

                <div class="mt-8">
                    <div class="relative">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-gray-200 dark:border-gray-700"></div>
                        </div>
                        <div class="relative flex justify-center text-xs">
                            <span class="px-3 bg-white dark:bg-gray-900 text-gray-400">
                                Control Internet
                            </span>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</x-guest-layout>
