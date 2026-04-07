<x-app-layout title="Cancelaciones de suscripción">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            Cancelaciones de suscripción
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
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-md p-5">
                <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-700 pb-3 mb-4">
                    <div>
                        <div class="text-lg font-bold text-gray-800 dark:text-white">Cancelaciones de suscripción</div>
                        <div class="text-xs text-gray-500 mt-1">Total: {{ $usuarios->total() }}</div>
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
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">MAC</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Estatus</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Estado</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Actualizado</th>
                                <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($usuarios as $u)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                    <td class="px-4 py-3 font-semibold text-gray-900 dark:text-white">{{ $u->numero_servicio }}</td>
                                    <td class="px-4 py-3 text-gray-800 dark:text-gray-100">{{ $u->nombre_cliente }}</td>
                                    <td class="px-4 py-3">
                                        @if($u->ip)
                                            <a href="http://{{ $u->ip }}" target="_blank" class="text-indigo-600 hover:text-indigo-800 hover:underline dark:text-indigo-400 dark:hover:text-indigo-300">
                                                {{ $u->ip }}
                                            </a>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-gray-700 dark:text-gray-200">{{ $u->mac ?? '-' }}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200">
                                            {{ optional($u->estatusServicio)->nombre }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-gray-700 dark:text-gray-200">{{ optional($u->estado)->nombre }}</td>
                                    <td class="px-4 py-3 text-gray-500">{{ optional($u->updated_at)->format('Y-m-d H:i') }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <form method="POST" action="{{ route($routePrefix . '.clientes.destroy', $u->id) }}" onsubmit="event.preventDefault(); Swal.fire({ title: '¿Estás seguro?', text: '¡No podrás revertir esto!', icon: 'warning', showCancelButton: true, confirmButtonColor: '#3085d6', cancelButtonColor: '#d33', confirmButtonText: 'Sí, ¡elimínalo!', cancelButtonText: 'Cancelar' }).then((result) => { if (result.isConfirmed) { this.submit(); } });">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="redirect_to" value="{{ url()->current() }}">
                                            <button class="px-2 py-1 rounded bg-red-600 text-white text-xs hover:bg-red-700">Eliminar</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="py-10 text-center text-gray-400 italic">Sin registros</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">{{ $usuarios->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
