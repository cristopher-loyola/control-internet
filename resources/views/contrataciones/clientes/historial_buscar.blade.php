<x-app-layout title="Buscar historial">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            Resultados de búsqueda de historial
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="mb-4 flex items-center justify-between">
                        <a href="{{ route('contrataciones.clientes.index') }}" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">
                            ← Volver
                        </a>
                        <form method="GET" action="{{ route('contrataciones.clientes.historial.buscar') }}" class="flex items-center gap-2">
                            <input
                                type="text"
                                name="q"
                                value="{{ $q }}"
                                class="form-input w-64"
                                placeholder="Número o nombre de cliente"
                                aria-label="Buscar historial"
                            />
                            <button type="submit" class="btn btn-primary">Buscar</button>
                        </form>
                    </div>

                    @php
                        $items = isset($resultados) ? $resultados : (isset($usuarios) ? $usuarios->map(fn($u) => (object)[
                            'numero_servicio' => $u->numero_servicio,
                            'nombre_cliente' => $u->nombre_cliente,
                            'telefono' => $u->telefono,
                            'estado' => 'Activo',
                        ]) : collect());
                    @endphp
                    @if ($items->isEmpty())
                        <p class="text-sm text-gray-600 dark:text-gray-400">No se encontraron clientes que coincidan con "{{ $q }}".</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700/40">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Número de Cliente</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nombre</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Teléfono</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Estado</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach ($items as $u)
                                        <tr>
                                            <td class="px-4 py-2 whitespace-nowrap text-sm">{{ $u->numero_servicio ?? '—' }}</td>
                                            <td class="px-4 py-2 whitespace-nowrap text-sm">{{ $u->nombre_cliente }}</td>
                                            <td class="px-4 py-2 whitespace-nowrap text-sm">{{ $u->telefono ?? '—' }}</td>
                                            <td class="px-4 py-2 whitespace-nowrap text-sm">
                                                <span class="inline-flex items-center px-2 py-1 rounded text-xs {{ ($u->estado ?? 'Activo') === 'Eliminado' ? 'bg-rose-600 text-white' : 'bg-emerald-600 text-white' }}">
                                                    {{ $u->estado ?? 'Activo' }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-2 whitespace-nowrap text-sm">
                                                @if(!is_null($u->numero_servicio))
                                                    <a href="{{ route('contrataciones.clientes.historial', $u->numero_servicio) }}" class="btn btn-info btn-sm">Ver historial</a>
                                                @else
                                                    —
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
