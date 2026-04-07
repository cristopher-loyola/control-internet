<x-app-layout title="Clientes en baja temporal">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            Clientes en baja temporal
        </h2>
    </x-slot>

    @php
        $isPagos = request()->is('pagos/*');
        $routePrefix = $isPagos ? 'pagos' : 'admin';
    @endphp

    <div class="py-6" x-data="bajaTemporal()">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-md p-5">
                <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-700 pb-3 mb-4">
                    <div>
                        <div class="text-lg font-bold text-gray-800 dark:text-white">Clientes en baja temporal</div>
                        <div class="text-xs text-gray-500 mt-1">Total: {{ $usuarios->total() }} clientes</div>
                    </div>
                    <a href="{{ route($routePrefix . '.index') }}" class="btn btn-primary btn-sm">Regresar al dashboard</a>
                </div>

                <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Número</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Nombre</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">IP</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Estatus</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Hasta</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Estado</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Actualizado</th>
                                <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse ($usuarios as $u)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                    <td class="px-4 py-3 font-semibold text-gray-900 dark:text-white">{{ $u->numero_servicio }}</td>
                                    <td class="px-4 py-3 text-gray-800 dark:text-gray-100">{{ $u->nombre_cliente }}</td>
                                    <td class="px-4 py-3">
                                        @if($u->ip && $u->ip !== '-')
                                            <a href="http://{{ $u->ip }}" target="_blank" class="text-indigo-600 hover:text-indigo-800 hover:underline dark:text-indigo-400 dark:hover:text-indigo-300">
                                                {{ $u->ip }}
                                            </a>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200">
                                            {{ optional($u->estatusServicio)->nombre }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200">
                                            {{ $u->baja_temporal_hasta ?? '-' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <select
                                            class="form-select text-xs rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                            @change="actualizarEstado($event, {{ $u->id }})"
                                        >
                                            @foreach($estados as $estado)
                                                <option value="{{ $estado->id }}" {{ $u->estado_id == $estado->id ? 'selected' : '' }}>
                                                    {{ $estado->nombre === 'Desactivado' ? 'Cortado' : $estado->nombre }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="px-4 py-3 text-gray-500">{{ optional($u->updated_at)->format('Y-m-d') }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <a href="{{ route($routePrefix . '.clientes.show', $u->id) }}" class="px-2 py-1 rounded bg-indigo-600 text-white text-xs hover:bg-indigo-700">Ver</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="py-10 text-center text-gray-400 italic">
                                        No hay clientes en baja temporal
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $usuarios->links() }}
                </div>
            </div>
        </div>
    </div>

    <script>
        function bajaTemporal() {
            return {
                actualizarEstado(e, id) {
                    const estadoId = e.target.value;
                    const url = `{{ route($routePrefix . '.dashboard.baja-temporal.estado', ':id') }}`.replace(':id', id);
                    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                    fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': token,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ estado_id: estadoId })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.ok) {
                            // Opcional: mostrar notificación de éxito
                        } else {
                            alert('Error al actualizar el estado');
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        alert('Error al actualizar el estado');
                    });
                }
            }
        }
    </script>
</x-app-layout>
