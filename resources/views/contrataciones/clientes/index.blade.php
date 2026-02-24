<x-app-layout title="Clientes">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            {{ __('Clientes') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-screen-2xl mx-auto sm:px-6 lg:px-7">
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg">
                <div class="p-4 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700/40">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nuevo</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Número de Cliente</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nombre</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Dirección</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Número Telefónico</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Uso</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tecnología</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Dispositivo</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Megas</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Paquete</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Siguiente Cobro</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Estado</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Estatus</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse ($clientes as $c)
                                <tr>
                                    <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100 text-center">
                                        @if(!is_null($c->fecha_contratacion))
                                            <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-emerald-600 text-white text-xs font-semibold">
                                                N
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">{{ $c->numero_servicio }}</td>
                                    <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">{{ $c->nombre_cliente }}</td>
                                    <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">{{ $c->domicilio ?? '—' }}</td>
                                    <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">{{ $c->telefono ?? '—' }}</td>
                                    <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">{{ $c->uso ?? '—' }}</td>
                                    <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                                        @if(!is_null($c->tecnologia))
                                            {{ strtoupper($c->tecnologia) }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                                        @if(!is_null($c->dispositivo))
                                            {{ $c->dispositivo }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">{{ $c->megas ?? '—' }}</td>
                                    <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                                        @if(!is_null($c->tarifa))
                                            ${{ number_format((float) $c->tarifa, 2) }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                                        @if(!is_null($c->fecha_contratacion))
                                            {{ \Carbon\Carbon::parse($c->fecha_contratacion)->format('d/m/Y') }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 whitespace-nowrap text-sm">
                                        <span class="inline-flex items-center px-2 py-1 rounded bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200">
                                            {{ optional($c->estado)->nombre ?? '—' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2 whitespace-nowrap text-sm">
                                        <span class="inline-flex items-center px-2 py-1 rounded bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200">
                                            {{ optional($c->estatusServicio)->nombre ?? '—' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2 whitespace-nowrap text-sm text-center">
                                        <a
                                            href="{{ route('contrataciones.clientes.show', $c->id) }}"
                                            class="inline-flex items-center px-2 py-1 border border-gray-300 dark:border-gray-600 rounded text-xs text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700"
                                        >
                                            Ver
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="14" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                                        No hay clientes registrados.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

