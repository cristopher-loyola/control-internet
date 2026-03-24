<x-app-layout title="Números de Cliente Disponibles">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            Números de Cliente Disponibles
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-lg font-medium">Números disponibles (1000 en adelante)</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                Total: <span class="font-semibold">{{ $totalDisponibles }}</span> números disponibles
                            </p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Rango analizado: 1000 - {{ $ultimoNumero }}
                            </p>
                        </div>
                        <a href="{{ route('admin.clientes.index') }}" class="btn btn-primary">Regresar</a>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700/40">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Número de Cliente
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Estado
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Acciones
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse($numeros as $numero)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {{ $numero->numero_servicio }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                                Disponible
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                            <button 
                                                type="button" 
                                                class="btn btn-primary btn-sm"
                                                onclick="copiarNumero({{ $numero->numero_servicio }})"
                                            >
                                                Copiar
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                                            No hay números disponibles en el rango especificado.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($numeros->hasPages())
                        <div class="mt-6">
                            {{ $numeros->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script>
        function copiarNumero(numero) {
            navigator.clipboard.writeText(numero).then(function() {
                Swal.fire({
                    icon: 'success',
                    title: '¡Copiado!',
                    text: 'Número ' + numero + ' copiado al portapapeles',
                    timer: 2000,
                    showConfirmButton: false
                });
            }).catch(function(err) {
                console.error('Error al copiar: ', err);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No se pudo copiar el número'
                });
            });
        }
    </script>
</x-app-layout>
