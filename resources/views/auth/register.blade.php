
<x-guest-layout :wide="true">
<div class="min-h-screen flex flex-col bg-[#FDFDFC] dark:bg-[#0a0a0a]">
    <div class="flex-1 flex flex-col">
    <div class="relative flex flex-col items-center justify-center bg-gray-900 dark:bg-black overflow-hidden px-6 py-14 mt-0">
        <div class="absolute inset-0 bg-gradient-to-br from-red-900/40 via-black to-black opacity-90 z-0"></div>
        <div class="absolute -top-20 -left-16 w-72 h-72 bg-red-600 rounded-full mix-blend-multiply blur-3xl opacity-20"></div>
        <div class="absolute -bottom-20 -right-16 w-72 h-72 bg-red-800 rounded-full mix-blend-multiply blur-3xl opacity-20"></div>

        <div class="relative z-10 text-center px-4">
            <div class="mb-6 flex justify-center">
                <img src="{{ asset('images/Clogo.png') }}" alt="Control Internet"
                     class="h-20 md:h-24 w-auto object-contain drop-shadow-2xl">
            </div>
            <h1 class="text-4xl md:text-5xl font-bold text-white tracking-wide mb-4 drop-shadow-lg">
                Registrar Nuevo Usuario
            </h1>
        </div>
    </div>

    {{-- Formulario pegado al hero, sin espacio --}}
    <div class="flex flex-col items-center bg-[#FDFDFC] dark:bg-[#0a0a0a]">
        <div class="w-full sm:max-w-md px-6 py-6 bg-white dark:bg-gray-800 shadow-md overflow-hidden sm:rounded-b-lg">

            <form method="POST" action="{{ route('register') }}">
                @csrf

                <!-- Name -->
                <div>
                    <x-input-label for="name" :value="__('Name')" />
                    <x-text-input id="name" class="block mt-1 w-full" type="text" name="name"
                                  :value="old('name')" required autofocus autocomplete="name" />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <!-- Email -->
                <div class="mt-4">
                    <x-input-label for="email" :value="__('Email')" />
                    <x-text-input id="email" class="block mt-1 w-full" type="email" name="email"
                                  :value="old('email')" required autocomplete="username" />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <div class="mt-4">
                    <x-input-label for="role" :value="__('Tipo de usuario')" />
                    <select id="role" name="role" class="block mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 shadow-sm focus:border-red-500 focus:ring-red-500 dark:bg-gray-800 dark:text-white">
                        <option value="">Selecciona un tipo de usuario</option>
                        <option value="tecnico" @selected(old('role') === 'tecnico')>Técnico/Instalador</option>
                        <option value="pagos" @selected(old('role') === 'pagos')>Pagos</option>
                        <option value="contrataciones" @selected(old('role') === 'contrataciones')>Contrataciones</option>
                    </select>
                    <x-input-error :messages="$errors->get('role')" class="mt-2" />
                </div>

                <!-- Password -->
                <div class="mt-4">
                    <x-input-label for="password" :value="__('Password')" />
                    <x-text-input id="password" class="block mt-1 w-full" type="password"
                                  name="password" required autocomplete="new-password" />
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <!-- Confirm Password -->
                <div class="mt-4">
                    <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
                    <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password"
                                  name="password_confirmation" required autocomplete="new-password" />
                    <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                </div>

                <div class="flex items-center justify-end mt-4">
                    <a class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800"
                       href="{{ route('login') }}">
                        {{ __('Already registered?') }}
                    </a>
                    <x-primary-button class="ms-4">
                        {{ __('Register') }}
                    </x-primary-button>
                </div>
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
