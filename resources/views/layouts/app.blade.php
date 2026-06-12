<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Control Internet</title>
        <link rel="icon" type="image/png" href="{{ asset('images/clogo2.png') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>[x-cloak]{display:none!important}</style>
        <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    </head>
    <body class="font-sans antialiased">
        @if(app()->environment('local'))
            <!-- SCRIPT DE DESBLOQUEO SOLO EN LOCAL -->
            <script>
                console.log('🚀 Modo LOCAL: Script de desbloqueo activado');
                
                // Función para quitar todos los listeners
                function unlockAll() {
                    // Quitar listeners de document
                    document.removeEventListener('contextmenu', null, true);
                    document.removeEventListener('keydown', null, true);
                    
                    // Quitar listeners de window
                    window.removeEventListener('contextmenu', null, true);
                    window.removeEventListener('keydown', null, true);
                    
                    // Quitar listeners de body
                    document.body.removeEventListener('contextmenu', null, true);
                    document.body.removeEventListener('keydown', null, true);
                    
                    // Quitar oncontextmenu de body y html
                    document.body.oncontextmenu = null;
                    document.documentElement.oncontextmenu = null;
                    
                    // Quitar oncontextmenu de todos los elementos
                    const allElements = document.querySelectorAll('*');
                    allElements.forEach(function(el) {
                        el.oncontextmenu = null;
                    });
                }
                
                // Ejecutar inmediatamente
                unlockAll();
                
                // Ejecutar cada 100ms para asegurar que se mantiene desbloqueado
                setInterval(unlockAll, 100);
                
                // Listener con captura para interceptar y permitir
                document.addEventListener('contextmenu', function(e) {
                    e.stopImmediatePropagation();
                    console.log('✅ Clic derecho permitido!');
                }, true);
                
                document.addEventListener('keydown', function(e) {
                    e.stopImmediatePropagation();
                }, true);
            </script>
        @endif
        <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                @php
                    $role = auth()->user()->role ?? null;
                    $roleHeaderColors = config('role_colors.header');
                    $defaultHeaderColor = config('role_colors.default_header');
                @endphp
                <header
                    x-data="navbarRole({
                        role: @js($role),
                        roleColors: @js($roleHeaderColors),
                        defaultColor: @js($defaultHeaderColor),
                    })"
                    x-init="init()"
                    :class="navClass"
                    class="shadow {{ $roleHeaderColors[$role] ?? $defaultHeaderColor }}"
                >
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
        
        <script>
            console.log('🌍 Entorno actual:', '{{ app()->environment() }}');
        </script>
        
        @if(app()->environment('production'))
        <script>
            console.log('🔒 Modo PRODUCCIÓN: Protección activada');
            document.addEventListener('contextmenu', function(e) {
                e.preventDefault();
            });
            
            document.addEventListener('keydown', function(e) {
                if (e.key === 'F12') {
                    e.preventDefault();
                }
                if (e.ctrlKey && e.shiftKey && e.key === 'I') {
                    e.preventDefault();
                }
                if (e.ctrlKey && e.shiftKey && e.key === 'J') {
                    e.preventDefault();
                }
                if (e.ctrlKey && e.key === 'u') {
                    e.preventDefault();
                }
            });
        </script>
        @else
        <script>
            console.log('✅ Modo LOCAL: Protección DESACTIVADA');
        </script>
        @endif
    </body>
</html>
