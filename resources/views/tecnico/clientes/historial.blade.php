<x-app-layout title="Historial del Cliente">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            {{ __('Historial del Cliente') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-4 flex items-center gap-2">
                <a href="{{ route('tecnico.clientes.index') }}" class="btn btn-secondary">← Volver a clientes</a>
                @if($actual)
                    <a href="{{ route('tecnico.clientes.show', $actual->id) }}" class="btn btn-primary">Ver Cliente Actual</a>
                @endif
            </div>

            {{-- Información actual --}}
            @if($actual)
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <h3 class="text-lg font-bold mb-4">Información Actual</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="text-sm text-gray-500">Número</label>
                                <p class="font-semibold">{{ $actual->numero_servicio }}</p>
                            </div>
                            <div>
                                <label class="text-sm text-gray-500">Nombre</label>
                                <p class="font-semibold">{{ $actual->nombre_cliente }}</p>
                            </div>
                            <div>
                                <label class="text-sm text-gray-500">Estado</label>
                                <p>
                                    <span class="px-2 py-1 rounded-full text-xs {{ $actual->estado && $actual->estado->nombre === 'Activado' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $actual->estado->nombre ?? 'N/A' }}
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Historial --}}
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-bold mb-4">Historial de Cambios</h3>
                    
                    @if($historial->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead>
                                    <tr class="text-left text-gray-600 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                                        <th class="py-2">Fecha</th>
                                        <th class="py-2">Estado</th>
                                        <th class="py-2">Estatus</th>
                                        <th class="py-2">Domicilio</th>
                                        <th class="py-2">Teléfono</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                    @foreach($historial as $h)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                            <td class="py-2">{{ $h->created_at->format('d/m/Y H:i') }}</td>
                                            <td class="py-2">
                                                <span class="px-2 py-1 rounded-full text-xs {{ $h->estado && $h->estado->nombre === 'Activado' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                    {{ $h->estado->nombre ?? 'N/A' }}
                                                </span>
                                            </td>
                                            <td class="py-2">
                                                <span class="px-2 py-1 rounded-full text-xs bg-gray-100 text-gray-800">
                                                    {{ $h->estatusServicio->nombre ?? 'N/A' }}
                                                </span>
                                            </td>
                                            <td class="py-2">{{ $h->domicilio }}</td>
                                            <td class="py-2">{{ $h->telefono }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-gray-500">No hay historial disponible</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
