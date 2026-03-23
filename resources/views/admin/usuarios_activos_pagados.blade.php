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
                <div class="flex items-center justify-between mb-3">
                    <div class="text-sm text-gray-600">Total: {{ $usuarios->total() }} clientes con estado Activo/Pagado</div>
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
                                <th class="py-2">Actualizado</th>
                                <th class="py-2">Acciones</th>
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
                                <td class="py-2">{{ optional($u->updated_at)->format('Y-m-d H:i') }}</td>
                                <td class="py-2">
                                    <div class="flex gap-2">
                                        <a href="{{ route($routePrefix . '.clientes.show', $u->id) }}" class="px-2 py-1 rounded bg-indigo-600 text-white text-xs hover:bg-indigo-700">Ver</a>
                                        <form method="POST" action="{{ route($routePrefix . '.clientes.destroy', $u->id) }}" onsubmit="event.preventDefault(); Swal.fire({ title: '¿Estás seguro?', text: '¡No podrás revertir esto!', icon: 'warning', showCancelButton: true, confirmButtonColor: '#3085d6', cancelButtonColor: '#d33', confirmButtonText: 'Sí, ¡elimínalo!', cancelButtonText: 'Cancelar' }).then((result) => { if (result.isConfirmed) { this.submit(); } });">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="redirect_to" value="{{ url()->current() }}">
                                            <button class="px-2 py-1 rounded bg-red-600 text-white text-xs hover:bg-red-700">Eliminar</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-8 text-center text-gray-400 italic">
                                    No se encontraron clientes con estado Activo/Pagado
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
