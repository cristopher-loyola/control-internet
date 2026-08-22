<x-app-layout title="Cortes de caja - {{ $titulo }}">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">Cortes de caja - {{ $titulo }}</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white shadow-sm rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900">Cortes actuales de {{ $titulo }}</h3>
                <p class="mt-1 text-sm text-gray-600">Consulta de solo lectura para supervisar los cortes de esta sede.</p>
                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full text-sm text-left text-gray-600">
                        <thead class="bg-amber-50 text-xs uppercase text-amber-800"><tr><th class="px-4 py-3">Corte</th><th class="px-4 py-3">Cajero</th><th class="px-4 py-3">Inicio</th><th class="px-4 py-3">Cobros</th><th class="px-4 py-3">Recaudado</th></tr></thead>
                        <tbody>
                            @forelse($cortesActivos as $corte)
                                <tr class="border-b"><td class="px-4 py-3 font-medium">#{{ $corte->id }}</td><td class="px-4 py-3">{{ $corte->user?->name ?? 'Sin usuario' }}</td><td class="px-4 py-3">{{ $corte->fecha_inicio?->format('d/m/Y H:i') }}</td><td class="px-4 py-3">{{ $corte->total_pagos_actual }}</td><td class="px-4 py-3 font-semibold text-green-600">${{ number_format((float) $corte->total_recaudado_actual, 2) }}</td></tr>
                            @empty
                                <tr><td colspan="5" class="px-4 py-6 text-center text-gray-500">No hay cortes activos.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                <div class="p-6 border-b"><h3 class="text-lg font-semibold text-gray-900">Historial de cortes cerrados</h3></div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm text-left text-gray-600">
                        <thead class="bg-gray-50 text-xs uppercase text-gray-700"><tr><th class="px-4 py-3">Corte</th><th class="px-4 py-3">Cajero</th><th class="px-4 py-3">Inicio</th><th class="px-4 py-3">Fin</th><th class="px-4 py-3">Cobros</th><th class="px-4 py-3">Recaudado</th></tr></thead>
                        <tbody>
                            @forelse($cortesCerrados as $corte)
                                <tr class="border-b"><td class="px-4 py-3 font-medium">#{{ $corte->id }}</td><td class="px-4 py-3">{{ $corte->user?->name ?? 'Sin usuario' }}</td><td class="px-4 py-3">{{ $corte->fecha_inicio?->format('d/m/Y H:i') }}</td><td class="px-4 py-3">{{ $corte->fecha_fin?->format('d/m/Y H:i') }}</td><td class="px-4 py-3">{{ $corte->total_pagos }}</td><td class="px-4 py-3 font-semibold text-green-600">${{ number_format((float) $corte->total_recaudado, 2) }}</td></tr>
                            @empty
                                <tr><td colspan="6" class="px-4 py-6 text-center text-gray-500">No hay cortes cerrados.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($cortesCerrados->hasPages())<div class="p-4">{{ $cortesCerrados->links() }}</div>@endif
            </div>
        </div>
    </div>
</x-app-layout>
