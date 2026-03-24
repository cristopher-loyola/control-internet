<x-app-layout title="Clientes">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            {{ __('Clientes') }}
        </h2>
    </x-slot>

    <div class="py-12" x-data="{
        selected: null,
        isNuevoCliente: true,
        isLoading: false,
        numerosDisponibles: [],
        numerosFiltrados: [],
        totalDisponibles: 0,
        ultimoNumero: 0,
        current_page: 1,
        last_page: 1,
        busquedaNumero: '',
        busquedaActual: '',
        form: { id: null, numero_servicio: '', nombre_cliente: '', domicilio: '', comunidad: '', telefono: '', uso: '', megas: '', tecnologia: '', dispositivo: '', tarifa: '', estado_id: '', estatus_servicio_id: '' },
        editMegasReadonly: false,
        createMegasReadonly: false,
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
        updateMegasCreate() {
            const costo = this.$refs.createTarifa?.value;
            const tec = this.$refs.createTecnologia?.value;
            const m = this.assignMegas(costo, tec);
            if (m !== null && this.$refs.createMegas) {
                this.$refs.createMegas.value = m;
                this.createMegasReadonly = true;
            } else {
                this.createMegasReadonly = false;
            }
        },
        openEdit() {
            if (this.form.id) this.$dispatch('open-modal', 'pagos-clientes-edit')
        },
        cargarNumerosDisponibles() {
            fetch('{{ route("pagos.clientes.numeros-disponibles") }}')
                .then(response => response.json())
                .then(data => {
                    this.numerosDisponibles = data.numeros;
                    this.numerosFiltrados = [...data.numeros]; // Inicializar filtered
                    this.totalDisponibles = data.total;
                    this.ultimoNumero = data.ultimoNumero;
                    this.current_page = data.current_page;
                    this.last_page = data.last_page;
                    this.busquedaActual = data.busqueda || '';
                })
                .catch(error => {
                    console.error('Error al cargar números disponibles:', error);
                });
        },
        copiarNumero(numero) {
            navigator.clipboard.writeText(numero).then(() => {
                Swal.fire({
                    icon: 'success',
                    title: '¡Copiado!',
                    text: 'Número ' + numero + ' copiado al portapapeles',
                    timer: 2000,
                    showConfirmButton: false
                });
            }).catch(err => {
                console.error('Error al copiar:', err);
            });
        },
        cargarPagina(page) {
            const url = new URL('{{ route("pagos.clientes.numeros-disponibles") }}', window.location.origin);
            url.searchParams.set('page', page);
            if (this.busquedaNumero) {
                url.searchParams.set('busqueda', this.busquedaNumero);
            }
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    this.numerosDisponibles = data.numeros;
                    this.numerosFiltrados = [...data.numeros]; // Ya viene filtrado del backend
                    this.totalDisponibles = data.total;
                    this.ultimoNumero = data.ultimoNumero;
                    this.current_page = data.current_page;
                    this.last_page = data.last_page;
                    this.busquedaActual = data.busqueda || '';
                })
                .catch(error => {
                    console.error('Error al cargar números disponibles:', error);
                });
        },
        filtrarNumeros() {
            // Buscar con backend incluyendo búsqueda
            const url = new URL('{{ route("pagos.clientes.numeros-disponibles") }}', window.location.origin);
            if (this.busquedaNumero) {
                url.searchParams.set('busqueda', this.busquedaNumero);
            }
            url.searchParams.set('page', 1); // Resetear a página 1 al buscar
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    this.numerosDisponibles = data.numeros;
                    this.numerosFiltrados = [...data.numeros]; // Ya viene filtrado del backend
                    this.totalDisponibles = data.total;
                    this.ultimoNumero = data.ultimoNumero;
                    this.current_page = data.current_page;
                    this.last_page = data.last_page;
                    this.busquedaActual = data.busqueda || '';
                })
                .catch(error => {
                    console.error('Error al filtrar números:', error);
                });
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
                    <button type="button" class="btn btn-primary" x-data x-on:click.prevent="$dispatch('open-modal', 'pagos-clientes-add-confirm')">Añadir</button>
                    <button type="button" class="btn btn-secondary" x-data x-on:click.prevent="$dispatch('open-modal', 'pagos-clientes-historial-buscar')">Buscar historial</button>
                    <button type="button" class="btn btn-info" x-data x-on:click.prevent="cargarNumerosDisponibles(); $dispatch('open-modal', 'pagos-clientes-numeros-disponibles')" title="Ver números de cliente disponibles">Números disponibles</button>
                    <a :href="selected ? '{{ route('pagos.clientes.historial', ['numero' => '__NUM__']) }}'.replace('__NUM__', form.numero_servicio ?? '') : '#'" :class="selected ? 'btn btn-info' : 'btn btn-primary info'" :aria-disabled="!selected">Historial</a>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <style>
                        .skel{position:relative;overflow:hidden;background-color:rgba(229,231,235,var(--tw-bg-opacity,1));border-radius:4px}
                        .skel::after{content:"";position:absolute;inset:0;transform:translateX(-100%);background:linear-gradient(90deg,transparent,rgba(255,255,255,.5),transparent);animation:skel-shimmer 1.4s infinite}
                        @keyframes skel-shimmer{100%{transform:translateX(100%)}}
                        @media (prefers-reduced-motion:reduce){.skel::after{animation:none}}
                    </style>

                    @if (session('status') === 'cliente-actualizado')
                        <div x-data x-init="Swal.fire({ icon: 'success', title: '¡Éxito!', text: 'Cliente actualizado.' })"></div>
                    @endif
                    @if (session('status') === 'cliente-creado')
                        <div x-data x-init="Swal.fire({ icon: 'success', title: '¡Éxito!', text: 'Cliente creado correctamente.' })"></div>
                    @endif

                    <div class="overflow-x-auto" style="scrollbar-gutter: stable both-edges;">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700" style="table-layout:fixed">
                            <colgroup>
                                <col style="width:8%">
                                <col style="width:18%">
                                <col style="width:18%">
                                <col style="width:14%">
                                <col style="width:8%">
                                <col style="width:8%">
                                <col style="width:10%">
                                <col style="width:10%">
                                <col style="width:6%">
                            </colgroup>
                            <thead class="bg-gray-50 dark:bg-gray-700/40">
                                <tr>
                                    <th class="h-10 px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Número de Cliente</th>
                                    <th class="h-10 px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nombre</th>
                                    <th class="h-10 px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Dirección</th>
                                    <th class="h-10 px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Número Telefónico</th>
                                    <th class="h-10 px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tecnología</th>
                                    <th class="h-10 px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Megas</th>
                                    <th class="h-10 px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Paquete</th>
                                    <th class="h-10 px-2 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-36 sm:w-40">Estatus</th>
                                    <th class="h-10 px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Acciones</th>
                                </tr>
                            </thead>
                            <tbody x-show="isLoading" aria-busy="true" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700" x-cloak>
                                @foreach(range(1,8) as $i)
                                <tr>@foreach(range(1,9) as $j)<td class="h-10 px-4 py-2"><div class="skel h-4 w-full"></div></td>@endforeach</tr>
                                @endforeach
                            </tbody>
                            <tbody x-show="!isLoading" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse ($clientes as $c)
                                    <tr x-on:click="selectRow(@js(['id' => $c->id, 'numero_servicio' => $c->numero_servicio, 'nombre_cliente' => $c->nombre_cliente, 'domicilio' => $c->domicilio, 'comunidad' => $c->comunidad, 'telefono' => $c->telefono, 'uso' => $c->uso, 'tecnologia' => $c->tecnologia, 'dispositivo' => $c->dispositivo, 'megas' => $c->megas, 'tarifa' => $c->tarifa, 'estado_id' => $c->estado_id, 'estatus_servicio_id' => $c->estatus_servicio_id]))" :class="selected === {{ $c->id }} ? 'bg-indigo-50 dark:bg-gray-700/40' : ''" class="cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                        <td class="h-10 px-4 py-2 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">{{ $c->numero_servicio }}</td>
                                        <td class="h-10 px-4 py-2 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                            <span class="inline-flex items-center gap-2">
                                                <span class="inline-block w-2 h-2 rounded-full {{ !is_null($c->fecha_contratacion) ? 'bg-emerald-600' : 'bg-gray-300' }}"></span>
                                                <span>{{ $c->nombre_cliente }}</span>
                                            </span>
                                        </td>
                                        <td class="h-10 px-4 py-2 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">{{ $c->domicilio ?? '—' }}</td>
                                        <td class="h-10 px-4 py-2 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">{{ $c->telefono ?? '—' }}</td>
                                        <td class="h-10 px-4 py-2 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">{{ $c->tecnologia ? strtoupper($c->tecnologia) : '—' }}</td>
                                        <td class="h-10 px-4 py-2 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">{{ $c->megas ?? '—' }}</td>
                                        <td class="h-10 px-4 py-2 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">{{ $c->tarifa ? '$' . number_format((float) $c->tarifa, 2) : '—' }}</td>
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
                                    <tr><td colspan="9" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">No hay clientes.</td></tr>
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

        <x-modal name="pagos-clientes-add-confirm" maxWidth="sm" focusable>
        <div class="p-5">
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2 text-center">Registrar cliente</h3>
            <p class="text-sm text-gray-700 dark:text-gray-300 mb-4 text-center">¿Es un nuevo cliente?</p>
            <div class="flex justify-center gap-3">
                <button
                    type="button"
                    class="btn btn-primary"
                    x-on:click="isNuevoCliente = true; $dispatch('close'); $dispatch('open-modal', 'admin-clientes-add')"
                >
                    Sí, es nuevo cliente
                </button>
                <button
                    type="button"
                    class="btn btn-danger"
                    x-on:click="isNuevoCliente = false; $dispatch('close'); $dispatch('open-modal', 'admin-clientes-add')"
                >
                    No
                </button>
            </div>
        </div>
        </x-modal>

        <x-modal name="admin-clientes-add" :show="$errors->clienteCreate->isNotEmpty()" maxWidth="lg" focusable>
        <form method="POST" action="{{ route('pagos.store') }}" class="p-6">
            @csrf
            <h3
                class="text-lg font-medium text-gray-900 dark:text-gray-100"
                x-text="isNuevoCliente ? 'Añadir nuevo cliente' : 'Añadir cliente'"
            ></h3>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Completa los datos del nuevo cliente.
            </p>
            @if ($errors->clienteCreate->any())
                <div class="mt-3 mb-4 p-3 rounded bg-red-600 text-white text-sm">{{ $errors->clienteCreate->first() }}</div>
            @endif
            <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <x-input-label for="numero_servicio" value="Número de Cliente" />
                    <x-text-input id="numero_servicio" name="numero_servicio" type="number" class="mt-1 block w-full" :value="old('numero_servicio')" required />
                    <x-input-error :messages="$errors->clienteCreate->get('numero_servicio')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="nombre_cliente" value="Nombre" />
                    <x-text-input id="nombre_cliente" name="nombre_cliente" type="text" class="mt-1 block w-full" :value="old('nombre_cliente')" required />
                    <x-input-error :messages="$errors->clienteCreate->get('nombre_cliente')" class="mt-2" />
                </div>
                <div class="sm:col-span-2">
                    <x-input-label for="domicilio" value="Dirección" />
                    <x-text-input id="domicilio" name="domicilio" type="text" class="mt-1 block w-full" :value="old('domicilio')" />
                    <x-input-error :messages="$errors->clienteCreate->get('domicilio')" class="mt-2" />
                </div>
                <input type="hidden" id="comunidad" name="comunidad" :value="old('comunidad')" />
                <div>
                    <x-input-label for="telefono" value="Número Telefónico" />
                    <x-text-input id="telefono" name="telefono" type="text" class="mt-1 block w-full" :value="old('telefono')" />
                    <x-input-error :messages="$errors->clienteCreate->get('telefono')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="uso" value="Uso" />
                    <select id="uso" name="uso" class="form-select mt-1 w-full">
                        <option value="">Selecciona una opción</option>
                        <option value="hogar" {{ old('uso') === 'hogar' ? 'selected' : '' }}>Hogar</option>
                        <option value="empresarial" {{ old('uso') === 'empresarial' ? 'selected' : '' }}>Empresarial</option>
                        <option value="escolar" {{ old('uso') === 'escolar' ? 'selected' : '' }}>Escolar</option>
                    </select>
                    <x-input-error :messages="$errors->clienteCreate->get('uso')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="dispositivo" value="Dispositivo" />
                    <select id="dispositivo" name="dispositivo" class="form-select mt-1 w-full">
                        <option value="">Selecciona una opción</option>
                        <option value="permanencia voluntaria" {{ old('dispositivo') === 'permanencia voluntaria' ? 'selected' : '' }}>Permanencia voluntaria</option>
                        <option value="como dato" {{ old('dispositivo') === 'como dato' ? 'selected' : '' }}>Como dato</option>
                    </select>
                    <x-input-error :messages="$errors->clienteCreate->get('dispositivo')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="tecnologia" value="Tecnología" />
                    <select id="tecnologia" name="tecnologia" class="form-select mt-1 w-full" x-ref="createTecnologia" x-on:change="updateMegasCreate()">
                        <option value="">Selecciona una opción</option>
                        <option value="ina" {{ old('tecnologia') === 'ina' ? 'selected' : '' }}>INA (Inalámbrico)</option>
                        <option value="foi" {{ old('tecnologia') === 'foi' ? 'selected' : '' }}>FOI (Fibra óptica indirecta)</option>
                        <option value="fod" {{ old('tecnologia') === 'fod' ? 'selected' : '' }}>FOD (Fibra óptica directa)</option>
                    </select>
                    <x-input-error :messages="$errors->clienteCreate->get('tecnologia')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="tarifa" value="Costo de paquete" />
                    <select id="tarifa" name="tarifa" class="form-select mt-1 w-full" x-ref="createTarifa" x-on:change="updateMegasCreate()">
                        <option value="">Selecciona una opción</option>
                        <option value="300.00" {{ in_array(old('tarifa'), ['300', '300.00']) ? 'selected' : '' }}>$300</option>
                        <option value="400.00" {{ in_array(old('tarifa'), ['400', '400.00']) ? 'selected' : '' }}>$400</option>
                        <option value="500.00" {{ in_array(old('tarifa'), ['500', '500.00']) ? 'selected' : '' }}>$500</option>
                        <option value="600.00" {{ in_array(old('tarifa'), ['600', '600.00']) ? 'selected' : '' }}>$600</option>
                    </select>
                    <x-input-error :messages="$errors->clienteCreate->get('tarifa')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="megas" value="Megas" />
                    <x-text-input id="megas" name="megas" type="number" class="mt-1 block w-full" :value="old('megas')" x-ref="createMegas" x-bind:readonly="createMegasReadonly" />
                    <x-input-error :messages="$errors->clienteCreate->get('megas')" class="mt-2" />
                </div>
                <div class="sm:col-span-2" x-show="isNuevoCliente">
                    <x-input-label for="fecha_contratacion" value="Fecha del siguiente cobro" />
                    <x-text-input id="fecha_contratacion" name="fecha_contratacion" type="date" class="mt-1 block w-full" :value="old('fecha_contratacion')" />
                    <x-input-error :messages="$errors->clienteCreate->get('fecha_contratacion')" class="mt-2" />
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">Cancelar</x-secondary-button>
                <button type="submit" class="ms-3 btn btn-primary">Añadir cliente</button>
            </div>
        </form>
        </x-modal>

        <x-modal name="pagos-clientes-numeros-disponibles" maxWidth="lg" focusable>
            <div class="p-4">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Números Disponibles
                        </h3>
                        <div class="mt-1 flex items-center gap-3 text-xs">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                <span x-text="totalDisponibles"></span>&nbsp;disponibles
                            </span>
                            <span class="text-gray-600 dark:text-gray-400">
                                1000 - <span x-text="ultimoNumero"></span>
                            </span>
                        </div>
                    </div>
                    <button type="button" class="btn btn-primary btn-sm" x-on:click="$dispatch('close')">Cerrar</button>
                </div>

                <div class="bg-gray-50 dark:bg-gray-700/50 rounded p-2 mb-3">
                    <div class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400 mb-2">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Click para copiar
                    </div>
                    <!-- Búsqueda específica -->
                    <div class="flex items-center gap-2">
                        <div class="flex-1 relative">
                            <input 
                                type="number" 
                                x-model="busquedaNumero"
                                x-on:input="filtrarNumeros()"
                                placeholder="Buscar número específico..."
                                class="w-full px-2 py-1 text-xs border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                                min="1000"
                            />
                        </div>
                        <button 
                            x-show="busquedaNumero"
                            type="button"
                            x-on:click="busquedaNumero = ''; filtrarNumeros()"
                            class="px-2 py-1 text-xs bg-gray-200 hover:bg-gray-300 text-gray-700 rounded transition-colors"
                        >
                            Limpiar
                        </button>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="max-h-64 overflow-y-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700/40 sticky top-0 z-10">
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Número
                                    </th>
                                    <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Acción
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <template x-for="(numero, index) in numerosFiltrados" :key="numero">
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors" :class="index % 2 === 0 ? 'bg-white dark:bg-gray-800' : 'bg-gray-50/50 dark:bg-gray-700/20'">
                                        <td class="px-3 py-2 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                            <span class="inline-flex items-center gap-1">
                                                <svg class="w-3 h-3 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                                <span x-text="numero"></span>
                                            </span>
                                        </td>
                                        <td class="px-3 py-2 whitespace-nowrap text-sm text-center">
                                            <button 
                                                type="button" 
                                                class="inline-flex items-center gap-1 px-2 py-1 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded transition-colors"
                                                x-on:click="copiarNumero(numero)"
                                            >
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"></path>
                                                </svg>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                                
                                <tr x-show="numerosFiltrados.length === 0">
                                    <td colspan="2" class="px-3 py-8 text-center">
                                        <div class="flex flex-col items-center gap-2">
                                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            <div class="text-gray-500 dark:text-gray-400 text-sm">
                                                <p class="font-medium" x-show="busquedaNumero">No se encontró el número <span x-text="busquedaNumero"></span></p>
                                                <p class="font-medium" x-show="!busquedaNumero">No hay números disponibles</p>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Paginación -->
                <div x-show="last_page > 1" class="mt-3 flex items-center justify-between">
                    <div class="text-xs text-gray-600 dark:text-gray-400">
                        Página <span x-text="current_page"></span> de <span x-text="last_page"></span>
                    </div>
                    <div class="flex gap-1">
                        <button 
                            type="button"
                            class="px-2 py-1 text-xs bg-gray-100 hover:bg-gray-200 text-gray-700 rounded transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                            x-on:click="cargarPagina(current_page - 1)"
                            :disabled="current_page === 1"
                        >
                            Anterior
                        </button>
                        <template x-for="page in Array.from({length: Math.min(5, last_page)}, (_, i) => i + 1)" :key="page">
                            <button 
                                type="button"
                                class="px-2 py-1 text-xs rounded transition-colors"
                                :class="page === current_page ? 'bg-blue-600 text-white' : 'bg-gray-100 hover:bg-gray-200 text-gray-700'"
                                x-on:click="cargarPagina(page)"
                                x-text="page"
                            ></button>
                        </template>
                        <button 
                            type="button"
                            class="px-2 py-1 text-xs bg-gray-100 hover:bg-gray-200 text-gray-700 rounded transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                            x-on:click="cargarPagina(current_page + 1)"
                            :disabled="current_page === last_page"
                        >
                            Siguiente
                        </button>
                    </div>
                </div>
            </div>
        </x-modal>
    </div>
</x-app-layout>
