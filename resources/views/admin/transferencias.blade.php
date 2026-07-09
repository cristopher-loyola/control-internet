<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            Pagos por Transferencia
        </h2>
    </x-slot>

    <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8"
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
                            class="w-full flex items-center gap-3 px-4 py-3 hover:bg-gray-50 text-left border-b border-gray-50 last:border-0 transition-colors group">
                            {{-- Avatar --}}
                            <div class="w-9 h-9 rounded-full flex items-center justify-center text-white text-sm font-bold shrink-0"
                                 :style="'background:' + colorLetra(c.nombre_cliente)">
                                <span x-text="c.nombre_cliente.charAt(0).toUpperCase()"></span>
                            </div>
                            {{-- Info --}}
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-gray-800 truncate group-hover:text-indigo-700 transition-colors" x-text="c.nombre_cliente"></p>
                                <p class="text-xs text-gray-400" x-text="'#' + c.numero_servicio + ' · $' + formatMonto(c.tarifa) + '/mes'"></p>
                            </div>
                            {{-- Estado --}}
                            <div class="shrink-0">
                                <span x-show="c.pendiente > 0"
                                      class="inline-flex items-center gap-1 text-xs bg-red-50 text-red-600 border border-red-200 rounded-full px-2.5 py-1 font-medium">
                                    <span class="w-1.5 h-1.5 rounded-full bg-red-500 inline-block"></span>
                                    <span x-text="'Adeuda $' + formatMonto(c.pendiente)"></span>
                                </span>
                                <span x-show="!c.pendiente || c.pendiente <= 0"
                                      class="inline-flex items-center gap-1 text-xs bg-emerald-50 text-emerald-600 border border-emerald-200 rounded-full px-2.5 py-1 font-medium">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 inline-block"></span>
                                    Al corriente
                                </span>
                            </div>
                        </button>
                    </template>
                </div>
            </div>
        </div>

        {{-- Cola de pagos --}}
        <div x-show="cola.length > 0" class="mb-4" x-cloak>

            {{-- Header cola --}}
            <div class="flex items-center justify-between mb-3 px-1">
                <div class="flex items-center gap-2">
                    <span class="text-sm font-semibold text-gray-700">Clientes a registrar</span>
                    <span class="bg-gray-800 text-white text-xs font-bold rounded-full w-5 h-5 flex items-center justify-center" x-text="cola.length"></span>
                </div>
                <button type="button" @click="limpiarCola()"
                        class="text-xs text-gray-400 hover:text-red-500 transition-colors flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    Limpiar todo
                </button>
            </div>

            {{-- Cards --}}
            <div class="space-y-2">
                <template x-for="(item, idx) in cola" :key="item.numero_servicio">
                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden transition-all">

                        {{-- Resultado OK --}}
                        <template x-if="item.resultado === 'ok'">
                            <div class="px-5 py-4 flex items-center gap-4">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center text-white text-sm font-bold shrink-0"
                                     :style="'background-color:' + colorLetra(item.nombre_cliente)">
                                    <span x-text="item.nombre_cliente.charAt(0).toUpperCase()"></span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-semibold text-gray-800 truncate" x-text="item.nombre_cliente"></p>
                                    <p class="text-xs text-gray-400" x-text="'#' + item.numero_servicio"></p>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <div class="flex items-center gap-1.5 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-xl px-3 py-1.5">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        <span class="text-xs font-semibold" x-text="'Folio #' + item.folio"></span>
                                    </div>
                                    <span class="text-sm font-bold text-gray-800" x-text="'$' + formatMonto(item.monto)"></span>
                                </div>
                            </div>
                        </template>

                        {{-- Resultado error --}}
                        <template x-if="item.resultado === 'error'">
                            <div class="px-5 py-4 flex items-center gap-4">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center text-white text-sm font-bold shrink-0"
                                     :style="'background-color:' + colorLetra(item.nombre_cliente)">
                                    <span x-text="item.nombre_cliente.charAt(0).toUpperCase()"></span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-semibold text-gray-800 truncate" x-text="item.nombre_cliente"></p>
                                    <p class="text-xs text-red-500 mt-0.5" x-text="item.errorMsg"></p>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <span class="bg-red-50 text-red-600 border border-red-200 rounded-xl px-3 py-1.5 text-xs font-semibold">Error</span>
                                </div>
                            </div>
                        </template>

                        {{-- Editable --}}
                        <template x-if="!item.resultado">
                            <div class="px-5 py-4">
                                <div class="flex items-center gap-3 mb-4">
                                    <div class="w-10 h-10 rounded-full flex items-center justify-center text-white text-sm font-bold shrink-0"
                                         :style="'background-color:' + colorLetra(item.nombre_cliente)">
                                        <span x-text="item.nombre_cliente.charAt(0).toUpperCase()"></span>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-semibold text-gray-800 truncate" x-text="item.nombre_cliente"></p>
                                        <p class="text-xs text-gray-400" x-text="'#' + item.numero_servicio"></p>
                                    </div>
                                    <div x-show="item.deuda_desc" class="shrink-0">
                                        <span class="text-xs bg-amber-50 text-amber-700 border border-amber-200 rounded-full px-2.5 py-1 font-medium" x-text="item.deuda_desc"></span>
                                    </div>
                                    <button type="button"
                                            @click="cola.splice(idx, 1)"
                                            class="shrink-0 w-7 h-7 flex items-center justify-center rounded-full text-gray-300 hover:text-red-400 hover:bg-red-50 transition-all">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </div>

                                {{-- Inputs --}}
                                <div class="bg-gray-50 rounded-xl p-3 grid grid-cols-2 gap-3">
                                    {{-- Período --}}
                                    <div>
                                        <label class="text-xs font-medium text-gray-400 block mb-1.5 uppercase tracking-wide">Período</label>
                                        <input
                                            type="month"
                                            x-model="item.periodo"
                                            class="w-full bg-white border border-gray-200 rounded-lg px-3 py-2 text-sm text-gray-700 focus:outline-none focus:border-gray-400 focus:ring-1 focus:ring-gray-200 transition-all"
                                            style="color-scheme:light"
                                        />
                                    </div>

                                    {{-- Monto --}}
                                    <div>
                                        <label class="text-xs font-medium text-gray-400 block mb-1.5 uppercase tracking-wide">Monto</label>
                                        <div class="flex items-center bg-white border border-gray-200 rounded-lg overflow-hidden focus-within:border-gray-400 focus-within:ring-1 focus-within:ring-gray-200 transition-all">
                                            <span class="pl-3 pr-1 text-gray-400 text-sm font-medium">$</span>
                                            <input
                                                type="number"
                                                x-model="item.monto"
                                                @click="$event.target.select()"
                                                min="0"
                                                step="0.01"
                                                class="flex-1 py-2 pr-3 text-sm text-gray-800 font-semibold focus:outline-none [appearance:textfield]"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>
            </div>

            {{-- Footer total + acción --}}
            <div class="mt-4 bg-white rounded-2xl border border-gray-100 shadow-sm px-5 py-4 flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-400 uppercase tracking-wide font-medium">Total a registrar</p>
                    <p class="text-2xl font-bold text-gray-800 mt-0.5" x-text="'$' + formatMonto(totalCola())"></p>
                </div>
                <button
                    type="button"
                    @click="registrar()"
                    :disabled="guardando || cola.length === 0 || colaRegistrada"
                    class="px-6 py-3 bg-emerald-600 text-white text-sm font-semibold rounded-xl hover:bg-emerald-700 disabled:opacity-40 disabled:cursor-not-allowed transition-all flex items-center gap-2 shadow-sm">
                    <svg x-show="guardando" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                    </svg>
                    <svg x-show="!guardando" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
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

        {{-- Toast --}}
        <div x-show="toast.show"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-6 scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 scale-95"
             class="fixed bottom-8 right-8 z-50 max-w-xs w-full"
             style="display:none">
            <div class="rounded-2xl overflow-hidden shadow-2xl"
                 :class="toast.ok ? '' : 'bg-red-600'">

                {{-- OK toast --}}
                <template x-if="toast.ok">
                    <div class="bg-white border border-gray-100 rounded-2xl px-5 py-4 flex items-start gap-4 shadow-xl">
                        <div class="shrink-0 w-10 h-10 rounded-xl flex items-center justify-center"
                             style="background:linear-gradient(135deg,#16a34a,#22c55e)">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0 pt-0.5">
                            <p class="text-sm font-bold text-gray-900">Pago registrado</p>
                            <p class="text-xs text-gray-500 mt-0.5" x-text="toast.msg"></p>
                        </div>
                        <button @click="toast.show = false"
                                class="shrink-0 w-6 h-6 flex items-center justify-center rounded-full text-gray-300 hover:text-gray-500 hover:bg-gray-100 transition-colors mt-0.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </template>

                {{-- Error toast --}}
                <template x-if="!toast.ok">
                    <div class="bg-red-600 rounded-2xl px-5 py-4 flex items-center gap-3">
                        <div class="shrink-0 w-8 h-8 rounded-full bg-white/20 flex items-center justify-center">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </div>
                        <p class="flex-1 text-sm font-medium text-white" x-text="toast.msg"></p>
                        <button @click="toast.show = false" class="text-white/60 hover:text-white">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </template>
            </div>

            {{-- Barra de progreso --}}
            <div x-show="toast.ok" class="mt-1.5 h-0.5 rounded-full bg-green-200 overflow-hidden mx-1">
                <div class="h-full bg-green-500 rounded-full"
                     style="animation: toast-progress 4s linear forwards"
                     x-show="toast.show"></div>
            </div>
        </div>

        <style>
        @keyframes toast-progress {
            from { width: 100%; }
            to   { width: 0%; }
        }
        </style>

        {{-- Historial --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mt-6"
             x-data="historial()" x-init="cargar()"
             @transferencia-registrada.window="cargar()">

            <div class="px-6 py-4 border-b border-gray-50 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-semibold text-gray-800">Historial de transferencias</h3>
                    <p class="text-xs text-gray-400 mt-0.5" x-text="total + ' registros en total'"></p>
                </div>
                <button @click="cargar()"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-gray-100 hover:bg-gray-200 text-gray-600 hover:text-gray-800 transition-all duration-150 active:scale-95">
                    <svg class="w-3.5 h-3.5" :class="cargando && 'animate-spin'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    <span x-text="cargando ? 'Cargando…' : 'Actualizar'"></span>
                </button>
            </div>

            {{-- Tabla --}}
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-50">
                            <th class="text-left px-6 py-3 text-xs font-semibold text-gray-400 uppercase tracking-wide">Folio</th>
                            <th class="text-left px-6 py-3 text-xs font-semibold text-gray-400 uppercase tracking-wide">Cliente</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-400 uppercase tracking-wide">Período</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-400 uppercase tracking-wide">Nota</th>
                            <th class="text-right px-6 py-3 text-xs font-semibold text-gray-400 uppercase tracking-wide">Monto</th>
                            <th class="text-right px-6 py-3 text-xs font-semibold text-gray-400 uppercase tracking-wide">Fecha</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <template x-if="cargando && rows.length === 0">
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center">
                                    <svg class="animate-spin w-5 h-5 text-gray-300 mx-auto" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                                    </svg>
                                </td>
                            </tr>
                        </template>
                        <template x-if="!cargando && rows.length === 0">
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-gray-300 text-sm">Sin registros</td>
                            </tr>
                        </template>
                        <template x-for="r in rows" :key="r.id">
                            <tr class="hover:bg-gray-50/60 transition-colors" :class="r.cancelando && 'opacity-40'">
                                <td class="px-6 py-3.5">
                                    <span class="inline-flex items-center gap-1 text-xs font-mono text-gray-500 bg-gray-100 rounded-md px-2 py-0.5"
                                          x-text="'#' + r.folio"></span>
                                </td>
                                <td class="px-6 py-3.5">
                                    <p class="font-medium text-gray-800 text-sm" x-text="r.nombre_cliente"></p>
                                    <p class="text-xs text-gray-400" x-text="'#' + r.numero_servicio"></p>
                                </td>
                                <td class="px-4 py-3.5">
                                    <span class="text-xs text-gray-600 bg-blue-50 border border-blue-100 rounded-full px-2.5 py-0.5"
                                          x-text="formatPeriodo(r.periodo)"></span>
                                </td>
                                <td class="px-4 py-3.5 text-xs text-gray-500 max-w-[180px] truncate" x-text="r.nota || '—'"></td>
                                <td class="px-6 py-3.5 text-right font-semibold text-gray-800" x-text="'$' + r.total.toLocaleString('es-MX')"></td>
                                <td class="px-6 py-3.5 text-right text-xs text-gray-400" x-text="r.created_at"></td>
                                <td class="px-4 py-3.5 text-center">
                                    <button @click="cancelar(r)"
                                            :disabled="r.cancelando"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 bg-red-50 text-red-600 border border-red-200 rounded-lg text-xs font-semibold hover:bg-red-100 disabled:opacity-40 disabled:cursor-not-allowed transition-all">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                        Cancelar
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            {{-- Paginación --}}
            <div x-show="lastPage > 1" class="px-6 py-4 border-t border-gray-50 flex items-center justify-between">
                <p class="text-xs text-gray-400">
                    Página <span class="font-semibold text-gray-600" x-text="page"></span> de <span x-text="lastPage"></span>
                </p>
                <div class="flex items-center gap-2">
                    <button @click="irA(page - 1)" :disabled="page <= 1"
                            class="px-3 py-1.5 text-xs border border-gray-200 rounded-lg text-gray-600 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed transition-colors">
                        ← Anterior
                    </button>
                    <template x-for="p in paginasVisibles()" :key="p">
                        <button @click="irA(p)"
                                :class="p === page
                                    ? 'bg-gray-800 text-white border-gray-800'
                                    : 'border-gray-200 text-gray-600 hover:bg-gray-50'"
                                class="w-8 h-8 text-xs border rounded-lg transition-colors"
                                x-text="p"></button>
                    </template>
                    <button @click="irA(page + 1)" :disabled="page >= lastPage"
                            class="px-3 py-1.5 text-xs border border-gray-200 rounded-lg text-gray-600 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed transition-colors">
                        Siguiente →
                    </button>
                </div>
            </div>
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
            toast: { show: false, msg: '', ok: true, _timer: null },

            init() {},

            mostrarToast(msg, ok = true) {
                clearTimeout(this.toast._timer);
                this.toast.msg  = msg;
                this.toast.ok   = ok;
                this.toast.show = true;
                this.toast._timer = setTimeout(() => { this.toast.show = false; }, 4000);
            },

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
                    const exitosos = (data.resultados || []).filter(r => r.ok).length;
                    const errores  = (data.resultados || []).filter(r => !r.ok).length;
                    this.colaRegistrada = this.cola.every(c => c.resultado === 'ok');
                    if (exitosos > 0) {
                        const msg = exitosos === 1
                            ? `Pago registrado correctamente`
                            : `${exitosos} pagos registrados correctamente`;
                        this.mostrarToast(errores > 0 ? msg + ` (${errores} con error)` : msg, errores === 0);
                        window.dispatchEvent(new CustomEvent('transferencia-registrada'));
                    } else if (errores > 0) {
                        this.mostrarToast('No se pudo registrar ningún pago', false);
                    }
                } catch(e) {
                    this.mostrarToast('Error de conexión. Intenta de nuevo.', false);
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

            mesesOpciones() {
                const nombres = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
                const opts = [];
                const hoy = new Date();
                for (let i = -12; i <= 2; i++) {
                    const d = new Date(hoy.getFullYear(), hoy.getMonth() + i, 1);
                    const val = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0');
                    opts.push({ value: val, label: nombres[d.getMonth()] + ' ' + d.getFullYear() });
                }
                return opts;
            },
        };
    }

    function historial() {
        return {
            rows: [],
            total: 0,
            page: 1,
            lastPage: 1,
            cargando: false,

            async cargar() {
                this.cargando = true;
                try {
                    const res = await fetch(`/admin/transferencias/historial?page=${this.page}`);
                    const json = await res.json();
                    if (json.ok) {
                        this.rows     = json.data.map(r => ({ ...r, cancelando: false }));
                        this.total    = json.total;
                        this.lastPage = json.last_page;
                    }
                } catch (e) {
                    console.error('historial error', e);
                } finally {
                    this.cargando = false;
                }
            },

            irA(p) {
                if (p < 1 || p > this.lastPage) return;
                this.page = p;
                this.cargar();
            },

            paginasVisibles() {
                const pages = [];
                const start = Math.max(1, this.page - 2);
                const end   = Math.min(this.lastPage, this.page + 2);
                for (let i = start; i <= end; i++) pages.push(i);
                return pages;
            },

            formatPeriodo(p) {
                if (!p) return '—';
                const meses = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
                const [y, m] = p.split('-');
                return `${meses[parseInt(m, 10) - 1]} ${y}`;
            },

            async cancelar(r) {
                const result = await Swal.fire({
                    title: 'Cancelar pago',
                    html: `<p class="text-gray-600 text-sm">¿Confirmas cancelar el pago <strong class="text-gray-900">#${r.folio}</strong> de <strong class="text-gray-900">${r.nombre_cliente}</strong>?</p><p class="text-xs text-gray-400 mt-2">Esta acción no se puede deshacer.</p>`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, cancelar',
                    cancelButtonText: 'No, mantener',
                    confirmButtonColor: '#dc2626',
                    cancelButtonColor: '#6b7280',
                    reverseButtons: true,
                    focusCancel: true,
                    customClass: {
                        popup:         'rounded-2xl shadow-2xl border border-gray-100 px-2',
                        title:         '!text-base !font-bold !text-gray-900',
                        confirmButton: '!rounded-xl !text-sm !font-semibold !px-5 !py-2.5',
                        cancelButton:  '!rounded-xl !text-sm !font-semibold !px-5 !py-2.5',
                        actions:       '!gap-2',
                    },
                });
                if (!result.isConfirmed) return;
                r.cancelando = true;
                try {
                    const fd = new FormData();
                    fd.append('motivo', 'Cancelado desde módulo de transferencias');
                    const res = await fetch(`/admin/pagos/facturas/${r.id}/cancel`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                        },
                        body: fd,
                    });
                    const json = await res.json();
                    if (json.ok || json.success) {
                        this.rows = this.rows.filter(x => x.id !== r.id);
                        this.total = Math.max(0, this.total - 1);
                        Swal.fire({
                            toast: true,
                            position: 'bottom-end',
                            icon: 'success',
                            title: 'Pago cancelado',
                            showConfirmButton: false,
                            timer: 2500,
                            timerProgressBar: true,
                            customClass: { popup: 'rounded-xl !text-sm' },
                        });
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error', text: json.message || 'No se pudo cancelar', customClass: { popup: 'rounded-2xl' } });
                        r.cancelando = false;
                    }
                } catch (e) {
                    Swal.fire({ icon: 'error', title: 'Error de conexión', text: 'Intenta de nuevo.', customClass: { popup: 'rounded-2xl' } });
                    r.cancelando = false;
                }
            },
        };
    }
    </script>
</x-app-layout>
