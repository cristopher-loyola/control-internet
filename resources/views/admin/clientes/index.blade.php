<x-app-layout title="Clientes">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            {{ __('Clientes') }}
        </h2>
    </x-slot>

    <div class="py-12" x-data="{
        selected: null,
        isNuevoCliente: true,
        deleteId: null,
        createMegasReadonly: false,
        editMegasReadonly: false,
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
                300: { FOD: 30, FOI: 20, INA: 10 },
                400: { FOD: 50, FOI: 30, INA: 20 },
                500: { FOD: 70, FOI: 40, INA: 30 },
                600: { FOD: 100, FOI: 50, INA: 40 },
            };
            return matrix[c][t];
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
            if (this.form.id) this.$dispatch('open-modal', 'admin-clientes-edit')
        }
    }">
        <div class="max-w-screen-2xl mx-auto sm:px-6 lg:px-7">
        <div class="flex justify-end mb-4 gap-3">
                <a
                    x-data=""
                    x-on:click.prevent="$dispatch('open-modal', 'admin-clientes-add-confirm')"
                    href="#"
                    class="btn btn-primary"
                    style="text-decoration: none;"
                >
                    Añadir
                </a>
                <a
                    x-on:click.prevent="openEdit()"
                    href="#"
                    :class="selected ? 'btn btn-success' : 'btn btn-success disabled'"
                    :aria-disabled="!selected"
                >
                    Editar
                </a>
            </div>
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    @if (session('status') === 'cliente-creado')
                        <div class="mb-4 p-3 rounded bg-emerald-600 text-white text-sm">
                            Cliente creado correctamente.
                        </div>
                    @endif
                    @if (session('status') === 'cliente-actualizado')
                        <div class="mb-4 p-3 rounded bg-emerald-600 text-white text-sm">
                            Cliente actualizado correctamente.
                        </div>
                    @endif
                    @if (session('status') === 'cliente-eliminado')
                        <div class="mb-4 p-3 rounded bg-red-600 text-white text-sm">
                            Cliente eliminado correctamente.
                        </div>
                    @endif

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700/40">
                                <tr>
                                    <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nuevo</th>
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

                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse ($clientes as $c)
                                    <tr
                                        x-on:click="selectRow(@js([
                                            'id' => $c->id,
                                            'numero_servicio' => $c->numero_servicio,
                                            'nombre_cliente' => $c->nombre_cliente,
                                            'domicilio' => $c->domicilio,
                                            'comunidad' => $c->comunidad,
                                            'telefono' => $c->telefono,
                                            'uso' => $c->uso,
                                            'tecnologia' => $c->tecnologia,
                                            'dispositivo' => $c->dispositivo,
                                            'megas' => $c->megas,
                                            'tarifa' => $c->tarifa,
                                            'fecha_contratacion' => $c->fecha_contratacion,
                                            'estado_id' => $c->estado_id,
                                            'estatus_servicio_id' => $c->estatus_servicio_id,
                                        ]))"
                                        :class="selected === {{ $c->id }} ? 'bg-red-50 dark:bg-gray-700/40' : ''"
                                        class="cursor-pointer"
                                    >
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
                                        <td class="px-4 py-2 whitespace-nowrap text-sm text-center space-x-2">
                                            <a
                                                href="{{ route('admin.clientes.show', $c->id) }}"
                                                class="inline-flex items-center px-2 py-1 border border-gray-300 dark:border-gray-600 rounded text-xs text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700"
                                            >
                                                Ver
                                            </a>
                                            <button
                                                type="button"
                                                class="btn btn-danger btn-sm"
                                                aria-label="Eliminar cliente"
                                                x-on:click.stop="deleteId = {{ $c->id }}; $dispatch('open-modal', 'admin-clientes-delete-confirm')"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 7h12M10 11v6M14 11v6M9 7V5a1 1 0 011-1h4a1 1 0 011 1v2m-9 0v11a2 2 0 002-2h6a2 2 0 002-2V7"/>
                                                </svg>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="13" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
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

        <x-modal name="admin-clientes-add" :show="$errors->clienteCreate->isNotEmpty()" maxWidth="lg" focusable>
        <form method="POST" action="{{ route('admin.clientes.store') }}" class="p-6">
            @csrf
            <h3
                class="text-lg font-medium text-gray-900 dark:text-gray-100"
                x-text="isNuevoCliente ? 'Añadir nuevo cliente' : 'Añadir cliente'"
            ></h3>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Completa los datos del nuevo cliente.
            </p>

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
                <x-secondary-button x-on:click="$dispatch('close')">
                    Cancelar
                </x-secondary-button>
                <button type="submit"
                    class="ms-3 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-semibold text-white bg-gradient-to-r from-red-600 to-red-800 hover:from-red-700 hover:to-red-900 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition duration-200"
                >
                    Guardar
                </button>
            </div>
        </form>
        </x-modal>

        <x-modal name="admin-clientes-add-confirm" maxWidth="sm" focusable>
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

        <x-modal name="admin-clientes-edit" :show="$errors->clienteEdit->isNotEmpty()" maxWidth="lg" focusable>
        <form id="admin-clientes-edit-form" method="POST" action="{{ route('admin.clientes.edit') }}" class="p-6">
            @csrf
            <input type="hidden" name="id" x-model="form.id" value="{{ old('id') }}">
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                Editar cliente
            </h3>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Actualiza los datos del cliente seleccionado.
            </p>

            <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <x-input-label for="edit_numero_servicio" value="Número de Cliente" />
                    <x-text-input id="edit_numero_servicio" name="numero_servicio" type="number" class="mt-1 block w-full" x-model="form.numero_servicio" value="{{ old('numero_servicio') }}" required />
                    <x-input-error :messages="$errors->clienteEdit->get('numero_servicio')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="edit_nombre_cliente" value="Nombre" />
                    <x-text-input id="edit_nombre_cliente" name="nombre_cliente" type="text" class="mt-1 block w-full" x-model="form.nombre_cliente" value="{{ old('nombre_cliente') }}" required />
                    <x-input-error :messages="$errors->clienteEdit->get('nombre_cliente')" class="mt-2" />
                </div>
                <div class="sm:col-span-2">
                    <x-input-label for="edit_domicilio" value="Dirección" />
                    <x-text-input id="edit_domicilio" name="domicilio" type="text" class="mt-1 block w-full" x-model="form.domicilio" value="{{ old('domicilio') }}" />
                    <x-input-error :messages="$errors->clienteEdit->get('domicilio')" class="mt-2" />
                </div>
                <input type="hidden" id="edit_comunidad" name="comunidad" x-model="form.comunidad" value="{{ old('comunidad') }}" />
                <div>
                    <x-input-label for="edit_telefono" value="Número Telefónico" />
                    <x-text-input id="edit_telefono" name="telefono" type="text" class="mt-1 block w-full" x-model="form.telefono" value="{{ old('telefono') }}" />
                    <x-input-error :messages="$errors->clienteEdit->get('telefono')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="edit_uso" value="Uso" />
                    <select id="edit_uso" name="uso" class="form-select mt-1 w-full" x-model="form.uso">
                        <option value="">Selecciona una opción</option>
                        <option value="hogar">Hogar</option>
                        <option value="empresarial">Empresarial</option>
                    </select>
                    <x-input-error :messages="$errors->clienteEdit->get('uso')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="edit_dispositivo" value="Dispositivo" />
                    <select id="edit_dispositivo" name="dispositivo" class="form-select mt-1 w-full" x-model="form.dispositivo">
                        <option value="">Selecciona una opción</option>
                        <option value="permanencia voluntaria">Permanencia voluntaria</option>
                        <option value="como dato">Como dato</option>
                    </select>
                    <x-input-error :messages="$errors->clienteEdit->get('dispositivo')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="edit_tecnologia" value="Tecnología" />
                    <select id="edit_tecnologia" name="tecnologia" class="form-select mt-1 w-full" x-model="form.tecnologia" x-on:change="updateMegasEdit()">
                        <option value="">Selecciona una opción</option>
                        <option value="ina">INA (Inalámbrico)</option>
                        <option value="foi">FOI (Fibra óptica indirecta)</option>
                        <option value="fod">FOD (Fibra óptica directa)</option>
                    </select>
                    <x-input-error :messages="$errors->clienteEdit->get('tecnologia')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="edit_tarifa" value="Costo de paquete" />
                    <select id="edit_tarifa" name="tarifa" class="form-select mt-1 w-full" x-model="form.tarifa" x-on:change="updateMegasEdit()">
                        <option value="">Selecciona una opción</option>
                        <option value="300.00">$300</option>
                        <option value="400.00">$400</option>
                        <option value="500.00">$500</option>
                        <option value="600.00">$600</option>
                    </select>
                    <x-input-error :messages="$errors->clienteEdit->get('tarifa')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="edit_megas" value="Megas" />
                    <x-text-input id="edit_megas" name="megas" type="number" class="mt-1 block w-full" x-model="form.megas" value="{{ old('megas') }}" x-bind:readonly="editMegasReadonly" />
                    <x-input-error :messages="$errors->clienteEdit->get('megas')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="edit_estado" value="Estado" />
                    <select id="edit_estado" name="estado_id" class="form-select mt-1 w-full" x-model="form.estado_id">
                        <option value="">Selecciona una opción</option>
                        <option value="1">Activado</option>
                        <option value="2">Desactivado</option>
                    </select>
                    <x-input-error :messages="$errors->clienteEdit->get('estado_id')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="edit_estatus_servicio" value="Estatus de servicio" />
                    <select id="edit_estatus_servicio" name="estatus_servicio_id" class="form-select mt-1 w-full" x-model="form.estatus_servicio_id">
                        <option value="">Selecciona una opción</option>
                        <option value="1">Pagado</option>
                        <option value="2">Suspendido</option>
                        <option value="3">Cancelado</option>
                        <option value="4">Pendiente de pago</option>
                    </select>
                    <x-input-error :messages="$errors->clienteEdit->get('estatus_servicio_id')" class="mt-2" />
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">
                    Cancelar
                </x-secondary-button>
                <button type="button" class="ms-3 btn btn-success" x-on:click.prevent="$dispatch('open-modal', 'admin-clientes-edit-confirm')">
                    Actualizar
                </button>
            </div>
        </form>
        </x-modal>

        <x-modal name="admin-clientes-edit-confirm" maxWidth="sm" focusable>
        <div class="p-5">
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">Confirmar actualización</h3>
            <p class="text-sm text-gray-700 dark:text-gray-300 mb-4">¿Estás seguro de actualizar estos datos?</p>
            <div class="flex justify-end gap-2">
                <button type="button" class="btn btn-danger" x-on:click="$dispatch('close')">Cancelar</button>
                <button type="button" class="btn btn-primary" x-on:click="document.getElementById('admin-clientes-edit-form').submit()">Aceptar cambios</button>
            </div>
        </div>
        </x-modal>

        <x-modal name="admin-clientes-delete-confirm" maxWidth="sm" focusable>
        <div class="p-5">
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">Eliminar cliente</h3>
            <p class="text-sm text-gray-700 dark:text-gray-300 mb-4">¿Seguro que deseas eliminar este cliente?</p>
            <div class="flex justify-end gap-2">
                <button type="button" class="btn btn-secondary" x-on:click="$dispatch('close')">Cancelar</button>
                <form method="POST" :action="`{{ route('admin.clientes.destroy', ':id') }}`.replace(':id', deleteId ?? '')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        Sí, eliminar
                    </button>
                </form>
            </div>
        </div>
        </x-modal>
    </div>
</x-app-layout>
