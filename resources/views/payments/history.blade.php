<x-app-layout title="Historial de Pagos - {{ ucfirst($location) }}">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            Historial de Pagos - {{ ucfirst($location) }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                            Pagos realizados por {{ ucfirst($location) }}
                        </h3>
                        <a href="{{ \App\Helpers\RouteHelper::dashboardRoute() }}" class="btn btn-secondary">
                            Volver al Dashboard
                        </a>
                    </div>

                    <!-- Formulario de búsqueda por folio y número de servicio -->
                    <div class="mb-6 bg-amber-50 border border-amber-200 rounded-lg p-4">
                        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                            <div>
                                <h4 class="font-semibold text-amber-900">Corte actual</h4>
                                <p class="text-sm text-amber-800">Información en tiempo real de los cortes activos de {{ ucfirst($location) }}.</p>
                            </div>
                            @php
                                $corteRoute = match($location) {
                                    'rosalito' => 'pagos.rosalito.cortes',
                                    'chivato' => 'pagos.chivato.cortes',
                                    default => 'pagos.pozo-hondo.cortes',
                                };
                            @endphp
                            <a href="{{ route($corteRoute) }}" class="inline-flex justify-center px-3 py-2 bg-amber-600 hover:bg-amber-700 text-white text-sm font-medium rounded-md no-underline">
                                Ver historial de cortes
                            </a>
                        </div>

                        <div class="mt-4 grid grid-cols-1 gap-3 {{ $cortesActivos->count() > 1 ? 'md:grid-cols-2' : '' }}">
                            @forelse($cortesActivos as $corte)
                                <div class="bg-white border border-amber-100 rounded-md p-4 text-sm text-gray-700">
                                    <div class="flex items-center justify-between gap-3">
                                        <span class="font-semibold text-gray-900">Corte #{{ $corte->id }}</span>
                                        <span class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 text-xs font-medium">Activo</span>
                                    </div>
                                    <dl class="mt-3 grid grid-cols-1 sm:grid-cols-3 gap-3">
                                        <div><dt class="text-xs text-gray-500">Inicio</dt><dd class="font-medium">{{ $corte->fecha_inicio?->format('d/m/Y H:i') ?? '—' }}</dd></div>
                                        <div><dt class="text-xs text-gray-500">Cobros</dt><dd class="font-medium">{{ $corte->total_pagos_actual }}</dd></div>
                                        <div><dt class="text-xs text-gray-500">Recaudado</dt><dd class="font-semibold text-green-600">${{ number_format((float) $corte->total_recaudado_actual, 2) }}</dd></div>
                                    </dl>
                                    <p class="mt-2 text-xs text-gray-500">Cajero: {{ $corte->user?->name ?? 'Sin usuario' }}</p>
                                </div>
                            @empty
                                <p class="text-sm text-gray-600">No hay un corte activo en esta sede.</p>
                            @endforelse
                        </div>
                    </div>

                    <div class="mb-6 bg-gray-50 dark:bg-gray-700 p-6 rounded-lg">
                        <form method="GET" action="{{ \App\Helpers\RouteHelper::historyRoute($location) }}">
                            <div class="flex flex-col md:flex-row gap-4 items-end">
                                <div class="flex-1">
                                    <label for="folio" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Buscar por número de folio
                                    </label>
                                    <input 
                                        type="text" 
                                        name="folio" 
                                        id="folio" 
                                        value="{{ request('folio') }}"
                                        placeholder="Ej: 12345"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-800 dark:text-white"
                                    >
                                </div>
                                <div class="flex-1">
                                    <label for="servicio" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Buscar por número de servicio
                                    </label>
                                    <input 
                                        type="text" 
                                        name="servicio" 
                                        id="servicio" 
                                        value="{{ request('servicio') }}"
                                        placeholder="Ej: 1001"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-800 dark:text-white"
                                    >
                                </div>
                                <div class="flex gap-2 mt-6 md:mt-0">
                                    <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 whitespace-nowrap">
                                        Buscar
                                    </button>
                                    @if(request('folio') || request('servicio'))
                                        <a href="{{ \App\Helpers\RouteHelper::historyRoute($location) }}" class="px-6 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 whitespace-nowrap">
                                            Limpiar
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                        <table class="min-w-full divide-y divide-gray-300 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Folio
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        No. servicio
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Monto
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Cajero
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Fecha
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse($pagos as $pago)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $pago->id }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                            {{ $pago->numero_servicio ?? 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                            ${{ number_format($pago->total, 2) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                            {{ $pago->cajero }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            {{ \Carbon\Carbon::parse($pago->created_at)->format('d/m/Y H:i') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                            No se encontraron pagos realizados por {{ ucfirst($location) }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($pagos->hasPages())
                        <div class="mt-6">
                            {{ $pagos->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
