<x-app-tecnico-layout title="Clientes">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            {{ __('Clientes') }}
        </h2>
    </x-slot>

    <div class="py-12" x-data="{
        selected: null,
        isLoading: false,
        form: { id: null, numero_servicio: '', nombre_cliente: '', domicilio: '', comunidad: '', telefono: '', uso: '', megas: '', tecnologia: '', dispositivo: '', tarifa: '', estado_id: '', estatus_servicio_id: '' },
        selectRow(row) {
            this.selected = row.id;
            this.form = {
                id: row.id,
                numero_servicio: row.numero_servicio ?? '',
                nombre_cliente: row.nombre_cliente ?? '',
                domicilio: row.domicilio ?? '',
                comunidad: row.comunidad ?? '',
                telefono: row.telefono ?? '',
                uso: row.uso ?? '',
                megas: row.megas ?? '',
                tecnologia: row.tecnologia ?? '',
                dispositivo: row.dispositivo ?? '',
                tarifa: row.tarifa ?? '',
                estado_id: row.estado_id ?? '',
                estatus_servicio_id: row.estatus_servicio_id ?? '',
            };
        }
    }">
        @if (session('status') === 'cliente-actualizado')
            <div x-data x-init="Swal.fire({ icon: 'success', title: '¡Éxito!', text: 'Cliente actualizado correctamente.' })"></div>
        @endif
        <div class="max-w-none w-full mx-auto sm:px-4 lg:px-8">

            <div class="flex justify-between items-center mb-4 gap-3">
                <form action="{{ route('tecnico.clientes.index') }}" method="GET" class="flex items-center gap-2" x-on:submit="isLoading = true">
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="Buscar por nombre, número o teléfono..." class="form-input rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm py-1 w-64">
                    <select name="tec" class="form-select rounded-md border-gray-300 text-sm py-1 px-2">
                        <option value="">Filtrar por rango</option>
                        <option value="ina" {{ request('tec') === 'ina' ? 'selected' : '' }}>INA (1000–4200)</option>
                        <option value="foi" {{ request('tec') === 'foi' ? 'selected' : '' }}>FOI (4800–5999)</option>
                        <option value="fod" {{ request('tec') === 'fod' ? 'selected' : '' }}>FOD (6000+)</option>
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm">Buscar</button>
                    @if(request('q') || request('tec'))
                        <a href="{{ route('tecnico.clientes.index') }}" class="btn btn-secondary btn-sm" style="text-decoration: none;">Limpiar</a>
                    @endif
                </form>

                <div class="flex gap-3">
                    <button type="button" class="btn btn-primary" x-data x-on:click.prevent="$dispatch('open-modal', 'tecnico-clientes-historial-buscar')">Buscar historial</button>
                    <a :href="selected ? '{{ route('tecnico.clientes.historial', ['numero' => '__NUM__']) }}'.replace('__NUM__', form.numero_servicio ?? '') : '#'" :class="selected ? 'btn btn-info' : 'btn btn-secondary opacity-50'" :aria-disabled="!selected">Historial</a>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <style>.skel{position:relative;overflow:hidden;background-color:rgba(229,231,235,1);border-radius:4px}.skel::after{content:"";position:absolute;inset:0;transform:translateX(-100%);background:linear-gradient(90deg,transparent,rgba(255,255,255,.5),transparent);animation:skel-shimmer 1.4s infinite}@keyframes skel-shimmer{100%{transform:translateX(100%)}}
                        @media (max-width: 640px) {
                            .max-w-none .flex.justify-between.items-center.mb-4 { flex-direction: column; gap: 0.75rem; align-items: stretch; }
                            .max-w-none .flex.justify-between.items-center.mb-4 form.flex.items-center.gap-2 { flex-direction: column; align-items: stretch; }
                            .max-w-none .flex.justify-between.items-center.mb-4 form.flex.items-center.gap-2 > * { width: 100%; }
                            .max-w-none .flex.justify-between.items-center.mb-4 form.flex.items-center.gap-2 input,
                            .max-w-none .flex.justify-between.items-center.mb-4 form.flex.items-center.gap-2 select { width: 100%; max-width: none; font-size: 1rem; min-height: 2.75rem; }
                            .max-w-none .flex.justify-between.items-center.mb-4 .flex.gap-3 { flex-wrap: wrap; justify-content: center; }
                            .max-w-none .flex.justify-between.items-center.mb-4 .flex.gap-3 > * { flex: 1 1 calc(50% - 0.375rem); min-width: 140px; }
                            .max-w-none .btn { font-size: 0.875rem; padding: 0.5rem 0.75rem; }
                            .max-w-none .btn-sm { font-size: 0.8125rem; padding: 0.5rem 0.75rem; }
                            .max-w-none .overflow-x-auto { overflow-x: auto; -webkit-overflow-scrolling: touch; }
                            .max-w-none table { font-size: 0.75rem; }
                            .max-w-none th, .max-w-none td { padding: 0.375rem 0.5rem; white-space: nowrap; }
                            .max-w-none .h-10 { height: 2.25rem; }
                            .max-w-none .p-6 { padding: 0.75rem; }
                        }
                    </style>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700/40">
                                <tr>
                                    <th class="h-10 px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Número</th>
                                    <th class="h-10 px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Nombre</th>
                                    <th class="h-10 px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Dirección</th>
                                    <th class="h-10 px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Teléfono</th>
                                    <th class="h-10 px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tecnología</th>
                                    <th class="h-10 px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Megas</th>
                                    <th class="h-10 px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Paquete</th>
                                    <th class="h-10 px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase">Estatus</th>
                                    <th class="h-10 px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Ver</th>
                                </tr>
                            </thead>
                            <tbody x-show="isLoading" class="bg-white dark:bg-gray-800 divide-y divide-gray-200" x-cloak>
                                @foreach(range(1,8) as $i)
                                <tr>@foreach(range(1,9) as $j)<td class="h-10 px-4 py-2"><div class="skel h-4 w-full"></div></td>@endforeach</tr>
                                @endforeach
                            </tbody>
                            <tbody x-show="!isLoading" class="bg-white dark:bg-gray-800 divide-y divide-gray-200">
                                @forelse ($clientes as $c)
                                    <tr x-on:click="selectRow(@js(['id' => $c->id, 'numero_servicio' => $c->numero_servicio, 'nombre_cliente' => $c->nombre_cliente, 'domicilio' => $c->domicilio, 'comunidad' => $c->comunidad, 'telefono' => $c->telefono, 'uso' => $c->uso, 'tecnologia' => $c->tecnologia, 'dispositivo' => $c->dispositivo, 'megas' => $c->megas, 'tarifa' => $c->tarifa, 'estado_id' => $c->estado_id, 'estatus_servicio_id' => $c->estatus_servicio_id]))" :class="selected === {{ $c->id }} ? 'bg-indigo-50 dark:bg-gray-700/40' : ''" class="cursor-pointer hover:bg-gray-50">
                                        <td class="h-10 px-4 py-2 whitespace-nowrap text-sm">{{ $c->numero_servicio }}</td>
                                        <td class="h-10 px-4 py-2 whitespace-nowrap text-sm">
                                            <span class="inline-flex items-center gap-2">
                                                <span class="inline-block w-2 h-2 rounded-full {{ !is_null($c->fecha_contratacion) ? 'bg-emerald-600' : 'bg-gray-300' }}"></span>
                                                <span>{{ $c->nombre_cliente }}</span>
                                            </span>
                                        </td>
                                        <td class="h-10 px-4 py-2 whitespace-nowrap text-sm">{{ $c->domicilio ?? '—' }}</td>
                                        <td class="h-10 px-4 py-2 whitespace-nowrap text-sm">{{ $c->telefono ?? '—' }}</td>
                                        <td class="h-10 px-4 py-2 whitespace-nowrap text-sm">{{ $c->tecnologia ? strtoupper($c->tecnologia) : '—' }}</td>
                                        <td class="h-10 px-4 py-2 whitespace-nowrap text-sm">{{ $c->megas ?? '—' }}</td>
                                        <td class="h-10 px-4 py-2 whitespace-nowrap text-sm">{{ $c->tarifa ? '$' . number_format((float) $c->tarifa, 2) : '—' }}</td>
                                        <td class="h-10 px-2 py-2 text-sm">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded bg-gray-100 text-xs">
                                                {{ optional($c->estatusServicio)->nombre ?? '—' }}/{{ optional($c->estado)->nombre ?? '—' }}
                                            </span>
                                        </td>
                                        <td class="h-10 px-4 py-2 whitespace-nowrap text-sm text-center">
                                            <a href="{{ route('tecnico.clientes.show', $c->id) }}" class="btn btn-warning btn-sm" x-on:click.stop>Ver</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="9" class="px-4 py-6 text-center text-sm text-gray-500">No hay clientes.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($clientes->hasPages())
                        <div class="mt-6 flex flex-col items-center gap-2">
                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                Mostrando {{ $clientes->firstItem() }} a {{ $clientes->lastItem() }} de {{ $clientes->total() }} resultados
                            </div>
                            {{ $clientes->links('pagination::tailwind') }}
                        </div>
                    @endif
            </div>
        </div>

        {{-- Modal Buscar Historial --}}
        <x-modal name="tecnico-clientes-historial-buscar" maxWidth="sm" focusable>
            <form method="GET" action="{{ route('tecnico.clientes.historial.buscar') }}" class="p-6">
                <h3 class="text-lg font-medium">Buscar historial</h3>
                <x-input-label for="historial_q" value="Número o nombre" />
                <x-text-input id="historial_q" name="q" type="text" class="mt-1 block w-full" placeholder="Ej. 122" required />
                <div class="mt-6 flex justify-end">
                    <x-secondary-button x-on:click="$dispatch('close')">Cancelar</x-secondary-button>
                    <button type="submit" class="ms-3 btn btn-primary">Buscar</button>
                </div>
            </form>
        </x-modal>
    </div>
</x-app-layout>
