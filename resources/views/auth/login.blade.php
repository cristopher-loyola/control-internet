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
    <div class="min-h-screen flex flex-col bg-[#FDFDFC] dark:bg-[#0a0a0a]">
        <div class="flex-1 flex flex-col">

        <div class="relative flex flex-col items-center justify-center bg-gray-900 dark:bg-black overflow-hidden px-6 py-14 mt-0">
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

                <h1 class="text-4xl md:text-5xl font-bold text-white tracking-wide mb-4 drop-shadow-lg">
                    Bienvenido
                </h1>

               
            </div>
        </div>

        <div class="flex flex-col items-center bg-[#FDFDFC] dark:bg-[#0a0a0a]">
        <div class="w-full sm:max-w-md px-6 py-6 bg-white dark:bg-gray-800 shadow-md overflow-hidden sm:rounded-b-lg text-[15px] leading-[22px] mt-8">

                <div class="mb-3 text-center lg:text-left">
                    <h2 class="text-3xl font-semibold text-gray-900 dark:text-white">
                        Iniciar Sesión
                    </h2>
                    <p class="mt-2 text-base text-gray-500 dark:text-gray-400">
                        Ingresa tus credenciales para acceder       
                    </p>
                </div>

                <x-auth-session-status class="mb-4 text-sm" :status="session('status')" />

                <form method="POST" action="{{ route('login') }}" class="space-y-3">
                    @csrf

                    {{-- Email --}}
                    <div>
                        <x-input-label for="email" :value="__('Correo Electrónico')" class="block mb-1 text-xs font-medium text-gray-600 dark:text-gray-400 text-center" />
                        <x-text-input
                            id="email"
                            class="block w-64 mx-auto rounded-md border-gray-300 dark:border-gray-700 focus:border-red-500 focus:ring-red-500 dark:bg-gray-800 dark:text-white text-sm py-2 px-3 transition duration-200"
                            type="email"
                            name="email"
                            :value="old('email')"
                            required autofocus autocomplete="username"
                            placeholder="Ingrese el usuario"
                        />
                        <x-input-error :messages="$errors->get('email')" class="mt-1 text-[11px]" />
                    </div>

                    {{-- Password --}}
                    <div>
                        <x-input-label for="password" :value="__('Contraseña')" class="block mb-1 text-xs font-medium text-gray-600 dark:text-gray-400 text-center" />
                        <x-text-input
                            id="password"
                            class="block w-64 mx-auto rounded-md border-gray-300 dark:border-gray-700 focus:border-red-500 focus:ring-red-500 dark:bg-gray-800 dark:text-white text-sm py-2 px-3 transition duration-200"
                            type="password"
                            name="password"
                            required autocomplete="current-password"
                            placeholder="••••••••"
                        />
                        <x-input-error :messages="$errors->get('password')" class="mt-1 text-[11px]" />
                    </div>

                    {{-- Recordarme --}}
                    <div class="w-64 mx-auto pt-1 flex justify-center">
                        <label for="remember_me" class="inline-flex items-center gap-1.5 cursor-pointer">
                            <input
                                id="remember_me"
                                type="checkbox"
                                class="h-4 w-4 rounded border-gray-300 dark:border-gray-700 text-red-600 shadow-sm focus:ring-red-500 dark:bg-gray-900 transition duration-150"
                                name="remember"
                            >
                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ __('Recordarme') }}</span>
                        </label>
                    </div>

                    {{-- Submit --}}
                    <div class="w-full flex justify-center mt-3">
                        <x-primary-button class="inline-flex justify-center items-center px-6 py-3 border border-transparent rounded-md shadow-sm text-sm font-semibold text-white bg-gradient-to-r from-red-600 to-red-800 hover:from-red-700 hover:to-red-900 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transform hover:-translate-y-0.5 transition duration-200 tracking-wide" style="width: 230px;">
                            {{ __('Ingresar') }}
                        </x-primary-button>
                    </div>

                    <!-- {{-- Olvidaste tu contraseña --}}
                    @if (Route::has('password.request'))
                        <div class="w-full flex justify-center mt-2">
                            <a class="text-xs font-medium text-red-600 hover:text-red-500 dark:text-red-400 dark:hover:text-red-300 transition duration-150 whitespace-nowrap" href="{{ route('password.request') }}">
                                {{ __('¿Olvidaste tu contraseña?') }}
                            </a>
                        </div>
                    @endif -->
                </form>

            </div>
        </div>
        </div>

        <div class="py-6">
            <div class="relative">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-gray-200 dark:border-gray-700"></div>
                </div>
                <div class="relative flex justify-center text-sm">
                    <span class="px-3 bg-white dark:bg-gray-900 text-gray-400">
                        Control Internet
                    </span>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>
