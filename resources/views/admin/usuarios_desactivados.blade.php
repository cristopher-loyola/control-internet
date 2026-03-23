<x-app-layout title="Clientes desactivados">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            Clientes desactivados
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
                    <div class="text-sm text-gray-600">Total: {{ $usuarios->total() }}</div>
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
                                <td class="py-2">{{ optional($u->estatusServicio)->nombre }}</td>
                                <td class="py-2">{{ optional($u->estado)->nombre }}</td>
                                <td class="py-2">{{ optional($u->updated_at)->format('Y-m-d H:i') }}</td>
                                <td class="py-2">
                                    <form method="POST" action="{{ route($routePrefix . '.clientes.destroy', $u->id) }}" onsubmit="event.preventDefault(); Swal.fire({ title: '¿Estás seguro?', text: '¡No podrás revertir esto!', icon: 'warning', showCancelButton: true, confirmButtonColor: '#3085d6', cancelButtonColor: '#d33', confirmButtonText: 'Sí, ¡elimínalo!', cancelButtonText: 'Cancelar' }).then((result) => { if (result.isConfirmed) { this.submit(); } });">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="redirect_to" value="{{ url()->current() }}">
                                        <button class="px-2 py-1 rounded bg-red-600 text-white text-xs hover:bg-red-700">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="py-3 text-gray-500">Sin registros</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">{{ $usuarios->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>

