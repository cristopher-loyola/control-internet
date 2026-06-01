<x-app-layout title="Clientes">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            {{ __('Clientes') }}
        </h2>
    </x-slot>

    <div class="py-12" x-data="clientesAdminComponent()">
        <div class="max-w-none w-full mx-auto sm:px-4 lg:px-8">
        @if (session('import_report'))
            <div class="mb-4">
                <div x-data x-init="$nextTick(() => $dispatch('open-modal', 'admin-clientes-import-result'))"></div>
            </div>
        @endif
        <div class="flex justify-between items-center mb-4 gap-3">
                <form action="{{ route('admin.clientes.index') }}" method="GET" class="flex items-center gap-2" x-on:submit="isLoading = true">
                    <input
                        type="text"
                        name="q"
                        value="{{ request('q') }}"
                        placeholder="Buscar por nombre, número o teléfono..."
                        class="form-input rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm py-1 w-64"
                    >
                    <select name="tec" class="form-select rounded-md border-gray-300 text-sm py-1 px-2">
                        <option value="">Filtrar por rango/clave</option>
                        <option value="ina" {{ request('tec') === 'ina' ? 'selected' : '' }}>INA (1000–4200)</option>
                        <option value="foi" {{ request('tec') === 'foi' ? 'selected' : '' }}>FOI (4800–5400, 5500–5999)</option>
                        <option value="fod" {{ request('tec') === 'fod' ? 'selected' : '' }}>FOD (5401–5499, 6000–{{ $fodMax ?? 7414 }})</option>
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm">
                        Buscar
                    </button>
                    @if(request('q') || request('tec'))
                        <a href="{{ route('admin.clientes.index') }}" class="btn btn-secondary btn-sm" style="text-decoration: none;">
                            Limpiar
                        </a>
                    @endif
                </form>
                <div class="flex gap-3">
                    <a
                        x-data=""
                        x-on:click.prevent="$dispatch('open-modal', 'admin-clientes-add-confirm')"
                        href="#"
                        class="btn btn-primary"
                        style="text-decoration: none;"
                    >
                        Añadir
                    </a>
                    <button
                        type="button"
                        class="btn btn-success"
                        x-data
                        x-on:click.prevent="$dispatch('open-modal', 'admin-clientes-import')"
                        title="Importar desde Excel (CSV)"
                    >
                        Importar
                    </button>
                    <button
                        type="button"
                        class="btn btn-success"
                        style="background-color: #15803d; border-color: #15803d;"
                        x-data
                        x-on:click.prevent="$dispatch('open-modal', 'admin-clientes-import-cartera')"
                        title="Importar Cartera (Excel CSV)"
                    >
                        Importar Cartera
                    </button>
                    <button
                        type="button"
                        class="btn btn-secondary"
                        x-data
                        x-on:click.prevent="$dispatch('open-modal', 'admin-clientes-historial-buscar')"
                        title="Buscar historial por número o nombre"
                    >
                        Buscar historial
                    </button>
                    <button
                        type="button"
                        class="btn btn-info"
                        x-data
                        x-on:click.prevent="cargarNumerosDisponibles(); $dispatch('open-modal', 'admin-clientes-numeros-disponibles')"
                        title="Ver números de cliente disponibles"
                    >
                        Números disponibles
                    </button>
                    <a
                        :href="selected ? '{{ route('admin.clientes.historial', ['numero' => '__NUM__']) }}'.replace('__NUM__', form.numero_servicio ?? '') : '#'"
                        :class="selected ? 'btn btn-info' : 'btn btn-primary info'"
                        :aria-disabled="!selected"
                        title="Ver historial de este número"
                    >
                        Historial
                    </a>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <style>
                        .skel{position:relative;overflow:hidden;background-color:rgba(229,231,235,var(--tw-bg-opacity,1));border-radius:4px}
                        .skel::after{content:"";position:absolute;inset:0;transform:translateX(-100%);background:linear-gradient(90deg,transparent,rgba(255,255,255,.5),transparent);animation:skel-shimmer 1.4s infinite}
                        @keyframes skel-shimmer{100%{transform:translateX(100%)}}
                        @media (prefers-reduced-motion:reduce){.skel::after{animation:none}}
                        @media (max-width: 640px) {
                            /* Solo afectar al contenido dentro de max-w-none, no a la navbar */
                            .max-w-none .flex.justify-between.items-center.mb-4 { flex-direction: column; gap: 0.75rem; align-items: stretch; }
                            .max-w-none .flex.justify-between.items-center.mb-4 form.flex.items-center.gap-2 { flex-direction: column; align-items: stretch; }
                            .max-w-none .flex.justify-between.items-center.mb-4 form.flex.items-center.gap-2 > * { width: 100%; }
                            .max-w-none .flex.justify-between.items-center.mb-4 form.flex.items-center.gap-2 input,
                            .max-w-none .flex.justify-between.items-center.mb-4 form.flex.items-center.gap-2 select { width: 100%; max-width: none; }
                            .max-w-none .flex.justify-between.items-center.mb-4 .flex.gap-3 { flex-wrap: wrap; }
                            .max-w-none .flex.justify-between.items-center.mb-4 .flex.gap-3 > * { flex: 1 1 calc(50% - 0.375rem); min-width: 140px; }
                            .max-w-none .btn { font-size: 0.8125rem; padding: 0.5rem 0.75rem; }
                            .max-w-none .overflow-x-auto { overflow-x: auto; -webkit-overflow-scrolling: touch; }
                            .max-w-none table { font-size: 0.75rem; }
                            .max-w-none th, .max-w-none td { padding: 0.375rem 0.5rem; white-space: nowrap; }
                            .max-w-none .h-10 { height: 2.25rem; }
                            .max-w-none .px-4 { padding-left: 0.75rem; padding-right: 0.75rem; }
                            .max-w-none .py-2 { padding-top: 0.5rem; padding-bottom: 0.5rem; }
                            .max-w-none { padding-left: 0.5rem; padding-right: 0.5rem; }
                        }
                    </style>
                    @if (session('status') === 'cliente-creado')
                        <div x-data x-init="Swal.fire({ icon: 'success', title: '¡Éxito!', text: 'Cliente creado correctamente.' })"></div>
                    @endif
                    @if (session('status') === 'cliente-actualizado')
                        <div x-data x-init="Swal.fire({ icon: 'success', title: '¡Éxito!', text: 'Cliente actualizado correctamente.' })"></div>
                    @endif
                    @if (session('status') === 'cliente-eliminado')
                        <div x-data x-init="Swal.fire({ icon: 'success', title: '¡Éxito!', text: 'Cliente eliminado correctamente.' })"></div>
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
                                <tr>
                                    <td class="h-10 px-4 py-2 align-middle"><div class="skel h-4 w-12"></div></td>
                                    <td class="h-10 px-4 py-2 align-middle"><div class="skel h-4 w-24"></div></td>
                                    <td class="h-10 px-4 py-2 align-middle"><div class="skel h-4 w-28"></div></td>
                                    <td class="h-10 px-4 py-2 align-middle"><div class="skel h-4 w-24"></div></td>
                                    <td class="h-10 px-4 py-2 align-middle"><div class="skel h-4 w-12"></div></td>
                                    <td class="h-10 px-4 py-2 align-middle"><div class="skel h-4 w-10"></div></td>
                                    <td class="h-10 px-4 py-2 align-middle"><div class="skel h-4 w-16"></div></td>
                                    <td class="h-10 px-2 py-2 align-middle"><div class="skel h-6 w-24"></div></td>
                                    <td class="h-10 px-4 py-2 text-center align-middle"><div class="skel h-7 w-16 inline-block"></div></td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tbody x-show="!isLoading" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
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
                                        <td class="h-10 px-4 py-2 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100 align-middle">{{ $c->numero_servicio }}</td>
                                        <td class="h-10 px-4 py-2 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100 align-middle">
                                            <span class="inline-flex items-center gap-2 leading-none">
                                                <span
                                                    class="inline-block w-2 h-2 rounded-full {{ !is_null($c->fecha_contratacion) ? 'bg-emerald-600' : 'bg-gray-300 dark:bg-gray-600' }}"
                                                ></span>
                                                <span>{{ $c->nombre_cliente }}</span>
                                            </span>
                                        </td>
                                        <td class="h-10 px-4 py-2 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300 align-middle">{{ $c->domicilio ?? '—' }}</td>
                                        <td class="h-10 px-4 py-2 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300 align-middle">{{ $c->telefono ?? '—' }}</td>
                                        <td class="h-10 px-4 py-2 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300 align-middle">
                                            @if(!is_null($c->tecnologia))
                                                {{ strtoupper($c->tecnologia) }}
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="h-10 px-4 py-2 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300 align-middle">{{ $c->megas ?? '—' }}</td>
                                        <td class="h-10 px-4 py-2 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300 align-middle">
                                            @if(!is_null($c->tarifa))
                                                ${{ number_format((float) $c->tarifa, 2) }}
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="h-10 px-2 py-2 whitespace-normal text-sm align-middle">
                                            <div class="flex flex-col leading-tight">
                                                <span class="text-[10px] font-semibold text-gray-500 dark:text-gray-400 tracking-wide uppercase">ESTATUS</span>
                                                <span class="mt-0.5 inline-flex items-center px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200">
                                                    {{ optional($c->estatusServicio)->nombre ?? '—' }}/{{ optional($c->estado)->nombre ?? '—' }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="h-10 px-4 py-2 whitespace-nowrap text-sm text-center space-x-2 align-middle">
                                            <a
                                                href="{{ route('admin.clientes.show', $c->id) }}"
                                                class="btn btn-warning btn-sm"
                                            >
                                                Ver
                                            </a>
                                            <button
                                                type="button"
                                                title="Modificar siguiente pago"
                                                class="btn btn-sm"
                                                style="background-color: #0ea5e9; border-color: #0284c7; color: #fff;"
                                                x-on:click.stop="abrirProximoPago({
                                                    id: {{ $c->id }},
                                                    nombre: '{{ addslashes($c->nombre_cliente) }}',
                                                    numero: '{{ $c->numero_servicio }}',
                                                    tarifa: {{ (float)($c->tarifa ?? 0) }},
                                                    actualPeriodo: '{{ $c->proximo_pago ?? '' }}',
                                                    actualMonto: '{{ $c->proximo_pago_monto ?? '' }}',
                                                    url: '{{ route('admin.clientes.proximo-pago', $c->id) }}'
                                                })"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                </svg>
                                            </button>
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
                                        <td colspan="9" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                                            No hay clientes registrados.
                                        </td>
                                    </tr>
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
        </div>

        <x-modal name="admin-clientes-historial-buscar" maxWidth="sm" focusable>
        <form method="GET" action="{{ route('admin.clientes.historial.buscar') }}" class="p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">Buscar historial</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Ingresa el número de cliente o el nombre.</p>
            <div>
                <x-input-label for="historial_q" value="Número o nombre" />
                <x-text-input id="historial_q" name="q" type="text" class="mt-1 block w-full" placeholder="Ej. 122 o Pedro" required />
            </div>
            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">
                    Cancelar
                </x-secondary-button>
                <button type="submit" class="ms-3 btn btn-primary">
                    Buscar
                </button>
            </div>
        </form>
        </x-modal>

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
                    <x-input-label for="primer_pago" value="Primer pago ($)" />
                    <x-text-input id="primer_pago" name="primer_pago" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('primer_pago')" placeholder="Ingresa la cantidad del primer pago" />
                    <p class="mt-1 text-xs text-gray-500">El primer pago vence el día 7 del mes siguiente. Si no paga a tiempo, se agregará recargo de $50.</p>
                    <x-input-error :messages="$errors->clienteCreate->get('primer_pago')" class="mt-2" />
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

        <x-modal name="admin-clientes-import-cartera" maxWidth="sm" focusable>
        <form method="POST" action="{{ route('admin.clientes.import-cartera') }}" enctype="multipart/form-data" class="p-6">
            @csrf
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">Importar Cartera</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Sube un archivo CSV con las columnas de Cartera de Clientes. Se actualizarán los datos de los clientes existentes.</p>
            <div>
                <input type="file" name="file" accept=".csv,text/csv" class="block w-full text-sm">
                @if ($errors->has('file'))
                    <div class="mt-2 text-sm text-red-600">{{ $errors->first('file') }}</div>
                @endif
            </div>
            <div class="mt-5 flex justify-end gap-2">
                <button type="button" class="btn btn-secondary" x-on:click="$dispatch('close')">Cancelar</button>
                <button type="submit" class="btn btn-success" style="background-color: #15803d; border-color: #15803d;">Importar Cartera</button>
            </div>
        </form>
        </x-modal>

        <x-modal name="admin-clientes-import" maxWidth="sm" focusable>
        <form method="POST" action="{{ route('admin.clientes.import') }}" enctype="multipart/form-data" class="p-6">
            @csrf
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">Importar clientes</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Sube un archivo CSV exportado desde Excel. Requeridos: numero_servicio y nombre_cliente. Opcionales: telefono, tarifa, paquete, zona, ip, mac.</p>
            <div>
                <input type="file" name="file" accept=".csv,text/csv" class="block w-full text-sm">
                @if ($errors->has('file'))
                    <div class="mt-2 text-sm text-red-600">{{ $errors->first('file') }}</div>
                @endif
            </div>
            <div class="mt-3 flex items-center gap-2">
                <input id="telefono_nullable" type="checkbox" name="telefono_nullable" checked class="rounded">
                <label for="telefono_nullable" class="text-sm text-gray-700 dark:text-gray-300">Dejar teléfono en nulo si viene vacío</label>
            </div>
            <div class="mt-5 flex justify-end gap-2">
                <button type="button" class="btn btn-secondary" x-on:click="$dispatch('close')">Cancelar</button>
                <button type="submit" class="btn btn-success">Importar</button>
            </div>
        </form>
        </x-modal>

        @php $rep = session('import_report'); @endphp
        @if ($rep)
        <x-modal name="admin-clientes-import-result" maxWidth="lg" focusable>
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">Resultado de la importación</h3>
                <div class="text-sm text-gray-700 dark:text-gray-300 mb-3">
                    Resumen:
                    <span class="ms-2">Creados: <strong class="text-green-600">{{ $rep['created'] ?? 0 }}</strong></span>,
                    <span class="ms-2">Actualizados: <strong class="text-blue-600">{{ $rep['updated'] ?? 0 }}</strong></span>,
                    <span class="ms-2">Omitidos: <strong class="text-amber-600">{{ $rep['skipped'] ?? 0 }}</strong></span>
                </div>

                <div class="space-y-4">
                    @if (!empty($rep['skipped_details']))
                        <div class="max-h-40 overflow-auto rounded border border-amber-200 dark:border-amber-900/50 p-3 bg-amber-50 dark:bg-amber-900/10">
                            <div class="text-xs font-bold mb-2 text-amber-800 dark:text-amber-400 uppercase tracking-wider">Registros omitidos:</div>
                            <ul class="list-disc ms-5 text-xs text-amber-700 dark:text-amber-300 space-y-1">
                                @foreach ($rep['skipped_details'] as $skip)
                                    <li>{{ $skip }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if (!empty($rep['errors']))
                        <div class="max-h-64 overflow-auto rounded border border-red-200 dark:border-red-900/50 p-3 bg-red-50 dark:bg-red-900/10">
                            <div class="text-xs font-bold mb-2 text-red-800 dark:text-red-400 uppercase tracking-wider">Errores detectados (máx. 200):</div>
                            <ul class="list-disc ms-5 text-xs text-red-700 dark:text-red-300 space-y-1">
                                @foreach (array_slice($rep['errors'], 0, 200) as $err)
                                    <li>{{ $err }}</li>
                                @endforeach
                                @if (count($rep['errors']) > 200)
                                    <li class="font-bold">… y {{ count($rep['errors']) - 200 }} más</li>
                                @endif
                            </ul>
                        </div>
                    @else
                        <p class="text-sm text-gray-600 dark:text-gray-400 flex items-center gap-2">
                            <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            No se detectaron errores de procesamiento.
                        </p>
                    @endif
                </div>
                <div class="mt-4 flex justify-end">
                    <button type="button" class="btn btn-primary" x-on:click="$dispatch('close')">Cerrar</button>
                </div>
            </div>
        </x-modal>
        @endif

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
            @if ($errors->clienteEdit->any())
                <div class="mt-3 mb-4 p-3 rounded bg-red-600 text-white text-sm">
                    {{ $errors->clienteEdit->first() }}
                </div>
            @endif

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
                        <option value="escolar">Escolar</option>
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

        <x-modal name="admin-clientes-numeros-disponibles" maxWidth="lg" focusable>
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
                    <div class="flex gap-2">
                        <a :href="`{{ route('admin.clientes.numeros-disponibles.export') }}?rango_inicio=${rangoInicio || 1000}&rango_fin=${rangoFin || ultimoNumero}`" class="btn btn-success btn-sm flex items-center gap-1" title="Exportar a Excel">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Exportar
                        </a>
                        <button type="button" class="btn btn-primary btn-sm" x-on:click="$dispatch('close')">Cerrar</button>
                    </div>
                </div>

                <div class="bg-gray-50 dark:bg-gray-700/50 rounded p-2 mb-3">
                    <div class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400 mb-2">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Click para copiar
                    </div>
                    <!-- Búsqueda específica y Rango -->
                    <div class="flex flex-col gap-2">
                        <div class="flex items-center gap-2">
                            <div class="flex-1 relative">
                                <label class="block text-[10px] text-gray-500 uppercase font-bold mb-0.5">Buscar número</label>
                                <input 
                                    type="number" 
                                    x-model="busquedaNumero"
                                    x-on:input="filtrarNumeros()"
                                    placeholder="Número específico..."
                                    class="w-full px-2 py-1 text-xs border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                                    min="1000"
                                />
                            </div>
                            <div class="w-24">
                                <label class="block text-[10px] text-gray-500 uppercase font-bold mb-0.5">Inicio</label>
                                <input 
                                    type="number" 
                                    x-model="rangoInicio"
                                    x-on:input.debounce.500ms="filtrarNumeros()"
                                    class="w-full px-2 py-1 text-xs border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                                    min="1000"
                                />
                            </div>
                            <div class="w-24">
                                <label class="block text-[10px] text-gray-500 uppercase font-bold mb-0.5">Fin</label>
                                <input 
                                    type="number" 
                                    x-model="rangoFin"
                                    x-on:input.debounce.500ms="filtrarNumeros()"
                                    :placeholder="ultimoNumero"
                                    class="w-full px-2 py-1 text-xs border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                                />
                            </div>
                        </div>
                        <div x-show="busquedaNumero || rangoInicio != 1000 || (rangoFin && rangoFin != ultimoNumero)" class="flex justify-end">
                            <button 
                                type="button"
                                x-on:click="busquedaNumero = ''; rangoInicio = 1000; rangoFin = ultimoNumero; filtrarNumeros()"
                                class="px-2 py-1 text-xs bg-gray-200 hover:bg-gray-300 text-gray-700 rounded transition-colors"
                            >
                                Limpiar filtros
                            </button>
                        </div>
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
                                <template x-for="(item, index) in numerosFiltrados" :key="item.numero">
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors" :class="index % 2 === 0 ? 'bg-white dark:bg-gray-800' : 'bg-gray-50/50 dark:bg-gray-700/20'">
                                        <td class="px-3 py-2 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                            <span class="inline-flex items-center gap-1">
                                                <svg x-show="!item.esta_apartado" class="w-3 h-3 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                                <svg x-show="item.esta_apartado" class="w-3 h-3 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                                </svg>
                                                <span x-text="item.numero" :class="item.esta_apartado ? 'text-yellow-600 dark:text-yellow-400' : ''"></span>
                                                <span x-show="item.esta_apartado" class="ml-2 text-[10px] bg-yellow-100 text-yellow-800 px-1 rounded font-bold uppercase">Apartado</span>
                                            </span>
                                        </td>
                                        <td class="px-3 py-2 whitespace-nowrap text-sm text-center">
                                            <div class="flex items-center justify-center gap-2">
                                                <button 
                                                    type="button" 
                                                    class="inline-flex items-center gap-1 px-2 py-1 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded transition-colors"
                                                    x-on:click="copiarNumero(item.numero)"
                                                    title="Copiar número"
                                                >
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"></path>
                                                    </svg>
                                                </button>

                                                <button 
                                                    x-show="!item.esta_apartado"
                                                    type="button" 
                                                    class="inline-flex items-center gap-1 px-2 py-1 bg-yellow-500 hover:bg-yellow-600 text-white text-xs font-medium rounded transition-colors"
                                                    x-on:click="apartarNumero(item.numero)"
                                                    title="Apartar número"
                                                >
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                                    </svg>
                                                </button>

                                                <button 
                                                    x-show="item.esta_apartado"
                                                    type="button" 
                                                    class="inline-flex items-center gap-1 px-2 py-1 bg-red-600 hover:bg-red-700 text-white text-xs font-medium rounded transition-colors"
                                                    x-on:click="liberarNumero(item.numero)"
                                                    title="Liberar número"
                                                >
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"></path>
                                                    </svg>
                                                </button>
                                            </div>
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

        <!-- Modal: Siguiente pago — dentro del x-data para acceder al estado Alpine -->
        <div x-show="ppModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
             @keydown.escape.window="ppModal = false">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-sm mx-4" @click.stop>
                <div class="flex items-center justify-between px-5 pt-5 pb-3 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-sky-100 text-sky-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </span>
                        <span class="text-sm font-semibold text-gray-800 dark:text-gray-100 uppercase tracking-wider">Siguiente pago</span>
                    </div>
                    <button @click="ppModal = false" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="px-5 py-4 space-y-4">
                    <!-- Info cliente -->
                    <div class="flex justify-between text-sm border-b border-gray-100 dark:border-gray-700 pb-3">
                        <div>
                            <div class="font-semibold text-gray-800 dark:text-gray-100" x-text="ppCliente.nombre"></div>
                            <div class="text-xs text-gray-400">ID <span x-text="ppCliente.numero"></span> · Tarifa normal: $<span x-text="Number(ppCliente.tarifa).toFixed(2)"></span></div>
                        </div>
                    </div>

                    <!-- Mes del próximo pago -->
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">
                            Mes del próximo pago
                        </label>
                        <input type="month" x-model="ppPeriodo"
                            class="form-input w-full rounded-lg border-gray-300 dark:border-gray-600 shadow-sm focus:border-sky-400 focus:ring focus:ring-sky-200 focus:ring-opacity-50 text-sm">
                        <p class="text-xs text-gray-400 mt-1">Vacío = calculado automáticamente</p>
                    </div>

                    <!-- Monto del próximo pago -->
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">
                            Monto del próximo pago
                        </label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 font-semibold text-sm">$</span>
                            <input type="number" step="0.01" min="0" x-model="ppMonto"
                                class="form-input pl-7 w-full rounded-lg border-gray-300 dark:border-gray-600 shadow-sm focus:border-sky-400 focus:ring focus:ring-sky-200 focus:ring-opacity-50 text-sm"
                                placeholder="0.00">
                        </div>
                        <p class="text-xs text-gray-400 mt-1">Vacío = usa la tarifa normal del cliente</p>
                    </div>

                    <!-- Resumen -->
                    <div x-show="ppPeriodo || ppMonto" class="text-xs bg-sky-50 dark:bg-sky-900/20 border border-sky-200 dark:border-sky-700 rounded-lg px-3 py-2 text-sky-700 dark:text-sky-300 space-y-0.5">
                        <div x-show="ppPeriodo">
                            📅 Próximo mes: <strong x-text="(()=>{ if(!ppPeriodo) return ''; const [y,m]=ppPeriodo.split('-'); const n=['','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre']; return n[parseInt(m)]+' '+y; })()"></strong>
                        </div>
                        <div x-show="ppMonto">
                            💰 Monto configurado: <strong x-text="'$' + Number(ppMonto || 0).toFixed(2)"></strong>
                        </div>
                    </div>

                    <!-- Resultado -->
                    <div x-show="ppResultado" x-cloak
                        :class="ppResultado?.ok ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-800'"
                        class="border rounded-lg px-3 py-2 text-sm font-medium" x-text="ppResultado?.msg"></div>
                </div>
                <div class="px-5 pb-5 flex flex-col gap-2">
                    <button @click="guardarProximoPago()" :disabled="ppGuardando"
                        class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg bg-sky-600 text-white text-sm font-semibold hover:bg-sky-700 active:scale-95 transition-all duration-150 shadow disabled:opacity-50">
                        <template x-if="ppGuardando">
                            <svg class="animate-spin w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                        </template>
                        <span x-text="ppGuardando ? 'Guardando...' : 'Guardar próximo pago'"></span>
                    </button>
                    <button @click="limpiarProximoPago()" type="button"
                        class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 text-xs text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        Restablecer a valores normales
                    </button>
                    <button @click="ppModal = false"
                        class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function clientesAdminComponent() {
            return {
                selected: null,
                isLoading: false,
                numerosDisponibles: [],
                numerosFiltrados: [],
                totalDisponibles: 0,
                ultimoNumero: 0,
                current_page: 1,
                last_page: 1,
                busquedaNumero: '',
                rangoInicio: 1000,
                rangoFin: null,
                busquedaActual: '',
                isNuevoCliente: true,
                deleteId: null,
                // --- Próximo pago ---
                ppModal: false,
                ppCliente: {},
                ppPeriodo: '',
                ppMonto: '',
                ppGuardando: false,
                ppResultado: null,
                abrirProximoPago(data) {
                    this.ppCliente = data;
                    this.ppPeriodo = data.actualPeriodo || '';
                    this.ppMonto   = data.actualMonto !== '' ? data.actualMonto : data.tarifa;
                    this.ppResultado = null;
                    this.ppGuardando = false;
                    this.ppModal = true;
                },
                async guardarProximoPago() {
                    if (this.ppGuardando) return;
                    this.ppGuardando = true;
                    this.ppResultado = null;
                    const token = document.querySelector('meta[name=csrf-token]')?.getAttribute('content') || '';
                    try {
                        const r = await fetch(this.ppCliente.url, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
                            body: JSON.stringify({
                                proximo_pago: this.ppPeriodo || null,
                                proximo_pago_monto: this.ppMonto !== '' ? Number(this.ppMonto) : null,
                            })
                        });
                        const j = await r.json();
                        if (r.ok && j?.ok) {
                            this.ppResultado = { ok: true, msg: '✓ Guardado correctamente' };
                            setTimeout(() => { this.ppModal = false; }, 900);
                        } else {
                            this.ppResultado = { ok: false, msg: j?.message || 'Error al guardar' };
                        }
                    } catch (_) {
                        this.ppResultado = { ok: false, msg: 'Error de conexión' };
                    }
                    this.ppGuardando = false;
                },
                limpiarProximoPago() {
                    this.ppPeriodo = '';
                    this.ppMonto   = this.ppCliente.tarifa;
                },
                // ---
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
                        300: { FOD: 30, FOI: 20, INA: 12 },
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
                },
                cargarNumerosDisponibles() {
                    const url = new URL('{{ route("admin.clientes.numeros-disponibles") }}', window.location.origin);
                    if (this.rangoInicio) url.searchParams.set('rango_inicio', this.rangoInicio);
                    if (this.rangoFin) url.searchParams.set('rango_fin', this.rangoFin);

                    fetch(url)
                        .then(response => response.json())
                        .then(data => {
                            this.numerosDisponibles = data.numeros;
                            this.numerosFiltrados = [...data.numeros];
                            this.totalDisponibles = data.total;
                            this.ultimoNumero = data.ultimoNumero;
                            this.rangoInicio = data.rango_inicio;
                            this.rangoFin = data.rango_fin;
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
                apartarNumero(numero) {
                    Swal.fire({
                        title: '¿Deseas apartar este número?',
                        text: "El número " + numero + " quedará reservado.",
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Sí, apartar',
                        cancelButtonText: 'Cancelar'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            fetch('{{ route("admin.clientes.numeros-disponibles.apartar") }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                body: JSON.stringify({ numero: numero })
                            })
                            .then(response => response.json())
                            .then(data => {
                                this.filtrarNumeros();
                                Swal.fire('¡Apartado!', data.message, 'success');
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                Swal.fire('Error', 'No se pudo apartar el número', 'error');
                            });
                        }
                    });
                },
                liberarNumero(numero) {
                    Swal.fire({
                        title: '¿Deseas liberar este número?',
                        text: "El número " + numero + " volverá a estar disponible.",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Sí, liberar',
                        cancelButtonText: 'Cancelar'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            fetch('{{ route("admin.clientes.numeros-disponibles.liberar") }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                body: JSON.stringify({ numero: numero })
                            })
                            .then(response => response.json())
                            .then(data => {
                                this.filtrarNumeros();
                                Swal.fire('¡Liberado!', data.message, 'success');
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                Swal.fire('Error', 'No se pudo liberar el número', 'error');
                            });
                        }
                    });
                },
                cargarPagina(page) {
                    const url = new URL('{{ route("admin.clientes.numeros-disponibles") }}', window.location.origin);
                    url.searchParams.set('page', page);
                    if (this.busquedaNumero) url.searchParams.set('busqueda', this.busquedaNumero);
                    if (this.rangoInicio) url.searchParams.set('rango_inicio', this.rangoInicio);
                    if (this.rangoFin) url.searchParams.set('rango_fin', this.rangoFin);
                    
                    fetch(url)
                        .then(response => response.json())
                        .then(data => {
                            this.numerosDisponibles = data.numeros;
                            this.numerosFiltrados = [...data.numeros];
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
                    const url = new URL('{{ route("admin.clientes.numeros-disponibles") }}', window.location.origin);
                    if (this.busquedaNumero) url.searchParams.set('busqueda', this.busquedaNumero);
                    if (this.rangoInicio) url.searchParams.set('rango_inicio', this.rangoInicio);
                    if (this.rangoFin) url.searchParams.set('rango_fin', this.rangoFin);
                    url.searchParams.set('page', 1);
                    
                    fetch(url)
                        .then(response => response.json())
                        .then(data => {
                            this.numerosDisponibles = data.numeros;
                            this.numerosFiltrados = [...data.numeros];
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
            }
        }
    </script>
</x-app-layout>
