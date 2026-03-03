<x-app-layout title="Historial de recibos">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            Historial de recibos impresos
        </h2>
    </x-slot>

    <div class="py-6" x-data="{ cancelId:null, motivo:'' }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="mb-4 flex items-center justify-between gap-4">
                        <a href="{{ route('admin.pagos.index') }}" class="btn btn-primary">
                            Volver a pagos
                        </a>
                        <form method="GET" action="{{ route('admin.pagos.historial') }}" class="flex flex-wrap items-end gap-3">
                            <div>
                                <label class="text-xs uppercase text-gray-500 dark:text-gray-400 block">Desde</label>
                                <input type="date" name="from" value="{{ $from }}" class="form-input mt-1">
                            </div>
                            <div>
                                <label class="text-xs uppercase text-gray-500 dark:text-gray-400 block">Hasta</label>
                                <input type="date" name="to" value="{{ $to }}" class="form-input mt-1">
                            </div>
                            <div>
                                <label class="text-xs uppercase text-gray-500 dark:text-gray-400 block">Cliente / Número</label>
                                <input type="text" name="cliente" value="{{ $cliente }}" class="form-input mt-1" placeholder="Nombre o número">
                            </div>
                            <div class="flex items-center gap-2 mt-5">
                                <button type="submit" class="btn btn-primary">Filtrar</button>
                                <a href="{{ route('admin.pagos.historial.export', array_merge(request()->query(), ['format'=>'csv'])) }}" class="btn btn-success">Exportar Excel</a>
                                <a href="{{ route('admin.pagos.historial.export', array_merge(request()->query(), ['format'=>'pdf'])) }}" target="_blank" class="btn btn-danger">Exportar PDF</a>
                            </div>
                        </form>
                    </div>

                    @if (session('status'))
                        <div class="mb-3 px-3 py-2 rounded bg-emerald-600 text-white text-sm">
                            {{ session('status') }}
                        </div>
                    @endif

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700/40">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Folio</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Fecha</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Monto</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Cliente</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Número</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Estado</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Usuario</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach ($rows as $r)
                                    <tr class="{{ $r->deleted_at ? 'bg-rose-50/80 dark:bg-rose-900/20' : '' }}">
                                        <td class="px-4 py-2 whitespace-nowrap text-sm font-mono">{{ str_pad((string)$r->reference_number, 8, '0', STR_PAD_LEFT) }}</td>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm">{{ optional($r->created_at)->format('d/m/Y H:i') }}</td>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm">${{ number_format((float)$r->total, 2) }}</td>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm">{{ $r->cliente ?? '—' }}</td>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm">{{ $r->numero_servicio ?? '—' }}</td>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm">
                                            <span class="inline-flex items-center px-2 py-1 rounded text-xs {{ $r->status === 'Cancelado' ? 'bg-rose-600 text-white' : 'bg-emerald-600 text-white' }}">
                                                {{ $r->status }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm">{{ $r->user_name ?? '—' }}</td>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm text-right">
                                            <a href="{{ route('admin.pagos.index') }}?folio={{ $r->reference_number }}" class="btn btn-info btn-sm">Re-imprimir</a>
                                            @if(!$r->deleted_at)
                                                <button type="button" class="btn btn-danger btn-sm" x-on:click.prevent="cancelId={{ $r->id }}; motivo=''; $dispatch('open-modal','admin-pagos-cancelar')">Cancelar</button>
                                            @else
                                                <span class="text-xs text-gray-500">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">
                        {{ $paginator->links() }}
                    </div>
                </div>
            </div>
        </div>

        <x-modal name="admin-pagos-cancelar" maxWidth="sm" focusable>
            <form method="POST" :action="cancelId ? '{{ route('admin.pagos.facturas.cancel', ['id'=>'__ID__']) }}'.replace('__ID__', cancelId) : '#'" class="p-6">
                @csrf
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">Cancelar recibo</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Esta acción invalidará el folio. Ingresa el motivo.</p>
                <div>
                    <x-input-label for="motivo" value="Motivo" />
                    <x-text-input id="motivo" name="motivo" type="text" class="mt-1 block w-full" x-model="motivo" required />
                </div>
                <div class="mt-6 flex justify-end">
                    <x-secondary-button x-on:click="$dispatch('close')">Cerrar</x-secondary-button>
                    <button type="submit" class="ms-3 btn btn-danger" :disabled="!motivo || motivo.length===0">Cancelar recibo</button>
                </div>
            </form>
        </x-modal>
    </div>
</x-app-layout>

