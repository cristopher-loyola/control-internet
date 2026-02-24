<x-app-layout title="Cliente">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            Vista completa de datos del usuario seleccionado
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="mb-4">
                        <a href="{{ route('contrataciones.clientes.index') }}" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">
                            ← Volver a la lista de clientes
                        </a>
                    </div>

                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <dt class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">ID</dt>
                            <dd class="text-sm">{{ $cliente->id }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Número de Cliente</dt>
                            <dd class="text-sm">{{ $cliente->numero_servicio ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Nombre</dt>
                            <dd class="text-sm">{{ $cliente->nombre_cliente ?? '—' }}</dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Dirección</dt>
                            <dd class="text-sm">{{ $cliente->domicilio ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Comunidad</dt>
                            <dd class="text-sm">{{ $cliente->comunidad ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Número Telefónico</dt>
                            <dd class="text-sm">{{ $cliente->telefono ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Uso</dt>
                            <dd class="text-sm">{{ $cliente->uso ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Tecnología</dt>
                            <dd class="text-sm">{{ $cliente->tecnologia ? strtoupper($cliente->tecnologia) : '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Dispositivo</dt>
                            <dd class="text-sm">{{ $cliente->dispositivo ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Megas</dt>
                            <dd class="text-sm">{{ $cliente->megas ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Costo de paquete</dt>
                            <dd class="text-sm">
                                @if(!is_null($cliente->tarifa))
                                    ${{ number_format((float) $cliente->tarifa, 2) }}
                                @else
                                    —
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Fecha del siguiente cobro</dt>
                            <dd class="text-sm">
                                @if(!is_null($cliente->fecha_contratacion))
                                    {{ \Carbon\Carbon::parse($cliente->fecha_contratacion)->format('d/m/Y') }}
                                @else
                                    —
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Paquete (texto completo)</dt>
                            <dd class="text-sm">{{ $cliente->paquete ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Estado</dt>
                            <dd class="text-sm">{{ optional($cliente->estado)->nombre ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Estatus de servicio</dt>
                            <dd class="text-sm">{{ optional($cliente->estatusServicio)->nombre ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Servicio ID</dt>
                            <dd class="text-sm">{{ $cliente->servicio_id ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Creado en</dt>
                            <dd class="text-sm">{{ optional($cliente->created_at)->format('d/m/Y H:i') ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Actualizado en</dt>
                            <dd class="text-sm">{{ optional($cliente->updated_at)->format('d/m/Y H:i') ?? '—' }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
