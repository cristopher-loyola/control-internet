<x-app-layout title="Historial del número {{ $numero }}">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            Historial del número de cliente {{ $numero }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="mb-4 flex items-center justify-between">
                        <a href="{{ route('pagos.clientes.index') }}" class="btn btn-primary">
                             Volver a la lista
                        </a>
                        @if ($actual)
                            <span class="text-xs px-2 py-1 rounded bg-emerald-600 text-white">Actualmente asignado a: {{ $actual->nombre_cliente }}</span>
                        @else
                            <span class="text-xs px-2 py-1 rounded bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200">No asignado actualmente</span>
                        @endif
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700/40">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Fecha</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Acción</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nombre</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Uso</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tecnología</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Megas</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Paquete</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Estado</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Estatus</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse ($historial as $h)
                                    <tr>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm">{{ optional($h->captured_at)->format('d/m/Y H:i') ?? $h->created_at->format('d/m/Y H:i') }}</td>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm">
                                            @php
                                                $accionEsp = match($h->accion) {
                                                    'create' => 'CREADO',
                                                    'update' => 'ACTUALIZADO',
                                                    'delete' => 'ELIMINADO',
                                                    default => strtoupper($h->accion),
                                                };
                                            @endphp
                                            <span class="inline-flex items-center justify-center px-2 py-1 rounded text-xs w-24 {{ $h->accion === 'delete' ? 'bg-rose-600 text-white' : ($h->accion === 'update' ? 'bg-amber-500 text-white' : 'bg-emerald-600 text-white') }}">
                                                {{ $accionEsp }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm">{{ $h->nombre_cliente ?? '—' }}</td>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm">{{ $h->uso ?? '—' }}</td>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm">{{ $h->tecnologia ? strtoupper($h->tecnologia) : '—' }}</td>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm">{{ $h->megas ?? '—' }}</td>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm">
                                            @if(!is_null($h->tarifa))
                                                ${{ number_format((float) $h->tarifa, 2) }}
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm">{{ optional($h->estado)->nombre ?? '—' }}</td>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm">{{ optional($h->estatusServicio)->nombre ?? '—' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                                            Aún no hay historial registrado para este número.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
