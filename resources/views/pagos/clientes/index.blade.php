<x-app-layout title="Clientes">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            {{ __('Clientes') }}
        </h2>
    </x-slot>

    <div class="py-12" x-data="{
        selected: null,
        isLoading: false,
        form: { id: null, numero_servicio: '', nombre_cliente: '', domicilio: '', comunidad: '', telefono: '', uso: '', megas: '', tecnologia: '', dispositivo: '', tarifa: '', estado_id: '', estatus_servicio_id: '' },
        editMegasReadonly: false,
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
        },
        parseCost(val) {
            if (!val) return null;
            if (typeof val === 'string') val = parseFloat(val);
            return Math.round(val);
        },
        assignMegas(costo, tecnologia) {
            const c = this.parseCost(costo);
            const t = (tecnologia || '').toUpperCase();
            const validC = [300, 400, 500, 600];
            const validT = ['FOD', 'FOI', 'INA'];
            if (!validC.includes(c) || !validT.includes(t)) return null;
            const matrix = {
                300: { FOD: 30, FOI: 20, INA: 12 },
                400: { FOD: 50, FOI: 30, INA: 20 },
                500: { FOD: 70, FOI: 40, INA: 30 },
                600: { FOD: 100, FOI: 50, INA: 40 },
            };
            return matrix[c][t];
        },
        updateMegasEdit() {
            const m = this.assignMegas(this.form.tarifa, this.form.tecnologia);
            if (m !== null) {
                this.form.megas = m;
                this.editMegasReadonly = true;
            } else {
                this.editMegasReadonly = false;
            }
        },
        openEdit() {
            if (this.form.id) this.$dispatch('open-modal', 'pagos-clientes-edit')
        }
    }">
        <div class="max-w-none w-full mx-auto sm:px-4 lg:px-8">
            @if (session('import_report'))
                <div class="mb-4">
                    <div x-data x-init="$nextTick(() => $dispatch('open-modal', 'pagos-clientes-import-result'))"></div>
                </div>
            @endif

            <div class="flex justify-between items-center mb-4 gap-3">
                <form action="{{ route('pagos.clientes.index') }}" method="GET" class="flex items-center gap-2" x-on:submit="isLoading = true">
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="Buscar por nombre, número o teléfono..." class="form-input rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm py-1 w-64">
                    <select name="tec" class="form-select rounded-md border-gray-300 text-sm py-1 px-2">
                        <option value="">Filtrar por rango</option>
                        <option value="ina" {{ request('tec') === 'ina' ? 'selected' : '' }}>INA (1000–4200)</option>
                        <option value="foi" {{ request('tec') === 'foi' ? 'selected' : '' }}>FOI (4800–5999)</option>
                        <option value="fod" {{ request('tec') === 'fod' ? 'selected' : '' }}>FOD (6000+)</option>
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm">Buscar</button>
                    @if(request('q') || request('tec'))
                        <a href="{{ route('pagos.clientes.index') }}" class="btn btn-secondary btn-sm" style="text-decoration: none;">Limpiar</a>
                    @endif
                </form>

                <div class="flex gap-3">
                    <button type="button" class="btn btn-primary" x-data x-on:click.prevent="$dispatch('open-modal', 'pagos-clientes-historial-buscar')">Buscar historial</button>
                    <a :href="selected ? '{{ route('pagos.clientes.historial', ['numero' => '__NUM__']) }}'.replace('__NUM__', form.numero_servicio ?? '') : '#'" :class="selected ? 'btn btn-info' : 'btn btn-secondary opacity-50'" :aria-disabled="!selected">Historial</a>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <style>.skel{position:relative;overflow:hidden;background-color:rgba(229,231,235,1);border-radius:4px}.skel::after{content:"";position:absolute;inset:0;transform:translateX(-100%);background:linear-gradient(90deg,transparent,rgba(255,255,255,.5),transparent);animation:skel-shimmer 1.4s infinite}@keyframes skel-shimmer{100%{transform:translateX(100%)}}</style>

                    @if (session('status') === 'cliente-actualizado')
                        <div x-data x-init="Swal.fire({ icon: 'success', title: '¡Éxito!', text: 'Cliente actualizado.' })"></div>
                    @endif

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
                                            <a href="{{ route('pagos.clientes.show', $c->id) }}" class="btn btn-warning btn-sm" x-on:click.stop>Ver</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="9" class="px-4 py-6 text-center text-sm text-gray-500">No hay clientes.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($clientes->hasPages())
                        <div class="mt-4">{{ $clientes->links() }}</div>
                    @endif
            </div>
        </div>

        {{-- Modales --}}
        <x-modal name="pagos-clientes-historial-buscar" maxWidth="sm" focusable>
            <form method="GET" action="{{ route('pagos.clientes.historial.buscar') }}" class="p-6">
                <h3 class="btn btn-primary">Buscar historial</h3>
                <x-input-label for="historial_q" value="Número o nombre" />
                <x-text-input id="historial_q" name="q" type="text" class="mt-1 block w-full" placeholder="Ej. 122" required />
                <div class="mt-6 flex justify-end">
                    <x-secondary-button x-on:click="$dispatch('close')">Cancelar</x-secondary-button>
                    <button type="submit" class="ms-3 btn btn-primary">Buscar</button>
                </div>
            </form>
        </x-modal>

        <x-modal name="pagos-clientes-edit" :show="$errors->clienteEdit->isNotEmpty()" maxWidth="lg" focusable>
            <form method="POST" action="{{ route('pagos.clientes.edit') }}" class="p-6">
                @csrf
                <input type="hidden" name="id" x-model="form.id">
                <h3 class="text-lg font-medium">Editar cliente</h3>
                @if ($errors->clienteEdit->any())
                    <div class="mt-3 mb-4 p-3 rounded bg-red-600 text-white text-sm">{{ $errors->clienteEdit->first() }}</div>
                @endif
                <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div><x-input-label for="edit_numero_servicio" value="Número" /><x-text-input id="edit_numero_servicio" name="numero_servicio" type="number" class="mt-1 block w-full" x-model="form.numero_servicio" required /></div>
                    <div><x-input-label for="edit_nombre_cliente" value="Nombre" /><x-text-input id="edit_nombre_cliente" name="nombre_cliente" type="text" class="mt-1 block w-full" x-model="form.nombre_cliente" required /></div>
                    <div class="sm:col-span-2"><x-input-label for="edit_domicilio" value="Dirección" /><x-text-input id="edit_domicilio" name="domicilio" type="text" class="mt-1 block w-full" x-model="form.domicilio" /></div>
                    <input type="hidden" name="comunidad" x-model="form.comunidad">
                    <div><x-input-label for="edit_telefono" value="Teléfono" /><x-text-input id="edit_telefono" name="telefono" type="text" class="mt-1 block w-full" x-model="form.telefono" /></div>
                    <div><x-input-label for="edit_uso" value="Uso" /><select id="edit_uso" name="uso" class="form-select mt-1 w-full" x-model="form.uso"><option value="">Selecciona...</option><option value="hogar">Hogar</option><option value="empresarial">Empresarial</option><option value="escolar">Escolar</option></select></div>
                    <div><x-input-label for="edit_dispositivo" value="Dispositivo" /><select id="edit_dispositivo" name="dispositivo" class="form-select mt-1 w-full" x-model="form.dispositivo"><option value="">Selecciona...</option><option value="permanencia voluntaria">Permanencia voluntaria</option><option value="como dato">Como dato</option></select></div>
                    <div><x-input-label for="edit_tecnologia" value="Tecnología" /><select id="edit_tecnologia" name="tecnologia" class="form-select mt-1 w-full" x-model="form.tecnologia" x-on:change="updateMegasEdit()"><option value="">Selecciona...</option><option value="ina">INA</option><option value="foi">FOI</option><option value="fod">FOD</option></select></div>
                    <div><x-input-label for="edit_tarifa" value="Paquete" /><select id="edit_tarifa" name="tarifa" class="form-select mt-1 w-full" x-model="form.tarifa" x-on:change="updateMegasEdit()"><option value="">Selecciona...</option><option value="300.00">$300</option><option value="400.00">$400</option><option value="500.00">$500</option><option value="600.00">$600</option></select></div>
                    <div><x-input-label for="edit_megas" value="Megas" /><x-text-input id="edit_megas" name="megas" type="number" class="mt-1 block w-full" x-model="form.megas" x-bind:readonly="editMegasReadonly" /></div>
                    <div><x-input-label for="edit_estado" value="Estado" /><select id="edit_estado" name="estado_id" class="form-select mt-1 w-full" x-model="form.estado_id"><option value="">Selecciona...</option><option value="1">Activado</option><option value="2">Desactivado</option></select></div>
                    <div><x-input-label for="edit_estatus" value="Estatus" /><select id="edit_estatus" name="estatus_servicio_id" class="form-select mt-1 w-full" x-model="form.estatus_servicio_id"><option value="">Selecciona...</option><option value="1">Pagado</option><option value="2">Suspendido</option><option value="3">Cancelado</option><option value="4">Pendiente</option></select></div>
                </div>
                <div class="mt-6 flex justify-end"><x-secondary-button x-on:click="$dispatch('close')">Cancelar</x-secondary-button><button type="submit" class="ms-3 btn btn-primary">Guardar</button></div>
            </form>
        </x-modal>
    </div>
</x-app-layout>
