<x-app-layout title="Pagos por adelantado">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            Pagos por adelantado
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 rounded shadow p-4">
                <div class="flex items-center justify-between mb-4">
                    <div class="text-sm text-gray-600 dark:text-gray-400">Total: {{ $clients->total() }}</div>
                    <a href="{{ route('admin.index') }}" class="btn btn-primary btn-sm">Regresar al dashboard</a>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500 border-b border-gray-100 dark:border-gray-700">
                                <th class="py-2 font-medium">Número</th>
                                <th class="py-2 font-medium">Nombre</th>
                                <th class="py-2 font-medium text-center">Meses</th>
                                <th class="py-2 font-medium text-center">Desde</th>
                                <th class="py-2 font-medium text-center">Hasta</th>
                                <th class="py-2 font-medium text-right">Total Pagado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-gray-700/50">
                            @forelse($clients as $p)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                    <td class="py-3 font-bold text-gray-800 dark:text-white">{{ $p->numero }}</td>
                                    <td class="py-3 text-gray-700 dark:text-gray-300">{{ $p->nombre }}</td>
                                    <td class="py-3 text-center">
                                        <span class="px-2 py-0.5 rounded bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 text-xs font-bold">
                                            {{ $p->meses }} meses
                                        </span>
                                    </td>
                                    <td class="py-3 text-center">
                                        <span class="px-2 py-0.5 rounded-full bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400 text-[11px] font-bold">
                                            {{ $p->desde }}
                                        </span>
                                    </td>
                                    <td class="py-3 text-center">
                                        <span class="px-2 py-0.5 rounded-full bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400 text-[11px] font-bold">
                                            {{ $p->hasta }}
                                        </span>
                                    </td>
                                    <td class="py-3 text-right font-semibold text-gray-800 dark:text-white">
                                        ${{ number_format($p->monto, 2) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-10 text-center text-gray-400 italic">No hay pagos adelantados registrados</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-4">
                    {{ $clients->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
