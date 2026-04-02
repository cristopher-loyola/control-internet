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
                        <a href="{{ route('pagos.clientes.index') }}" class="btn btn-primary">
                            Volver a la lista de clientes
                        </a>
                        <button
                            type="button"
                            class="btn btn-success btn-sm"
                            x-data
                            x-on:click="$dispatch('open-modal', 'pagos-clientes-show-edit')"
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
                            <dt class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Zona</dt>
                            <dd class="text-sm">{{ $cliente->zona ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">IP</dt>
                            <dd class="text-sm">
                                @if($cliente->ip && $cliente->ip !== '-')
                                    <a href="http://{{ $cliente->ip }}" target="_blank" class="text-indigo-600 hover:text-indigo-800 hover:underline">
                                        {{ $cliente->ip }}
                                    </a>
                                @else
                                    {{ $cliente->ip ?? '—' }}
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">MAC</dt>
                            <dd class="text-sm">{{ $cliente->mac ?? '—' }}</dd>
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
                            <dt class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">ESTATUS</dt>
                            <dd class="text-sm">
                                {{ optional($cliente->estatusServicio)->nombre ?? '—' }}/{{ optional($cliente->estado)->nombre ?? '—' }}
                            </dd>
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

    <x-modal name="pagos-clientes-show-edit" :show="$errors->clienteEdit->isNotEmpty()" maxWidth="lg" focusable>
        <form method="POST" action="{{ route('pagos.clientes.edit') }}" class="p-6"
              x-data="{
                editMegasReadonly: false,
                assignMegas(costo, tecnologia) {
                    let c = costo;
                    if (c && typeof c === 'string') c = parseFloat(c);
                    c = c ? Math.round(c) : null;
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
                },
                updateEstado() {
                    const estatus = this.$refs.editEstatusServicio?.value;
                    const estadoSelect = this.$refs.editEstado;
                    if (!estatus || !estadoSelect) return;
                    if (['1', '4'].includes(estatus)) {
                        estadoSelect.value = '1';
                    } else if (['2', '3'].includes(estatus)) {
                        estadoSelect.value = '2';
                    }
                }
              }"
        >
            @csrf
            <input type="hidden" name="id" value="{{ $cliente->id }}">
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-1">Editar cliente</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Actualiza los datos del cliente.</p>
            @if ($errors->clienteEdit->any())
                <div class="mt-3 mb-4 p-3 rounded bg-red-600 text-white text-sm">{{ $errors->clienteEdit->first() }}</div>
            @endif

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <x-input-label for="edit_numero_servicio" value="Número de Cliente" />
                    <x-text-input id="edit_numero_servicio" name="numero_servicio" type="number" class="mt-1 block w-full" value="{{ $cliente->numero_servicio }}" required />
                    <x-input-error :messages="$errors->clienteEdit->get('numero_servicio')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="edit_nombre_cliente" value="Nombre" />
                    <x-text-input id="edit_nombre_cliente" name="nombre_cliente" type="text" class="mt-1 block w-full" value="{{ $cliente->nombre_cliente }}" required />
                </div>
                <div class="sm:col-span-2">
                    <x-input-label for="edit_domicilio" value="Dirección" />
                    <x-text-input id="edit_domicilio" name="domicilio" type="text" class="mt-1 block w-full" value="{{ $cliente->domicilio }}" />
                </div>
                <input type="hidden" name="comunidad" value="{{ $cliente->comunidad }}">
                <div>
                    <x-input-label for="edit_telefono" value="Número Telefónico" />
                    <x-text-input id="edit_telefono" name="telefono" type="text" class="mt-1 block w-full" value="{{ $cliente->telefono }}" />
                </div>
                <div>
                    <x-input-label for="edit_ip" value="Dirección IP" />
                    <x-text-input id="edit_ip" name="ip" type="text" class="mt-1 block w-full" value="{{ $cliente->ip }}" />
                </div>
                <div>
                    <x-input-label for="edit_mac" value="MAC Address" />
                    <x-text-input id="edit_mac" name="mac" type="text" class="mt-1 block w-full" value="{{ $cliente->mac }}" />
                </div>
                <div>
                    <x-input-label for="edit_uso" value="Uso" />
                    <select id="edit_uso" name="uso" class="form-select mt-1 w-full">
                        <option value="">Selecciona una opción</option>
                        <option value="hogar" {{ $cliente->uso === 'hogar' ? 'selected' : '' }}>Hogar</option>
                        <option value="empresarial" {{ $cliente->uso === 'empresarial' ? 'selected' : '' }}>Empresarial</option>
                        <option value="escolar" {{ $cliente->uso === 'escolar' ? 'selected' : '' }}>Escolar</option>
                    </select>
                </div>
                <div>
                    <x-input-label for="edit_dispositivo" value="Dispositivo" />
                    <select id="edit_dispositivo" name="dispositivo" class="form-select mt-1 w-full">
                        <option value="">Selecciona una opción</option>
                        <option value="permanencia voluntaria" {{ $cliente->dispositivo === 'permanencia voluntaria' ? 'selected' : '' }}>Permanencia voluntaria</option>
                        <option value="como dato" {{ $cliente->dispositivo === 'como dato' ? 'selected' : '' }}>Como dato</option>
                    </select>
                </div>
                <div>
                    <x-input-label for="edit_tecnologia" value="Tecnología" />
                    <select id="edit_tecnologia" name="tecnologia" class="form-select mt-1 w-full" x-ref="editTecnologia" x-on:change="updateMegasEdit()">
                        <option value="">Selecciona una opción</option>
                        <option value="ina" {{ $cliente->tecnologia === 'ina' ? 'selected' : '' }}>INA (Inalámbrico)</option>
                        <option value="foi" {{ $cliente->tecnologia === 'foi' ? 'selected' : '' }}>FOI (Fibra óptica indirecta)</option>
                        <option value="fod" {{ $cliente->tecnologia === 'fod' ? 'selected' : '' }}>FOD (Fibra óptica directa)</option>
                    </select>
                </div>
                <div>
                    <x-input-label for="edit_tarifa" value="Costo de paquete" />
                    <select id="edit_tarifa" name="tarifa" class="form-select mt-1 w-full" x-ref="editTarifa" x-on:change="updateMegasEdit()">
                        <option value="">Selecciona una opción</option>
                        <option value="300.00" {{ (float)$cliente->tarifa === 300.00 ? 'selected' : '' }}>$300</option>
                        <option value="400.00" {{ (float)$cliente->tarifa === 400.00 ? 'selected' : '' }}>$400</option>
                        <option value="500.00" {{ (float)$cliente->tarifa === 500.00 ? 'selected' : '' }}>$500</option>
                        <option value="600.00" {{ (float)$cliente->tarifa === 600.00 ? 'selected' : '' }}>$600</option>
                    </select>
                </div>
                <div>
                    <x-input-label for="edit_megas" value="Megas" />
                    <x-text-input id="edit_megas" name="megas" type="number" class="mt-1 block w-full" x-ref="editMegas" x-bind:readonly="editMegasReadonly" value="{{ $cliente->megas }}" />
                </div>
                <div>
                    <x-input-label for="edit_estado" value="Estado" />
                    <select id="edit_estado" name="estado_id" class="form-select mt-1 w-full" x-ref="editEstado">
                        <option value="">Selecciona una opción</option>
                        <option value="1" {{ optional($cliente->estado)->id === 1 ? 'selected' : '' }}>Activado</option>
                        <option value="2" {{ optional($cliente->estado)->id === 2 ? 'selected' : '' }}>Desactivado</option>
                    </select>
                </div>
                <div>
                    <x-input-label for="edit_estatus_servicio" value="Estatus de servicio" />
                    <select id="edit_estatus_servicio" name="estatus_servicio_id" class="form-select mt-1 w-full" x-ref="editEstatusServicio" x-on:change="updateEstado()">
                        <option value="">Selecciona una opción</option>
                        <option value="1" {{ optional($cliente->estatusServicio)->id === 1 ? 'selected' : '' }}>Pagado</option>
                        <option value="2" {{ optional($cliente->estatusServicio)->id === 2 ? 'selected' : '' }}>Suspendido</option>
                        <option value="3" {{ optional($cliente->estatusServicio)->id === 3 ? 'selected' : '' }}>Cancelado</option>
                        <option value="4" {{ optional($cliente->estatusServicio)->id === 4 ? 'selected' : '' }}>Pendiente de pago</option>
                    </select>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-2">
                <x-secondary-button x-on:click="$dispatch('close')">Cancelar</x-secondary-button>
                <button type="submit" class="btn btn-success">Guardar cambios</button>
            </div>
        </form>
    </x-modal>
</x-app-layout>
