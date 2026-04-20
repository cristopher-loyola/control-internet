@props([
    'title',
    'zona',
    'stats' => [],
    'chart' => ['labels' => [], 'values' => []],
    'payments' => [],
    'zonaRoute' => null,
    'corteActivo' => null,
])

<div class="min-h-[calc(100vh-4rem)] bg-white">
    <div class="w-full px-4 sm:px-6 lg:px-8 py-6">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <div class="text-sm text-gray-500">Zona</div>
                <div class="text-2xl font-semibold tracking-tight text-gray-900">{{ $title }}</div>
            </div>
        </div>
        
        {{-- Tarjeta de pagos específica de la zona --}}
        <div class="mt-6">
            @if($title === 'Chivato')
                <x-chivato-payments-card />
            @elseif($title === 'Pozo Hondo')
                <x-pozo-hondo-payments-card />
            @endif
        </div>
            <div class="flex flex-col sm:flex-row gap-2">
                @if($zonaRoute)
                    <button id="btn-iniciar-corte"
                       data-url="{{ route($zonaRoute . '.corte.iniciar') }}"
                       class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150 shadow-sm disabled:opacity-50 disabled:cursor-not-allowed">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Iniciar corte
                    </button>
                    <button id="btn-finalizar-corte"
                       data-url="{{ route($zonaRoute . '.corte.finalizar') }}"
                       class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:bg-red-700 active:bg-red-900 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150 shadow-sm disabled:opacity-50 disabled:cursor-not-allowed">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 10a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z"/>
                        </svg>
                        Finalizar corte
                    </button>
                    @if($corteActivo)
                        <a href="{{ route($zonaRoute . '.corte.exportar') }}"
                           class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150 shadow-sm">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Exportar Excel
                        </a>
                    @endif
                @endif
            </div>
        </div>
    </div>
</div>

@if($zonaRoute)
<script>
(function() {
    const btnIniciar = document.getElementById('btn-iniciar-corte');
    const btnFinalizar = document.getElementById('btn-finalizar-corte');
    const zonaRoute = '{{ $zonaRoute }}';

    // Obtener CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    // Función para mostrar mensaje
    function showMessage(message, type = 'success') {
        // Crear notificación con botón de cerrar
        const div = document.createElement('div');
        div.id = 'corte-message';
        div.className = `fixed top-4 right-4 px-4 py-3 rounded shadow-lg z-50 ${type === 'success' ? 'bg-green-600' : type === 'error' ? 'bg-red-600' : 'bg-yellow-600'} text-white max-w-sm`;
        div.innerHTML = `
            <div class="flex items-start gap-2">
                <span class="flex-1">${message}</span>
                <button onclick="this.parentElement.parentElement.remove()" class="text-white hover:text-gray-200 font-bold text-lg leading-none">&times;</button>
            </div>
        `;
        document.body.appendChild(div);
        // El mensaje no se elimina automáticamente, el usuario debe cerrarlo manualmente
    }

    // Función para verificar estado del corte
    async function checkCorteStatus() {
        try {
            const response = await fetch(`/${zonaRoute.replace('_', '-')}/corte/activo`);
            const data = await response.json();

            if (data.activo) {
                btnIniciar.disabled = true;
                btnFinalizar.disabled = false;
                btnIniciar.classList.add('opacity-50', 'cursor-not-allowed');
                btnFinalizar.classList.remove('opacity-50', 'cursor-not-allowed');
            } else {
                btnIniciar.disabled = false;
                btnFinalizar.disabled = true;
                btnIniciar.classList.remove('opacity-50', 'cursor-not-allowed');
                btnFinalizar.classList.add('opacity-50', 'cursor-not-allowed');
            }
        } catch (error) {
            console.error('Error checking corte status:', error);
        }
    }

    // Iniciar corte
    btnIniciar?.addEventListener('click', async function() {
        const url = this.dataset.url;
        this.disabled = true;

        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
            });

            let data;
            try {
                data = await response.json();
            } catch (e) {
                const text = await response.text();
                console.error('Respuesta no es JSON:', text);
                showMessage('Error: Respuesta inválida del servidor. Revisa la consola.', 'error');
                this.disabled = false;
                return;
            }

            if (response.ok && data.ok) {
                showMessage(data.message || 'Corte iniciado correctamente');
                checkCorteStatus();
                // Redirigir a la página de pagos
                setTimeout(() => {
                    window.location.href = `/${zonaRoute.replace('_', '-')}/pagos`;
                }, 1000);
            } else {
                const message = data.message || `Error ${response.status}: No se pudo iniciar el corte`;
                showMessage(message, 'error');
                this.disabled = false;
            }
        } catch (error) {
            console.error('Error de conexión:', error);
            showMessage('Error de conexión: ' + error.message, 'error');
            this.disabled = false;
        }
    });

    // Finalizar corte
    btnFinalizar?.addEventListener('click', async function() {
        const url = this.dataset.url;
        const btn = this;

        // SweetAlert2 confirmation modal
        const result = await Swal.fire({
            title: '¿Finalizar corte de caja?',
            text: 'Esta acción cerrará el corte actual y no podrá realizarse más cobros en este turno.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, finalizar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#6b7280',
            reverseButtons: true,
            focusCancel: true,
            customClass: {
                popup: 'rounded-lg',
                title: 'text-lg font-semibold text-gray-800',
                htmlContainer: 'text-sm text-gray-600',
                confirmButton: 'px-4 py-2 rounded-md font-medium text-sm',
                cancelButton: 'px-4 py-2 rounded-md font-medium text-sm'
            }
        });

        if (!result.isConfirmed) {
            return;
        }

        this.disabled = true;

        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
            });

            const data = await response.json();

            if (data.ok) {
                showMessage(`Corte finalizado. Total recaudado: $${parseFloat(data.corte.total_recaudado).toFixed(2)} (${data.corte.total_pagos} pagos)`);
                checkCorteStatus();
            } else {
                showMessage(data.message || 'Error al finalizar corte', 'error');
                this.disabled = false;
            }
        } catch (error) {
            showMessage('Error de conexión', 'error');
            this.disabled = false;
        }
    });

    // Verificar estado al cargar
    checkCorteStatus();
})();
</script>
@endif

