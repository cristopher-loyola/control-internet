<x-app-sidebar>
    <x-slot name="headerTitle">Historial de Cortes - Chivato</x-slot>

    <div class="min-h-screen bg-gray-100 py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-800 mb-2">Historial de Cortes de Caja</h2>
                        <p class="text-sm text-gray-600">Lista de todos los cortes de caja cerrados</p>
                    </div>
                    <div class="p-3 bg-indigo-100 rounded-lg">
                        <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Tabla de Cortes -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-500">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-100">
                            <tr>
                                <th class="px-4 py-3"># Corte</th>
                                <th class="px-4 py-3">Inicio</th>
                                <th class="px-4 py-3">Fin</th>
                                <th class="px-4 py-3">Cobros</th>
                                <th class="px-4 py-3">Total Recaudado</th>
                                <th class="px-4 py-3">Estado</th>
                                <th class="px-4 py-3">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($cortes as $index => $corte)
                                <tr class="bg-white border-b hover:bg-gray-50">
                                    <td class="px-4 py-3 font-medium text-gray-900">
                                        #{{ $corte->id }}
                                    <td class="px-4 py-3">
                                        <div class="text-gray-900 font-medium">
                                            {{ $corte->fecha_inicio->format('d/m/Y') }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            {{ $corte->fecha_inicio->format('H:i') }} hrs
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($corte->fecha_fin)
                                            <div class="text-gray-900 font-medium">
                                                {{ $corte->fecha_fin->format('d/m/Y') }}
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                {{ $corte->fecha_fin->format('H:i') }} hrs
                                            </div>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            {{ $corte->total_pagos }} cobro{{ $corte->total_pagos !== 1 ? 's' : '' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 font-medium text-green-600">
                                        ${{ number_format($corte->total_recaudado, 2) }}
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($corte->estado === 'cerrado')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                Cerrado
                                            </span>
                                        @elseif($corte->estado === 'activo')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                Activo
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                {{ $corte->estado }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($index === 0 && !$corteActivo && $corte->estado === 'cerrado')
                                            <button onclick="reanudarCorte({{ $corte->id }})" 
                                                    class="inline-flex items-center px-3 py-1.5 bg-yellow-500 hover:bg-yellow-600 text-white text-xs font-medium rounded-lg transition-colors duration-200">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                                </svg>
                                                Reanudar
                                            </button>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                                        <div class="flex flex-col items-center">
                                            <svg class="w-12 h-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                            </svg>
                                            <p>No hay cortes registrados en el historial.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Script para reanudar corte -->
            <script>
                function reanudarCorte(corteId) {
                    if (!confirm('¿Estás seguro de que deseas reanudar este corte? Podrás seguir agregando pagos a él.')) {
                        return;
                    }

                    fetch('/chivato/corte/reanudar', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({})
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.ok) {
                            alert('Corte reanudado correctamente');
                            window.location.reload();
                        } else {
                            alert(data.message || 'Error al reanudar el corte');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error al reanudar el corte');
                    });
                }
            </script>

            <!-- Resumen -->
            @if($cortes->count() > 0)
                <div class="mt-6">
                    <div class="bg-white rounded-lg shadow-sm p-6 max-w-md">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600">Resumen del Historial</p>
                                <p class="text-xs text-gray-500 mt-1">
                                    {{ $cortes->count() }} corte{{ $cortes->count() !== 1 ? 's' : '' }} registrado{{ $cortes->count() !== 1 ? 's' : '' }}
                                </p>
                                <div class="mt-3 pt-3 border-t border-gray-200">
                                    <p class="text-sm text-gray-600">Total de Cobros</p>
                                    <p class="text-xl font-bold text-blue-600">
                                        {{ $cortes->sum('total_pagos') }}
                                    </p>
                                </div>
                                <div class="mt-3 pt-3 border-t border-gray-200">
                                    <p class="text-sm text-gray-600">Total Recaudado</p>
                                    <p class="text-xl font-bold text-green-600">
                                        ${{ number_format($cortes->sum('total_recaudado'), 2) }}
                                    </p>
                                </div>
                            </div>
                            <div class="p-3 bg-indigo-100 rounded-lg">
                                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-sidebar>
