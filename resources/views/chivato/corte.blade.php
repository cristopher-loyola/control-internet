<x-app-sidebar>
    <x-slot name="headerTitle">Corte - Chivato</x-slot>

    <div class="min-h-screen bg-gray-100 py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-2">Corte de Caja</h2>
                <p class="text-sm text-gray-600">Genera el corte de caja del día</p>
            </div>

            <!-- Grid de opciones -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Card: Corte del Día -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="p-3 bg-indigo-100 rounded-lg">
                            <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <h3 class="font-semibold text-gray-800">Corte del Día</h3>
                    </div>
                    <p class="text-sm text-gray-600 mb-4">Genera el corte de caja con los pagos del día actual.</p>
                    <button class="w-full px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                        Generar corte
                    </button>
                </div>

                <!-- Card: Historial de Cortes -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="p-3 bg-blue-100 rounded-lg">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <h3 class="font-semibold text-gray-800">Historial</h3>
                    </div>
                    <p class="text-sm text-gray-600 mb-4">Consulta cortes de caja anteriores por fecha.</p>
                    <button class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Ver historial
                    </button>
                </div>

                <!-- Card: Reporte por Período -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="p-3 bg-green-100 rounded-lg">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                        </div>
                        <h3 class="font-semibold text-gray-800">Reporte por Período</h3>
                    </div>
                    <p class="text-sm text-gray-600 mb-4">Genera reportes de ingresos por rango de fechas.</p>
                    <button class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                        Ver reporte
                    </button>
                </div>
            </div>

            <!-- Resumen del día -->
            <div class="mt-6 bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Resumen del día - Chivato</h3>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="p-4 bg-indigo-50 rounded-lg">
                        <p class="text-sm text-gray-600">Total en caja</p>
                        <p class="text-2xl font-bold text-gray-900">$0.00</p>
                    </div>
                    <div class="p-4 bg-green-50 rounded-lg">
                        <p class="text-sm text-gray-600">Pagos recibidos</p>
                        <p class="text-2xl font-bold text-gray-900">0</p>
                    </div>
                    <div class="p-4 bg-blue-50 rounded-lg">
                        <p class="text-sm text-gray-600">Efectivo</p>
                        <p class="text-2xl font-bold text-gray-900">$0.00</p>
                    </div>
                    <div class="p-4 bg-amber-50 rounded-lg">
                        <p class="text-sm text-gray-600">Transferencias</p>
                        <p class="text-2xl font-bold text-gray-900">$0.00</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-sidebar>
