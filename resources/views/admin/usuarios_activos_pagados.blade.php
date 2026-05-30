<x-app-layout title="Clientes activos pagados">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            Clientes con estado Activo/Pagado
        </h2>
    </x-slot>

    @php
        $isPagos = request()->is('pagos/*');
        $routePrefix = $isPagos ? 'pagos' : 'admin';
    @endphp

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('status') === 'cliente-eliminado')
                <div x-data x-init="Swal.fire({ icon: 'success', title: '¡Eliminado!', text: 'El cliente ha sido eliminado correctamente.', timer: 3000, showConfirmButton: false })"></div>
            @endif
            <div class="bg-white dark:bg-gray-800 rounded shadow p-4">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <div class="text-sm text-gray-600">Total: {{ $usuarios->total() }} clientes que han pagado</div>
                    <form method="GET" action="" class="flex items-center gap-2">
                        <input type="text" name="buscar" value="{{ $buscar }}" placeholder="Buscar por número..."
                            class="form-input rounded-lg border-gray-300 dark:border-gray-600 shadow-sm text-sm focus:border-indigo-400 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 w-48">
                        <button type="submit" class="btn btn-primary text-sm px-3 py-1.5">Buscar</button>
                        @if($buscar !== '')
                            <a href="{{ request()->url() }}" class="btn btn-secondary text-sm px-3 py-1.5">Limpiar</a>
                        @endif
                    </form>
                    <a href="{{ route($routePrefix . '.index') }}" class="btn btn-primary">Regresar al dashboard</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-600">
                                <th class="py-2">Número</th>
                                <th class="py-2">Nombre</th>
                                <th class="py-2">Estatus</th>
                                <th class="py-2">Estado</th>
                                <th class="py-2">Mes pagado</th>
                                <th class="py-2">Actualizado</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($usuarios as $u)
                            <tr class="border-t border-gray-200">
                                <td class="py-2">{{ $u->numero_servicio }}</td>
                                <td class="py-2">{{ $u->nombre_cliente }}</td>
                                <td class="py-2">
                                    <span class="px-2 py-1 rounded text-xs font-semibold bg-green-100 text-green-800">
                                        {{ optional($u->estatusServicio)->nombre }}
                                    </span>
                                </td>
                                <td class="py-2">
                                    <span class="px-2 py-1 rounded text-xs font-semibold bg-blue-100 text-blue-800">
                                        {{ optional($u->estado)->nombre }}
                                    </span>
                                </td>
                                <td class="py-2">
                                    @if($u->ultimo_periodo)
                                        @php
                                            [$y, $m] = explode('-', $u->ultimo_periodo);
                                            $meses = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
                                        @endphp
                                        <span class="px-2 py-1 rounded text-xs font-semibold bg-indigo-100 text-indigo-800">
                                            {{ $meses[(int)$m] ?? $m }} {{ $y }}
                                        </span>
                                    @else
                                        <span class="text-gray-400 text-xs">—</span>
                                    @endif
                                </td>
                                <td class="py-2">{{ optional($u->updated_at)->format('Y-m-d H:i') }}</td>
                                <td class="py-2">
                                    <div class="flex gap-2">
                                        <a href="{{ route($routePrefix . '.clientes.show', $u->id) }}" class="px-2 py-1 rounded bg-indigo-600 text-white text-xs hover:bg-indigo-700">Ver</a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-8 text-center text-gray-400 italic">
                                    No se encontraron clientes que hayan pagado
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                
                <!-- Paginación -->
                <div class="mt-4">
                    {{ $usuarios->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
