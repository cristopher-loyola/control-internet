<x-app-layout title="REACTIVACIONES">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight uppercase">
            {{ __('REACTIVACIONES PENDIENTES - ') }} {{ now()->locale('es')->monthName }} {{ now()->year }}
        </h2>
    </x-slot>

    <div class="py-6" x-data="reactivacionesManager()">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                
                <!-- Buscador -->
                <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
                    <form action="{{ route('pagos.reactivaciones.index') }}" method="GET" class="w-full md:w-1/2 flex gap-2">
                        <input type="text" name="q" value="{{ request('q') }}" placeholder="Buscar por ID, nombre o zona..."
                            class="form-input w-full rounded-lg border-gray-300 dark:border-gray-600 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white">
                        <button type="submit" class="btn btn-primary">Buscar</button>
                    </form>
                </div>

                <!-- Tabla de Reactivaciones -->
                <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">ID / Cliente</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Zona</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">IP</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">¿Quién cortó?</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Estado Actual</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Acción</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                            @foreach ($usuarios as $u)
                            <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="font-bold dark:text-white">{{ $u->numero_servicio }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $u->nombre_cliente }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm dark:text-gray-300">{{ $u->zona ?? '-' }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm">
                                    @if($u->ip)
                                        <a href="http://{{ $u->ip }}" target="_blank" class="text-indigo-600 hover:text-indigo-800 hover:underline dark:text-indigo-400 dark:hover:text-indigo-300">
                                            {{ $u->ip }}
                                        </a>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm dark:text-gray-300">
                                    {{ $u->cortador->nombre ?? 'Sin asignar' }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">
                                        {{ $u->estado_corte }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <button @click="reactivarUsuario({{ $u->id }}, '{{ $u->nombre_cliente }}')"
                                        class="inline-flex items-center px-3 py-1 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 active:bg-green-900 focus:outline-none focus:border-green-900 focus:ring ring-green-300 disabled:opacity-25 transition ease-in-out duration-150">
                                        Reactivar
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                            @if($usuarios->isEmpty())
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                    No hay reactivaciones pendientes por el momento.
                                </td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <div class="mt-6">
                    {{ $usuarios->links() }}
                </div>
            </div>
        </div>
    </div>

    <script>
        function reactivacionesManager() {
            return {
                async reactivarUsuario(id, nombre) {
                    const result = await Swal.fire({
                        title: '¿Confirmar reactivación?',
                        text: `El usuario ${nombre} será marcado como Reactivado y activado en el sistema.`,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#10b981',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Sí, reactivar',
                        cancelButtonText: 'Cancelar',
                        background: document.documentElement.classList.contains('dark') ? '#1f2937' : '#fff',
                        color: document.documentElement.classList.contains('dark') ? '#fff' : '#000'
                    });

                    if (!result.isConfirmed) return;

                    try {
                        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                        const r = await fetch(`{{ url('/pagos/cortes') }}/${id}/update`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': token
                            },
                            body: JSON.stringify({ 
                                estado_corte: 'Reactivado'
                            })
                        });
                        
                        if(r.ok) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Usuario Reactivado',
                                text: 'El estatus ha sido actualizado correctamente.',
                                timer: 2000,
                                showConfirmButton: false
                            });
                            setTimeout(() => window.location.reload(), 2000);
                        }
                    } catch(e) {
                        console.error(e);
                        Swal.fire('Error', 'No se pudo procesar la reactivación', 'error');
                    }
                }
            }
        }
    </script>
</x-app-layout>