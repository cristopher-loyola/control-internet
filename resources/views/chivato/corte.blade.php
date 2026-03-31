<x-app-sidebar>
    <x-slot name="headerTitle">Corte - Chivato</x-slot>

    <div class="min-h-screen bg-gray-100 py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-800 mb-2">Corte de Caja - Pagos Exitosos</h2>
                        <p class="text-sm text-gray-600">Lista de todos los pagos cobrados exitosamente en el perfil Chivato</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm text-gray-500">Total de registros</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $pagos->count() }}</p>
                    </div>
                </div>
            </div>

            <!-- Tabla de Pagos Exitosos -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-500">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-100">
                            <tr>
                                <th class="px-4 py-3">Folio</th>
                                <th class="px-4 py-3">No. Servicio</th>
                                <th class="px-4 py-3">Cliente</th>
                                <th class="px-4 py-3">Periodo</th>
                                <th class="px-4 py-3">Total</th>
                                <th class="px-4 py-3">Método</th>
                                <th class="px-4 py-3">Quién cobró</th>
                                <th class="px-4 py-3">Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($pagos as $pago)
                                <tr class="bg-white border-b hover:bg-gray-50">
                                    <td class="px-4 py-3 font-medium text-gray-900">
                                        {{ str_pad($pago['reference_number'], 8, '0', STR_PAD_LEFT) }}
                                    </td>
                                    <td class="px-4 py-3">{{ $pago['numero_servicio'] }}</td>
                                    <td class="px-4 py-3">{{ $pago['nombre'] }}</td>
                                    <td class="px-4 py-3">{{ $pago['periodo'] }}</td>
                                    <td class="px-4 py-3 font-medium text-green-600">
                                        ${{ number_format($pago['total'], 2) }}
                                    </td>
                                    <td class="px-4 py-3">{{ $pago['metodo'] }}</td>
                                    <td class="px-4 py-3">{{ $pago['cobro'] }}</td>
                                    <td class="px-4 py-3">{{ $pago['fecha_formateada'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                                        No hay pagos registrados en este momento.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Resumen del corte -->
            @if($pagos->count() > 0)
                <div class="mt-6">
                    <div class="bg-white rounded-lg shadow-sm p-6 max-w-md">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600">Total en caja</p>
                                <p class="text-2xl font-bold text-green-600">
                                    ${{ number_format($pagos->sum('total'), 2) }}
                                </p>
                            </div>
                            <div class="p-3 bg-green-100 rounded-lg">
                                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-sidebar>
