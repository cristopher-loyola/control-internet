<x-app-layout title="Historial de Pagos - {{ ucfirst($location) }}">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            Historial de Pagos - {{ ucfirst($location) }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                            Pagos realizados por {{ ucfirst($location) }}
                        </h3>
                        <a href="{{ \App\Helpers\RouteHelper::dashboardRoute() }}" class="btn btn-secondary">
                            Volver al Dashboard
                        </a>
                    </div>

                    <!-- Formulario de búsqueda por folio y número de servicio -->
                    <div class="mb-6 bg-gray-50 dark:bg-gray-700 p-6 rounded-lg">
                        <form method="GET" action="{{ \App\Helpers\RouteHelper::historyRoute($location) }}">
                            <div class="flex flex-col md:flex-row gap-4 items-end">
                                <div class="flex-1">
                                    <label for="folio" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Buscar por número de folio
                                    </label>
                                    <input 
                                        type="text" 
                                        name="folio" 
                                        id="folio" 
                                        value="{{ request('folio') }}"
                                        placeholder="Ej: 12345"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-800 dark:text-white"
                                    >
                                </div>
                                <div class="flex-1">
                                    <label for="servicio" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Buscar por número de servicio
                                    </label>
                                    <input 
                                        type="text" 
                                        name="servicio" 
                                        id="servicio" 
                                        value="{{ request('servicio') }}"
                                        placeholder="Ej: 1001"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-800 dark:text-white"
                                    >
                                </div>
                                <div class="flex gap-2 mt-6 md:mt-0">
                                    <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 whitespace-nowrap">
                                        Buscar
                                    </button>
                                    @if(request('folio') || request('servicio'))
                                        <a href="{{ \App\Helpers\RouteHelper::historyRoute($location) }}" class="px-6 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 whitespace-nowrap">
                                            Limpiar
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                        <table class="min-w-full divide-y divide-gray-300 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Folio
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        No. servicio
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Monto
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Cajero
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Fecha
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse($pagos as $pago)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $pago->id }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                            {{ $pago->numero_servicio ?? 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                            ${{ number_format($pago->total, 2) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                            {{ $pago->cajero }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            {{ \Carbon\Carbon::parse($pago->created_at)->format('d/m/Y H:i') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                            No se encontraron pagos realizados por {{ ucfirst($location) }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($pagos->hasPages())
                        <div class="mt-6">
                            {{ $pagos->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
