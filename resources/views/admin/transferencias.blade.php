<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Pagos por Transferencia
        </h2>
    </x-slot>

    <div class="py-6 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8"
         x-data="transferencias()"
         x-init="init()">

        {{-- Búsqueda --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-4">
            <p class="text-sm text-gray-500 mb-3">Busca el cliente que pagó por transferencia y agrégalo a la lista.</p>
            <div class="relative">
                <input
                    type="text"
                    x-model="busqueda"
                    @input.debounce.300ms="buscar()"
                    @keydown.escape="resultados = []"
                    placeholder="Nombre o número de servicio…"
                    class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-gray-400 shadow-sm"
                />
                <div x-show="cargandoBusqueda" class="absolute right-3 top-3.5">
                    <svg class="animate-spin w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                    </svg>
                </div>

                {{-- Dropdown resultados --}}
                <div x-show="resultados.length > 0"
                     @click.outside="resultados = []"
                     class="absolute z-20 mt-1 w-full bg-white border border-gray-100 rounded-xl shadow-lg overflow-hidden">
                    <template x-for="c in resultados" :key="c.numero_servicio">
                        <button
                            type="button"
                            @click="agregar(c)"
                            class="w-full flex items-center justify-between px-4 py-3 hover:bg-gray-50 text-left border-b border-gray-50 last:border-0">
                            <div>
                                <p class="text-sm font-medium text-gray-800" x-text="c.nombre_cliente"></p>
                                <p class="text-xs text-gray-400" x-text="'#' + c.numero_servicio + ' · $' + formatMonto(c.tarifa) + '/mes'"></p>
                            </div>
                            <div class="text-right ml-4 shrink-0">
                                <span x-show="c.pendiente > 0"
                                      class="inline-block text-xs bg-amber-50 text-amber-700 border border-amber-200 rounded-full px-2 py-0.5"
                                      x-text="'Adeuda $' + formatMonto(c.pendiente)"></span>
                                <span x-show="!c.pendiente || c.pendiente <= 0"
                                      class="inline-block text-xs bg-gray-50 text-gray-400 border border-gray-200 rounded-full px-2 py-0.5">
                                    Al corriente
                                </span>
                            </div>
                        </button>
                    </template>
                </div>
            </div>
        </div>

        {{-- Cola de pagos --}}
        <div x-show="cola.length > 0" class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-4">
            <div class="px-6 py-4 border-b border-gray-50 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-700">
                    Clientes a registrar
                    <span class="ml-1 bg-gray-100 text-gray-500 text-xs rounded-full px-2 py-0.5" x-text="cola.length"></span>
                </h3>
                <button type="button" @click="limpiarCola()" class="text-xs text-gray-400 hover:text-red-500">Limpiar todo</button>
            </div>

            <div class="divide-y divide-gray-50">
                <template x-for="(item, idx) in cola" :key="item.numero_servicio">
                    <div class="px-6 py-4 flex items-start gap-3">
                        {{-- Avatar --}}
                        <div class="w-9 h-9 rounded-full flex items-center justify-center text-white text-sm font-semibold shrink-0 mt-1"
                             :style="'background-color:' + colorLetra(item.nombre_cliente)">
                            <span x-text="item.nombre_cliente.charAt(0).toUpperCase()"></span>
                        </div>

                        {{-- Info --}}
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-800 truncate" x-text="item.nombre_cliente"></p>
                            <p class="text-xs text-gray-400" x-text="'#' + item.numero_servicio"></p>
                            <p x-show="item.deuda_desc" class="text-xs text-amber-600 mt-0.5" x-text="item.deuda_desc"></p>
                        </div>

                        {{-- Período --}}
                        <div class="shrink-0 w-36">
                            <label class="text-xs text-gray-400 block mb-1">Período</label>
                            <input
                                type="month"
                                x-model="item.periodo"
                                :disabled="!!item.resultado"
                                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none shadow-sm disabled:bg-gray-50 disabled:text-gray-400"
                            />
                        </div>

                        {{-- Monto --}}
                        <div class="shrink-0 w-28">
                            <label class="text-xs text-gray-400 block mb-1">Monto</label>
                            <div class="flex items-center border border-gray-200 rounded-lg overflow-hidden shadow-sm">
                                <span class="px-2 text-gray-400 text-sm">$</span>
                                <input
                                    type="number"
                                    x-model="item.monto"
                                    @click="$event.target.select()"
                                    :disabled="!!item.resultado"
                                    min="0"
                                    step="0.01"
                                    class="flex-1 py-2 pr-2 text-sm text-gray-800 focus:outline-none [appearance:textfield] w-full disabled:bg-gray-50 disabled:text-gray-400"
                                />
                            </div>
                        </div>

                        {{-- Nota --}}
                        <div class="shrink-0 w-36">
                            <label class="text-xs text-gray-400 block mb-1">Nota (opcional)</label>
                            <input
                                type="text"
                                x-model="item.nota"
                                :disabled="!!item.resultado"
                                placeholder="Ej. ref. 1234"
                                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none shadow-sm disabled:bg-gray-50 disabled:text-gray-400"
                            />
                        </div>

                        {{-- Estado resultado --}}
                        <div class="shrink-0 flex items-center gap-2 mt-6">
                            <template x-if="item.resultado === 'ok'">
                                <div class="flex items-center gap-1 text-emerald-600 text-xs font-medium">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <span x-text="'Folio #' + item.folio"></span>
                                </div>
                            </template>
                            <template x-if="item.resultado === 'error'">
                                <span class="text-red-500 text-xs" x-text="item.errorMsg"></span>
                            </template>
                            <template x-if="!item.resultado">
                                <button type="button"
                                        @click="cola.splice(idx, 1)"
                                        class="text-gray-300 hover:text-red-400 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </template>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Footer --}}
            <div class="px-6 py-4 bg-gray-50 flex items-center justify-between">
                <p class="text-sm text-gray-500">
                    Total:
                    <span class="font-semibold text-gray-800" x-text="'$' + formatMonto(totalCola())"></span>
                </p>
                <button
                    type="button"
                    @click="registrar()"
                    :disabled="guardando || cola.length === 0 || colaRegistrada"
                    class="px-5 py-2.5 bg-emerald-600 text-white text-sm font-semibold rounded-xl hover:bg-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex items-center gap-2">
                    <svg x-show="guardando" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                    </svg>
                    <span x-text="guardando ? 'Registrando…' : 'Registrar ' + cola.length + ' pago' + (cola.length !== 1 ? 's' : '')"></span>
                </button>
            </div>
        </div>

        {{-- Estado vacío --}}
        <div x-show="cola.length === 0" class="text-center py-16 text-gray-300">
            <svg class="w-12 h-12 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
            </svg>
            <p class="text-sm">Busca un cliente para comenzar</p>
        </div>
    </div>

    <script>
    function transferencias() {
        return {
            busqueda: '',
            resultados: [],
            cola: [],
            cargandoBusqueda: false,
            guardando: false,
            colaRegistrada: false,

            init() {},

            // Default inteligente: si estamos en los primeros 10 días del mes, sugerir mes anterior
            periodoDefault() {
                const hoy = new Date();
                if (hoy.getDate() <= 10) {
                    const d = new Date(hoy.getFullYear(), hoy.getMonth() - 1, 1);
                    return d.toISOString().slice(0, 7);
                }
                return hoy.toISOString().slice(0, 7);
            },

            async buscar() {
                const q = this.busqueda.trim();
                if (q.length < 2) { this.resultados = []; return; }
                this.cargandoBusqueda = true;
                try {
                    const r = await fetch(`/admin/transferencias/buscar?q=${encodeURIComponent(q)}`);
                    const data = await r.json();
                    const enCola = new Set(this.cola.map(c => c.numero_servicio));
                    this.resultados = (data.data || []).filter(c => !enCola.has(c.numero_servicio));
                } catch(e) {
                    this.resultados = [];
                } finally {
                    this.cargandoBusqueda = false;
                }
            },

            async agregar(c) {
                this.resultados = [];
                this.busqueda = '';

                let montoDefault = parseFloat(c.tarifa) || 0;
                let deudaDesc = '';
                let periodoDefault = this.periodoDefault();

                try {
                    const r = await fetch(`/admin/pagos/deuda?numero=${c.numero_servicio}`);
                    const data = await r.json();
                    if (data.ok) {
                        if (data.pendiente > 0) {
                            montoDefault = data.pendiente;
                            deudaDesc = data.descripcion || '';
                        }
                        // Usar el período más antiguo que debe como default
                        if (data.desde_periodo) {
                            periodoDefault = data.desde_periodo;
                        }
                    }
                } catch(e) {}

                this.cola.push({
                    numero_servicio: c.numero_servicio,
                    nombre_cliente:  c.nombre_cliente,
                    tarifa:          c.tarifa,
                    monto:           montoDefault,
                    periodo:         periodoDefault,
                    nota:            '',
                    deuda_desc:      deudaDesc,
                    resultado:       null,
                    folio:           null,
                    errorMsg:        '',
                });
            },

            async registrar() {
                if (this.guardando) return;
                this.guardando = true;

                const pagos = this.cola
                    .filter(c => !c.resultado)
                    .map(c => ({
                        numero_servicio: c.numero_servicio,
                        monto:           parseFloat(c.monto) || 0,
                        periodo:         c.periodo,
                        nota:            c.nota,
                    }));

                try {
                    const r = await fetch('/admin/transferencias', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        },
                        body: JSON.stringify({ pagos }),
                    });
                    const data = await r.json();

                    for (const res of (data.resultados || [])) {
                        const item = this.cola.find(c => c.numero_servicio === res.numero_servicio);
                        if (!item) continue;
                        if (res.ok) {
                            item.resultado = 'ok';
                            item.folio = res.folio;
                        } else {
                            item.resultado = 'error';
                            item.errorMsg = res.mensaje || 'Error';
                        }
                    }
                    this.colaRegistrada = this.cola.every(c => c.resultado === 'ok');
                } catch(e) {
                    alert('Error de conexión. Intenta de nuevo.');
                } finally {
                    this.guardando = false;
                }
            },

            limpiarCola() {
                this.cola = [];
                this.colaRegistrada = false;
            },

            totalCola() {
                return this.cola.reduce((s, c) => s + (parseFloat(c.monto) || 0), 0);
            },

            formatMonto(v) {
                return parseFloat(v || 0).toLocaleString('es-MX', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
            },

            colorLetra(nombre) {
                const colores = ['#6366f1','#8b5cf6','#ec4899','#14b8a6','#f59e0b','#3b82f6','#10b981'];
                let h = 0;
                for (let i = 0; i < nombre.length; i++) h = (h * 31 + nombre.charCodeAt(i)) % colores.length;
                return colores[Math.abs(h)];
            },
        };
    }
    </script>
</x-app-layout>
