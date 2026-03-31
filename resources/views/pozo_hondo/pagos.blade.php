<x-app-sidebar>
    <x-slot name="headerTitle">Pagos - Pozo Hondo</x-slot>

    <div class="min-h-screen bg-gray-100 py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header con buscador -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Buscar cliente</h2>
                <div class="flex gap-4">
                    <input type="number" 
                           placeholder="Ingresa el ID del cliente..."
                           class="flex-1 rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <button class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                        Buscar
                    </button>
                </div>
            </div>

            <!-- Grid de acciones -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Card: Registrar Pago -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="p-3 bg-green-100 rounded-lg">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <h3 class="font-semibold text-gray-800">Registrar Pago</h3>
                    </div>
                    <p class="text-sm text-gray-600 mb-4">Registra un nuevo pago de mensualidad o servicio.</p>
                    <a href="#" class="block w-full text-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                        Ir a recibos
                    </a>
                </div>

                <!-- Card: Historial de Pagos -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="p-3 bg-blue-100 rounded-lg">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                            </svg>
                        </div>
                        <h3 class="font-semibold text-gray-800">Historial de Pagos</h3>
                    </div>
                    <p class="text-sm text-gray-600 mb-4">Consulta el historial de pagos realizados.</p>
                    <button class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Ver historial
                    </button>
                </div>

                <!-- Card: Pagos Pendientes -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="p-3 bg-amber-100 rounded-lg">
                            <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <h3 class="font-semibold text-gray-800">Pagos Pendientes</h3>
                    </div>
                    <p class="text-sm text-gray-600 mb-4">Clientes con pagos pendientes del mes.</p>
                    <button class="w-full px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700">
                        Ver pendientes
                    </button>
                </div>
            </div>
        </div>
    </div>
</x-app-sidebar>
