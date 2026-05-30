<x-app-layout title="Clientes con pagos pendientes">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            Clientes con pagos pendientes ({{ $items->total() }})
        </h2>
    </x-slot>

    @php
        $isPagos = request()->is('pagos/*');
        $routePrefix = $isPagos ? 'pagos' : 'admin';
    @endphp

    <div class="py-6" x-data="transferenciaModal()">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 rounded shadow p-4">
                <form method="get" class="flex flex-wrap items-end gap-3 mb-3">
                    <div>
                        <label class="block text-xs text-gray-600 font-bold uppercase mb-1">Buscar cliente</label>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Número o nombre..." class="rounded border-gray-300 text-sm focus:ring-blue-500 focus:border-blue-500 w-64" />
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 font-bold uppercase mb-1">Mes</label>
                        <input type="month" name="month" value="{{ $month }}" class="rounded border-gray-300 text-sm focus:ring-blue-500 focus:border-blue-500" />
                    </div>
                    <button class="btn btn-primary px-6">Filtrar</button>
                    <div class="ml-auto flex gap-2">
                        <a href="{{ route($routePrefix . '.index') }}" class="btn btn-primary">Volver a inicio</a>
                    </div>
                </form>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                        <tr class="text-left text-gray-600">
                            <th class="py-2">#</th>
                            <th class="py-2">Nombre</th>
                            <th class="py-2">Mensualidad</th>
                            <th class="py-2">Recargo</th>
                            <th class="py-2">Pendiente</th>
                            <th class="py-2">Meses</th>
                            <th class="py-2">Desde</th>
                            <th class="py-2 text-center">Transferencia</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($items as $r)
                            <tr class="border-t border-gray-200">
                                <td class="py-2">
                                    <span class="inline-flex items-center gap-1">
                                        @if(($r['recargo'] ?? 0) > 0)
                                            <span title="Con recargo" class="inline-block w-2.5 h-2.5 rounded-full bg-red-600"></span>
                                        @endif
                                        {{ $r['numero'] }}
                                    </span>
                                </td>
                                <td class="py-2">{{ $r['nombre'] }}</td>
                                <td class="py-2">${{ number_format((float)$r['mensualidad'], 2) }}</td>
                                <td class="py-2">${{ number_format((float)$r['recargo'], 2) }}</td>
                                <td class="py-2 font-semibold">${{ number_format((float)$r['pendiente'], 2) }}</td>
                                <td class="py-2">{{ $r['meses_adeudo'] }}</td>
                                <td class="py-2">{{ $r['desde_periodo'] }}</td>
                                <td class="py-2 text-center">
                                    <button type="button"
                                        title="Registrar transferencia"
                                        @click="abrir({
                                            numero: '{{ $r['numero'] }}',
                                            nombre: '{{ addslashes($r['nombre']) }}',
                                            mensualidad: {{ (float)$r['mensualidad'] }},
                                            recargo: {{ (float)$r['recargo'] }},
                                            pendiente: {{ (float)$r['pendiente'] }},
                                            meses: {{ (int)$r['meses_adeudo'] }},
                                            desdeRaw: '{{ $r['desde_periodo_raw'] }}',
                                            storeUrl: '{{ $routePrefix === 'pagos' ? route('pagos.recibos.facturas.store') : route('admin.pagos.facturas.store') }}'
                                        })"
                                        class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-emerald-100 text-emerald-700 hover:bg-emerald-200 hover:text-emerald-900 transition-all duration-150 focus:outline-none focus:ring-2 focus:ring-emerald-400">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="py-3 text-gray-500">Sin clientes con pagos pendientes.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">
                    {{ $items->links() }}
                </div>
            </div>
        </div>

        <!-- Modal transferencia -->
        <div x-show="open" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
             @keydown.escape.window="open = false">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-sm mx-4"
                 @click.stop>
                <!-- Cabecera -->
                <div class="flex items-center justify-between px-5 pt-5 pb-3 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-emerald-100 text-emerald-700">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                            </svg>
                        </span>
                        <span class="text-sm font-semibold text-gray-800 dark:text-gray-100 uppercase tracking-wider">Transferencia</span>
                    </div>
                    <button @click="open = false" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Cuerpo -->
                <div class="px-5 py-4 space-y-3">
                    <!-- Info cliente -->
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Cliente</span>
                        <span class="font-semibold text-gray-800 dark:text-gray-100" x-text="cliente.nombre"></span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">ID</span>
                        <span class="font-semibold text-gray-800 dark:text-gray-100" x-text="cliente.numero"></span>
                    </div>

                    <!-- Meses a pagar -->
                    <div class="border-t border-gray-100 dark:border-gray-700 pt-3">
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                Meses a saldar por transferencia
                            </label>
                            <button type="button" @click="toggleTodos()"
                                class="text-xs text-emerald-600 hover:text-emerald-800 font-medium"
                                x-text="seleccionados.length === mesesList.length ? 'Desmarcar todos' : 'Marcar todos'"></button>
                        </div>

                        <!-- Botón agregar mes anterior -->
                        <button type="button" @click="agregarMesAnterior()"
                            class="w-full mb-1 flex items-center justify-center gap-1 py-1 rounded border border-dashed border-gray-300 dark:border-gray-600 text-xs text-gray-500 hover:border-emerald-400 hover:text-emerald-600 transition-colors">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                            </svg>
                            Agregar mes anterior
                        </button>

                        <div class="space-y-1 max-h-48 overflow-y-auto pr-1">
                            <template x-for="mes in mesesList" :key="mes.value">
                                <label class="flex items-center gap-2 p-1.5 rounded cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 text-sm"
                                    :class="mes.esAdeudo ? '' : 'opacity-70'">
                                    <input type="checkbox" :value="mes.value"
                                        x-model="seleccionados"
                                        class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-400">
                                    <span x-text="mes.label"></span>
                                    <span x-show="mes.esAdeudo" class="text-[10px] bg-red-100 text-red-700 px-1 rounded">adeudo</span>
                                    <span class="ml-auto text-gray-500 text-xs" x-text="'$' + mes.total.toFixed(2)"></span>
                                </label>
                            </template>
                        </div>
                    </div>

                    <!-- Total calculado -->
                    <div class="flex justify-between text-sm bg-emerald-50 dark:bg-emerald-900/20 rounded-lg px-3 py-2">
                        <span class="font-semibold text-gray-700 dark:text-gray-300">Total transferencia</span>
                        <span class="font-bold text-emerald-700 dark:text-emerald-400" x-text="'$' + totalSeleccionado().toFixed(2)"></span>
                    </div>

                    <!-- Quién cobró -->
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">Quién registra</label>
                        <select x-model="cobrador"
                            class="form-select w-full rounded-lg border-gray-300 dark:border-gray-600 shadow-sm focus:border-emerald-400 focus:ring focus:ring-emerald-200 focus:ring-opacity-50 text-sm">
                            <option value="">Selecciona...</option>
                            <option value="Luz">Luz</option>
                            <option value="Jaime">Jaime</option>
                            <option value="Nancy">Nancy</option>
                            <option value="Alan">Alan</option>
                            <option value="Cristopher">Cristopher</option>
                        </select>
                    </div>

                    <!-- Resultado -->
                    <div x-show="resultado" x-cloak
                        :class="resultado?.ok ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-800'"
                        class="border rounded-lg px-3 py-2 text-sm font-medium" x-text="resultado?.msg"></div>
                </div>

                <!-- Acciones -->
                <div class="px-5 pb-5 flex flex-col gap-2">
                    <button @click="registrar()"
                        :disabled="guardando || seleccionados.length === 0 || !cobrador"
                        class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700 active:scale-95 transition-all duration-150 shadow disabled:opacity-50 disabled:cursor-not-allowed">
                        <template x-if="guardando">
                            <svg class="animate-spin w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                        </template>
                        <template x-if="!guardando">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                            </svg>
                        </template>
                        <span x-text="guardando ? 'Registrando...' : 'Registrar transferencia'"></span>
                    </button>
                    <button @click="cerrar()"
                        class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        <span x-text="resultado?.ok ? 'Cerrar y recargar' : 'Cancelar'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
    function transferenciaModal() {
        const MESES = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];

        function parsePeriodo(ym) {
            const [y, m] = ym.split('-').map(Number);
            return { y, m: m - 1 }; // m es 0-based
        }

        function labelMes(y, m0) {
            return `${MESES[m0]} ${y}`;
        }

        function addMeses(y, m0, n) {
            const d = new Date(y, m0 + n, 1);
            return { y: d.getFullYear(), m: d.getMonth() };
        }

        return {
            open: false,
            guardando: false,
            resultado: null,
            seleccionados: [],
            cobrador: '',
            mesesList: [],
            cliente: {},

            abrir(data) {
                this.cliente = data;
                this.resultado = null;
                this.guardando = false;
                this.cobrador = '';

                const hoy = new Date();
                const mesActualY = hoy.getFullYear();
                const mesActualM = hoy.getMonth(); // 0-based

                // 1. Generar meses adeudados
                const adeudados = [];
                const { y: y0, m: m0 } = parsePeriodo(data.desdeRaw || '{{ now()->format("Y-m") }}');
                for (let i = 0; i < (data.meses || 1); i++) {
                    const { y, m } = addMeses(y0, m0, i);
                    const value = `${y}-${String(m + 1).padStart(2, '0')}`;
                    const recargo = (i === 0 && data.recargo > 0) ? data.recargo : 0;
                    adeudados.push({ value, label: labelMes(y, m), total: data.mensualidad + recargo,
                        esActual: (y === mesActualY && m === mesActualM), esAdeudo: true });
                }

                // 2. Prepend 5 meses extra anteriores al primer adeudo (desmarcados)
                const extras = [];
                for (let i = 5; i >= 1; i--) {
                    const { y, m } = addMeses(y0, m0, -i);
                    const value = `${y}-${String(m + 1).padStart(2, '0')}`;
                    extras.push({ value, label: labelMes(y, m), total: data.mensualidad,
                        esActual: false, esAdeudo: false });
                }

                this.mesesList = [...extras, ...adeudados];

                // Por defecto: marcar adeudados excepto mes actual
                this.seleccionados = adeudados.filter(x => !x.esActual).map(x => x.value);

                this.open = true;
            },

            agregarMesAnterior() {
                const primero = this.mesesList[0];
                if (!primero) return;
                const { y, m } = parsePeriodo(primero.value);
                const prev = addMeses(y, m, -1);
                const value = `${prev.y}-${String(prev.m + 1).padStart(2, '0')}`;
                // Evitar duplicados
                if (this.mesesList.find(x => x.value === value)) return;
                this.mesesList = [
                    { value, label: labelMes(prev.y, prev.m), total: this.cliente.mensualidad,
                      esActual: false, esAdeudo: false },
                    ...this.mesesList
                ];
            },

            toggleTodos() {
                if (this.seleccionados.length === this.mesesList.length) {
                    this.seleccionados = [];
                } else {
                    this.seleccionados = this.mesesList.map(x => x.value);
                }
            },

            totalSeleccionado() {
                return this.mesesList
                    .filter(x => this.seleccionados.includes(x.value))
                    .reduce((s, x) => s + x.total, 0);
            },

            async registrar() {
                if (this.seleccionados.length === 0 || !this.cobrador) return;
                this.guardando = true;
                this.resultado = null;
                const token = document.querySelector('meta[name=csrf-token]')?.getAttribute('content') || '';
                const url = this.cliente.storeUrl;
                const ahora = new Date();
                const fecha = ahora.toLocaleDateString('es-MX', {weekday:'long', year:'numeric', month:'long', day:'numeric'});
                const hora = ahora.toLocaleTimeString('es-MX');

                let ok = 0, err = 0;
                for (const periodo of this.seleccionados) {
                    const mes = this.mesesList.find(x => x.value === periodo);
                    try {
                        const r = await fetch(url, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': token,
                            },
                            body: JSON.stringify({
                                numero_servicio: this.cliente.numero,
                                total: mes?.total ?? this.cliente.mensualidad,
                                payload: {
                                    nombre: this.cliente.nombre,
                                    mensualidad: this.cliente.mensualidad,
                                    recargo: (mes?.total ?? 0) > this.cliente.mensualidad ? 'si' : 'no',
                                    metodo: 'Deposito a cuenta',
                                    cobro: this.cobrador,
                                    otro: 'no',
                                    prepay: 'no',
                                    pago_anterior: 0,
                                    mes_siguiente: false,
                                    periodo_override: periodo,
                                    fecha,
                                    hora,
                                }
                            })
                        });
                        const j = await r.json();
                        if (r.ok && j?.ok) { ok++; } else { err++; }
                    } catch (_) { err++; }
                }

                this.guardando = false;
                if (err === 0) {
                    this.resultado = { ok: true, msg: `✓ ${ok} pago(s) registrado(s) correctamente` };
                } else {
                    this.resultado = { ok: false, msg: `${ok} registrado(s), ${err} con error (posiblemente ya existía)` };
                }
            },

            cerrar() {
                if (this.resultado?.ok) {
                    window.location.reload();
                } else {
                    this.open = false;
                }
            },
        };
    }
    </script>
</x-app-layout>
