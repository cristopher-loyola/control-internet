<x-app-layout title="Clientes">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            {{ __('Clientes') }}
        </h2>
    </x-slot>

    <div class="py-12" x-data="{
        selected: null,
        form: { id: null, numero_servicio: '', nombre_cliente: '', domicilio: '', comunidad: '', telefono: '', uso: '', megas: '', tarifa: '' },
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
                tarifa: row.tarifa ?? '',
            };
        },
        openEdit() {
            if (this.form.id) this.$dispatch('open-modal', 'admin-clientes-edit')
        }
    }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="flex justify-end mb-4">
                <a
                    x-data=""
                    x-on:click.prevent="$dispatch('open-modal', 'admin-clientes-add')"
                    href="#"
                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-semibold text-white bg-gradient-to-r from-red-600 to-red-800 hover:from-red-700 hover:to-red-900 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition duration-200"
                >
                    Añadir
                </a>
                <a
                    x-on:click.prevent="openEdit()"
                    href="#"
                    :class="selected ? 'ml-2 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-semibold text-white bg-gradient-to-r from-emerald-600 to-emerald-700 hover:from-emerald-700 hover:to-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition duration-200' : 'ml-2 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-semibold text-white bg-gray-400 cursor-not-allowed opacity-70'"
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

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700/40">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Número de Cliente</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nombre</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Dirección</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Comunidad</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Número Telefónico</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Uso</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Megas</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tarifa</th>
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
                                            'megas' => $c->megas,
                                            'tarifa' => $c->tarifa,
                                        ]))"
                                        :class="selected === {{ $c->id }} ? 'bg-red-50 dark:bg-gray-700/40' : ''"
                                        class="cursor-pointer"
                                    >
                                        <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">{{ $c->numero_servicio }}</td>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">{{ $c->nombre_cliente }}</td>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">{{ $c->domicilio ?? '—' }}</td>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">{{ $c->comunidad ?? '—' }}</td>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">{{ $c->telefono ?? '—' }}</td>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">{{ $c->uso ?? '—' }}</td>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">{{ $c->megas ?? '—' }}</td>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                                            @if(!is_null($c->tarifa))
                                                ${{ number_format((float) $c->tarifa, 2) }}
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm">
                                            <span class="inline-flex items-center px-2 py-1 rounded bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200">
                                                {{ $c->estado_id ?? '—' }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm">
                                            <span class="inline-flex items-center px-2 py-1 rounded bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200">
                                                {{ $c->estatus_servicio_id ?? '—' }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
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
    </div>

    <x-modal name="admin-clientes-add" :show="$errors->clienteCreate->isNotEmpty()" maxWidth="lg" focusable>
        <form method="POST" action="{{ route('admin.clientes.store') }}" class="p-6">
            @csrf
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                Añadir cliente
            </h3>
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
                <div>
                    <x-input-label for="comunidad" value="Comunidad" />
                    <x-text-input id="comunidad" name="comunidad" type="text" class="mt-1 block w-full" :value="old('comunidad')" />
                    <x-input-error :messages="$errors->clienteCreate->get('comunidad')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="telefono" value="Número Telefónico" />
                    <x-text-input id="telefono" name="telefono" type="text" class="mt-1 block w-full" :value="old('telefono')" />
                    <x-input-error :messages="$errors->clienteCreate->get('telefono')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="uso" value="Uso" />
                    <x-text-input id="uso" name="uso" type="text" class="mt-1 block w-full" :value="old('uso')" />
                    <x-input-error :messages="$errors->clienteCreate->get('uso')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="megas" value="Megas" />
                    <x-text-input id="megas" name="megas" type="number" class="mt-1 block w-full" :value="old('megas')" />
                    <x-input-error :messages="$errors->clienteCreate->get('megas')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="tarifa" value="Tarifa" />
                    <x-text-input id="tarifa" name="tarifa" type="number" step="0.01" class="mt-1 block w-full" :value="old('tarifa')" />
                    <x-input-error :messages="$errors->clienteCreate->get('tarifa')" class="mt-2" />
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

    <x-modal name="admin-clientes-edit" :show="$errors->clienteEdit->isNotEmpty()" maxWidth="lg" focusable>
        <form method="POST" action="{{ route('admin.clientes.edit') }}" class="p-6">
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
                <div>
                    <x-input-label for="edit_comunidad" value="Comunidad" />
                    <x-text-input id="edit_comunidad" name="comunidad" type="text" class="mt-1 block w-full" x-model="form.comunidad" value="{{ old('comunidad') }}" />
                    <x-input-error :messages="$errors->clienteEdit->get('comunidad')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="edit_telefono" value="Número Telefónico" />
                    <x-text-input id="edit_telefono" name="telefono" type="text" class="mt-1 block w-full" x-model="form.telefono" value="{{ old('telefono') }}" />
                    <x-input-error :messages="$errors->clienteEdit->get('telefono')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="edit_uso" value="Uso" />
                    <x-text-input id="edit_uso" name="uso" type="text" class="mt-1 block w-full" x-model="form.uso" value="{{ old('uso') }}" />
                    <x-input-error :messages="$errors->clienteEdit->get('uso')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="edit_megas" value="Megas" />
                    <x-text-input id="edit_megas" name="megas" type="number" class="mt-1 block w-full" x-model="form.megas" value="{{ old('megas') }}" />
                    <x-input-error :messages="$errors->clienteEdit->get('megas')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="edit_tarifa" value="Tarifa" />
                    <x-text-input id="edit_tarifa" name="tarifa" type="number" step="0.01" class="mt-1 block w-full" x-model="form.tarifa" value="{{ old('tarifa') }}" />
                    <x-input-error :messages="$errors->clienteEdit->get('tarifa')" class="mt-2" />
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">
                    Cancelar
                </x-secondary-button>
                <button type="submit"
                        class="ms-3 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-semibold text-white bg-gradient-to-r from-emerald-600 to-emerald-700 hover:from-emerald-700 hover:to-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition duration-200">
                    Guardar cambios
                </button>
            </div>
        </form>
    </x-modal>
</x-app-layout>
