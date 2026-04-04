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
            <div class="bg-white dark:bg-gray-800 rounded shadow p-4">
                <div class="flex items-center justify-between mb-3">
                    <div class="text-sm text-gray-600">Total: {{ $usuarios->total() }} clientes</div>
                    <a href="{{ route($routePrefix . '.index') }}" class="btn btn-primary">Regresar al dashboard</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-600">
                                <th class="py-2">Número</th>
                                <th class="py-2">Nombre</th>
                                <th class="py-2">IP</th>
                                <th class="py-2">Estatus</th>
                                <th class="py-2">Estado</th>
                                <th class="py-2">Actualizado</th>
                                <th class="py-2 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse ($usuarios as $u)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                    <td class="py-2">{{ $u->numero_servicio }}</td>
                                    <td class="py-2">{{ $u->nombre_cliente }}</td>
                                    <td class="py-2">
                                        @if($u->ip && $u->ip !== '-')
                                            <a href="http://{{ $u->ip }}" target="_blank" class="text-indigo-600 hover:text-indigo-800 hover:underline">
                                                {{ $u->ip }}
                                            </a>
                                        @else
                                            {{ $u->ip }}
                                        @endif
                                    </td>
                                    <td class="py-2">{{ optional($u->estatusServicio)->nombre }}</td>
                                    <td class="py-2">
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
                                    <td class="py-2 text-gray-500">{{ optional($u->updated_at)->format('Y-m-d') }}</td>
                                    <td class="py-2 text-right">
                                        <div class="inline-flex gap-2">
                                            <a href="{{ route($routePrefix . '.clientes.show', $u->id) }}" class="px-2 py-1 rounded bg-indigo-600 text-white text-xs hover:bg-indigo-700">Ver</a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="py-8 text-center text-gray-400 italic">
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

