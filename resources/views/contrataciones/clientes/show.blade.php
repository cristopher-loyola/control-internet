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
                    <div class="mb-4 flex items-center justify-between">
                        <a href="{{ route('contrataciones.clientes.index') }}" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">
                            ← Volver a la lista de clientes
                        </a>
                        <button
                            type="button"
                            class="btn btn-success"
                            x-data
                            x-on:click.prevent="$dispatch('open-modal', 'contrataciones-clientes-edit')"
                            title="Editar cliente"
                        >
                            Editar
                        </button>
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

    <x-modal name="contrataciones-clientes-edit" :show="$errors->clienteEdit->isNotEmpty()" maxWidth="lg" focusable>
        <form id="contrataciones-clientes-edit-form" method="POST" action="{{ route('contrataciones.clientes.edit') }}" class="p-6"
              x-data="{
                editMegasReadonly: false,
                assignMegas(costo, tecnologia) {
                    const parseCost = (val) => { if (!val) return null; if (typeof val === 'string') val = parseFloat(val); return Math.round(val); };
                    const c = parseCost(costo);
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
                    const costo = this.$refs.editTarifa?.value;
                    const tec = this.$refs.editTecnologia?.value;
                    const m = this.assignMegas(costo, tec);
                    if (m !== null && this.$refs.editMegas) {
                        this.$refs.editMegas.value = m;
                        this.editMegasReadonly = true;
                    } else {
                        this.editMegasReadonly = false;
                    }
                }
              }"
        >
            @csrf
            <input type="hidden" name="id" value="{{ $cliente->id }}">
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                Editar cliente
            </h3>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Actualiza los datos del cliente.
            </p>

            <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <x-input-label for="edit_numero_servicio" value="Número de Cliente" />
                    <x-text-input id="edit_numero_servicio" name="numero_servicio" type="number" class="mt-1 block w-full" value="{{ old('numero_servicio', $cliente->numero_servicio) }}" required />
                    <x-input-error :messages="$errors->clienteEdit->get('numero_servicio')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="edit_nombre_cliente" value="Nombre" />
                    <x-text-input id="edit_nombre_cliente" name="nombre_cliente" type="text" class="mt-1 block w-full" value="{{ old('nombre_cliente', $cliente->nombre_cliente) }}" required />
                    <x-input-error :messages="$errors->clienteEdit->get('nombre_cliente')" class="mt-2" />
                </div>
                <div class="sm:col-span-2">
                    <x-input-label for="edit_domicilio" value="Dirección" />
                    <x-text-input id="edit_domicilio" name="domicilio" type="text" class="mt-1 block w-full" value="{{ old('domicilio', $cliente->domicilio) }}" />
                    <x-input-error :messages="$errors->clienteEdit->get('domicilio')" class="mt-2" />
                </div>
                <input type="hidden" id="edit_comunidad" name="comunidad" value="{{ old('comunidad', $cliente->comunidad) }}" />
                <div>
                    <x-input-label for="edit_telefono" value="Número Telefónico" />
                    <x-text-input id="edit_telefono" name="telefono" type="text" class="mt-1 block w-full" value="{{ old('telefono', $cliente->telefono) }}" />
                    <x-input-error :messages="$errors->clienteEdit->get('telefono')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="edit_uso" value="Uso" />
                    <select id="edit_uso" name="uso" class="form-select mt-1 w-full">
                        <option value="">Selecciona una opción</option>
                        <option value="hogar" {{ old('uso', $cliente->uso) === 'hogar' ? 'selected' : '' }}>Hogar</option>
                        <option value="empresarial" {{ old('uso', $cliente->uso) === 'empresarial' ? 'selected' : '' }}>Empresarial</option>
                        <option value="escolar" {{ old('uso', $cliente->uso) === 'escolar' ? 'selected' : '' }}>Escolar</option>
                    </select>
                    <x-input-error :messages="$errors->clienteEdit->get('uso')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="edit_dispositivo" value="Dispositivo" />
                    <select id="edit_dispositivo" name="dispositivo" class="form-select mt-1 w-full">
                        <option value="">Selecciona una opción</option>
                        <option value="permanencia voluntaria" {{ old('dispositivo', $cliente->dispositivo) === 'permanencia voluntaria' ? 'selected' : '' }}>Permanencia voluntaria</option>
                        <option value="como dato" {{ old('dispositivo', $cliente->dispositivo) === 'como dato' ? 'selected' : '' }}>Como dato</option>
                    </select>
                    <x-input-error :messages="$errors->clienteEdit->get('dispositivo')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="edit_tecnologia" value="Tecnología" />
                    <select id="edit_tecnologia" name="tecnologia" class="form-select mt-1 w-full" x-ref="editTecnologia" x-on:change="updateMegasEdit()">
                        <option value="">Selecciona una opción</option>
                        <option value="ina" {{ old('tecnologia', $cliente->tecnologia) === 'ina' ? 'selected' : '' }}>INA (Inalámbrico)</option>
                        <option value="foi" {{ old('tecnologia', $cliente->tecnologia) === 'foi' ? 'selected' : '' }}>FOI (Fibra óptica indirecta)</option>
                        <option value="fod" {{ old('tecnologia', $cliente->tecnologia) === 'fod' ? 'selected' : '' }}>FOD (Fibra óptica directa)</option>
                    </select>
                    <x-input-error :messages="$errors->clienteEdit->get('tecnologia')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="edit_tarifa" value="Costo de paquete" />
                    <select id="edit_tarifa" name="tarifa" class="form-select mt-1 w-full" x-ref="editTarifa" x-on:change="updateMegasEdit()">
                        <option value="">Selecciona una opción</option>
                        <option value="300.00" {{ in_array(old('tarifa', (string) $cliente->tarifa), ['300', '300.00']) ? 'selected' : '' }}>$300</option>
                        <option value="400.00" {{ in_array(old('tarifa', (string) $cliente->tarifa), ['400', '400.00']) ? 'selected' : '' }}>$400</option>
                        <option value="500.00" {{ in_array(old('tarifa', (string) $cliente->tarifa), ['500', '500.00']) ? 'selected' : '' }}>$500</option>
                        <option value="600.00" {{ in_array(old('tarifa', (string) $cliente->tarifa), ['600', '600.00']) ? 'selected' : '' }}>$600</option>
                    </select>
                    <x-input-error :messages="$errors->clienteEdit->get('tarifa')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="edit_megas" value="Megas" />
                    <x-text-input id="edit_megas" name="megas" type="number" class="mt-1 block w-full" x-ref="editMegas" value="{{ old('megas', $cliente->megas) }}" x-bind:readonly="editMegasReadonly" />
                    <x-input-error :messages="$errors->clienteEdit->get('megas')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="edit_estado" value="Estado" />
                    <select id="edit_estado" name="estado_id" class="form-select mt-1 w-full">
                        <option value="">Selecciona una opción</option>
                        <option value="1" {{ old('estado_id', $cliente->estado_id) == 1 ? 'selected' : '' }}>Activado</option>
                        <option value="2" {{ old('estado_id', $cliente->estado_id) == 2 ? 'selected' : '' }}>Desactivado</option>
                    </select>
                    <x-input-error :messages="$errors->clienteEdit->get('estado_id')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="edit_estatus_servicio" value="Estatus de servicio" />
                    <select id="edit_estatus_servicio" name="estatus_servicio_id" class="form-select mt-1 w-full">
                        <option value="">Selecciona una opción</option>
                        <option value="1" {{ old('estatus_servicio_id', $cliente->estatus_servicio_id) == 1 ? 'selected' : '' }}>Pagado</option>
                        <option value="2" {{ old('estatus_servicio_id', $cliente->estatus_servicio_id) == 2 ? 'selected' : '' }}>Suspendido</option>
                        <option value="3" {{ old('estatus_servicio_id', $cliente->estatus_servicio_id) == 3 ? 'selected' : '' }}>Cancelado</option>
                        <option value="4" {{ old('estatus_servicio_id', $cliente->estatus_servicio_id) == 4 ? 'selected' : '' }}>Pendiente de pago</option>
                    </select>
                    <x-input-error :messages="$errors->clienteEdit->get('estatus_servicio_id')" class="mt-2" />
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">
                    Cancelar
                </x-secondary-button>
                <button type="submit" class="ms-3 btn btn-success">
                    Actualizar
                </button>
            </div>
        </form>
    </x-modal>
</x-app-layout>
