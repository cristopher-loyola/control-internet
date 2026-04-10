<x-app-layout title="Dashboard">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-6" x-data="adminDashboard()">
        <!-- Pantalla de Carga
        <div x-show="loading" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-[100] flex flex-col items-center justify-center bg-gray-900/60 backdrop-blur-sm">
            <div class="relative">
                <div class="w-20 h-20 border-4 border-indigo-200 border-t-indigo-600 rounded-full animate-spin"></div>
                <div class="absolute inset-0 flex items-center justify-center">
                    <svg class="w-8 h-8 text-indigo-600 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
            </div>
            <p class="mt-4 text-white font-bold tracking-widest animate-pulse uppercase text-sm">Cargando Dashboard...</p>
        </div> -->

        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="flex flex-wrap items-end gap-3">
                <div class="flex items-center gap-2">
                    <label class="text-sm font-semibold">Periodo</label>
                    <select x-model="period" @change="onPeriodChange()" class="rounded border-gray-300 text-sm">
                        <option value="day">Día</option>
                        <option value="week">Semana</option>
                        <option value="month">Mes</option>
                    </select>
                </div>

                <div x-show="period==='day'" class="flex items-center gap-2">
                    <input type="date" x-model="dayDate" @change="loadMetrics()" class="rounded border-gray-300 text-sm" />
                    <span class="text-xs text-gray-500">Formato: DD/MM/YYYY</span>
                </div>

                <div x-show="period==='week'" class="flex items-center gap-2">
                    <div class="flex flex-col">
                        <label class="text-xs text-gray-600">Fecha de inicio</label>
                        <input type="date" x-model="weekFrom" @change="onWeekChange('from')" class="rounded border-gray-300 text-sm" />
                    </div>
                    <div class="flex flex-col">
                        <label class="text-xs text-gray-600">Fecha de fin</label>
                        <input type="date" x-model="weekTo" @change="onWeekChange('to')" class="rounded border-gray-300 text-sm" />
                    </div>
                    <span class="text-xs" :class="validWeek ? 'text-gray-500' : 'text-red-600'">Debe ser una semana completa (7 días)</span>
                </div>

                <div x-show="period==='month'" class="flex items-center gap-2">
                    <input type="month" x-model="monthVal" @change="onMonthChange()" class="rounded border-gray-300 text-sm" />
                    <span class="text-xs text-gray-500">Selecciona mes/año</span>
                </div>

                <button @click="exportar('excel')" :disabled="!isValidPeriod()" class="btn btn-success disabled:opacity-60"> Exportar Excel</button>
                <button @click="exportar('pdf')" :disabled="!isValidPeriod()" class="btn btn-danger disabled:opacity-60"> Exportar PDF</button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded shadow p-4">
                    <div class="text-gray-500 text-sm">Ventas del período</div>
                    <div class="text-2xl font-bold" x-text="money(metrics.ventas_total)"></div>
                    <div class="text-xs text-gray-500" x-text="metrics.ventas_count + ' ventas'"></div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded shadow p-4">
                    <div class="text-gray-500 text-sm">Ingresos</div>
                    <div class="text-2xl font-bold" x-text="money(metrics.ingresos)"></div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-md p-5 lg:col-span-2 flex flex-col gap-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-widest text-gray-400">Distribución del período</div>
                            <div class="text-lg font-bold text-gray-800 dark:text-white mt-0.5">Métodos de pago</div>
                        </div>
                        <div class="w-9 h-9 rounded-full flex items-center justify-center" style="background:rgba(22,163,74,0.12);">
                           
                        </div>
                    </div>
                    <div class="flex flex-col sm:flex-row items-center gap-6">
                        <div class="relative flex-shrink-0" style="width:180px;height:180px;">
                            <canvas id="chartMetodos"></canvas>
                            <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                                <span class="text-xs text-gray-400">Total</span>
                                <span class="text-lg font-bold text-gray-800 dark:text-white" x-text="money(metrics.ventas_total)"></span>
                            </div>
                        </div>
                        <div class="flex-1 w-full space-y-3">
                            <template x-for="(m, i) in (metrics.metodos || [])" :key="i">
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        <span class="flex-shrink-0 w-2.5 h-2.5 rounded-full" :style="'background:' + metodoColors[i % metodoColors.length]"></span>
                                        <span class="flex-1 text-sm text-gray-700 dark:text-gray-300 truncate" x-text="m.metodo || 'N/D'"></span>
                                        <span class="text-sm font-semibold text-gray-800 dark:text-white" x-text="money(m.monto)"></span>
                                        <span class="text-xs text-gray-400 w-8 text-right" x-text="metodoPct(m.monto) + '%'"></span>
                                    </div>
                                    <div class="h-1.5 w-full bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                                        <div class="h-full rounded-full transition-all duration-700"
                                             :style="'width:' + metodoPct(m.monto) + '%;background:' + metodoColors[i % metodoColors.length]"></div>
                                    </div>
                                </div>
                            </template>
                            <div x-show="!metrics.metodos || metrics.metodos.length === 0" class="text-sm text-gray-400">Sin datos en el período</div>
                        </div>
                    </div>
                </div>

                {{-- Clientes nuevos --}}
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-md p-5 flex flex-col gap-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-lg font-bold text-gray-800 dark:text-white mt-0.5">Clientes nuevos</div>
                        </div>

                    </div>
                    <div class="grid grid-cols-3 gap-2">
                        <div class="rounded-xl p-2.5 text-center" style="background:rgba(14,165,233,0.08);">
                            <div class="text-xs font-semibold uppercase tracking-widest mb-1" style="color:#0ea5e9;">Hoy</div>
                            <div class="text-2xl font-bold text-gray-800 dark:text-white" x-text="metrics.clientes_nuevos?.day ?? 0"></div>
                        </div>
                        <div class="rounded-xl p-2.5 text-center" style="background:rgba(22,163,74,0.08);">
                            <div class="text-xs font-semibold uppercase tracking-widest mb-1" style="color:#16a34a;">Semana</div>
                            <div class="text-2xl font-bold text-gray-800 dark:text-white" x-text="metrics.clientes_nuevos?.week ?? 0"></div>
                        </div>
                        <div class="rounded-xl p-2.5 text-center" style="background:rgba(245,158,11,0.08);">
                            <div class="text-xs font-semibold uppercase tracking-widest mb-1" style="color:#f59e0b;">Mes</div>
                            <div class="text-2xl font-bold text-gray-800 dark:text-white" x-text="metrics.clientes_nuevos?.month ?? 0"></div>
                        </div>
                    </div>
                    <div class="flex-1">
                        <canvas id="chartClientes" height="130"></canvas>
                    </div>
                </div>

            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Pagos adelantados --}}
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-md p-5 flex flex-col gap-4">
                    <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-700 pb-3">
                        <div>
                            <div class="text-lg font-bold text-gray-800 dark:text-white mt-0.5">Pagos por adelantado</div>
                        </div>
                        <div class="flex items-center gap-2">
                            <a href="{{ route('admin.dashboard.prepay.index') }}" class="btn btn-primary btn-sm">Ver todos</a>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left text-gray-400 border-b border-gray-100 dark:border-gray-700">
                                    <th class="pb-2 font-medium">Cliente</th>
                                    <th class="pb-2 font-medium text-center">Desde</th>
                                    <th class="pb-2 font-medium text-center">Hasta</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50 dark:divide-gray-700/50">
                                <template x-for="p in (metrics.prepay_clients || [])" :key="p.numero">
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                        <td class="py-3">
                                            <div class="font-bold text-gray-800 dark:text-white" x-text="p.numero"></div>
                                            <div class="text-xs text-gray-500 truncate max-w-[150px]" x-text="p.nombre"></div>
                                        </td>
                                        <td class="py-3 text-center">
                                            <span class="px-2 py-0.5 rounded-full bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400 text-[11px] font-bold" x-text="p.desde"></span>
                                        </td>
                                        <td class="py-3 text-center">
                                            <div class="flex flex-col items-center gap-1">
                                                <span class="px-2 py-0.5 rounded-full bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400 text-[11px] font-bold" x-text="p.hasta"></span>
                                                <template x-if="p.expira_pronto">
                                                    <span class="px-2 py-0.5 rounded-full bg-yellow-50 dark:bg-yellow-900/20 text-yellow-700 dark:text-yellow-300 text-[11px] font-bold"
                                                          x-text="(p.dias_para_vencer ?? 0) === 0 ? 'Vence hoy' : `Vence en ${(p.dias_para_vencer ?? 0)}d`"></span>
                                                </template>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="!metrics.prepay_clients || metrics.prepay_clients.length === 0">
                                    <td colspan="3" class="py-10 text-center text-gray-400 italic">No hay pagos adelantados registrados</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Cancelaciones de suscripción - Total sin filtro de período --}}
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-md p-5 flex flex-col gap-4">
                    <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-700 pb-3">
                        <div>
                            <div class="text-lg font-bold text-gray-800 dark:text-white mt-0.5">Cancelaciones</div>
                        </div>
                        <a href="{{ route('admin.dashboard.cancelados') }}" class="btn btn-primary btn-sm">Ver todos</a>
                    </div>
                    <div class="text-3xl font-bold text-gray-800 dark:text-white" x-text="metrics.cancelados_count ?? 0"></div>
                    <div class="text-xs text-gray-500 mt-1">Total de cancelaciones</div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead><tr class="text-left text-gray-400 border-b border-gray-100 dark:border-gray-700"><th class="pb-2 font-medium">Número</th><th class="pb-2 font-medium">Nombre</th><th class="pb-2 font-medium">Fecha</th></tr></thead>
                            <tbody class="divide-y divide-gray-50 dark:divide-gray-700/50">
                                <template x-for="c in (metrics.cancelados || [])" :key="c.id">
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                        <td class="py-2" x-text="c.numero_servicio"></td>
                                        <td class="py-2" x-text="c.nombre_cliente"></td>
                                        <td class="py-2 text-gray-500" x-text="(c.updated_at ?? '').replace('T',' ').slice(0,10)"></td>
                                    </tr>
                                </template>
                                <tr x-show="!metrics.cancelados || metrics.cancelados.length === 0"><td colspan="3" class="py-5 text-center text-gray-400 italic">Sin cancelaciones</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="bg-white dark:bg-gray-800 rounded shadow p-4">
                    <div class="flex items-center justify-between">
                        <div class="font-semibold mb-2">Clientes activos</div>
                        <a href="{{ route('admin.dashboard.activos.pagados') }}" class="btn btn-primary">Ver todos</a>
                    </div>
                    <div class="text-3xl font-bold" x-text="metrics.clientes_activos ?? 0"></div>
                    <div class="text-sm text-gray-500 mt-1">
                        Estado: <span class="font-semibold" x-text="metrics.clientes_activos_label ?? 'Activado'"></span>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded shadow p-4">
                    <div class="flex items-center justify-between">
                        <div class="font-semibold mb-2">Clientes desactivados</div>
                        <a href="{{ route('admin.dashboard.desactivados') }}" class="btn btn-primary">Ver todos</a>
                    </div>
                    <div class="text-3xl font-bold" x-text="metrics.clientes_desactivados ?? 0"></div>
                    <div class="text-sm text-gray-500 mt-1">Estado: Desactivado/Inactivo/Suspendido</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded shadow p-4">
                    <div class="flex items-center justify-between">
                        <div class="font-semibold mb-2">Baja temporal</div>
                        <a href="{{ route('admin.dashboard.baja-temporal') }}" class="btn btn-primary">Ver todos</a>
                    </div>
                    <div class="text-3xl font-bold" x-text="metrics.baja_temporal_count ?? 0"></div>
                    <div class="text-sm text-gray-500 mt-1">Estatus: Baja temporal</div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded shadow p-4 mt-4">
                <div class="flex items-center justify-between">
                    <div class="font-semibold">Clientes con pagos pendientes</div>
                    <a href="{{ route('admin.dashboard.morosos') }}" class="btn btn-primary">Ver todos</a>
                </div>
                <div class="mt-2 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead><tr class="text-left text-gray-600">
                            <th class="py-1">Número</th><th class="py-1">Nombre</th><th class="py-1">Pendiente</th><th class="py-1">Vencimiento</th><th class="py-1">Meses</th>
                        </tr></thead>
                        <tbody>
                            <template x-for="m in (metrics.morosos || [])" :key="m.numero">
                                <tr class="border-t border-gray-200">
                                    <td class="py-1">
                                        <span class="inline-flex items-center gap-1">
                                            <span class="inline-block w-2.5 h-2.5 rounded-full" :class="(m.recargo||0)>0 ? 'bg-red-600' : 'bg-yellow-500'"></span>
                                            <span x-text="m.numero"></span>
                                        </span>
                                    </td>
                                    <td class="py-1" x-text="m.nombre"></td>
                                    <td class="py-1 font-semibold" x-text="money(m.pendiente)"></td>
                                    <td class="py-1" x-text="m.vencimiento"></td>
                                    <td class="py-1" x-text="m.meses_adeudo"></td>
                                </tr>
                            </template>
                            <tr x-show="!metrics.morosos || metrics.morosos.length===0">
                                <td colspan="5" class="py-2 text-gray-500">Sin clientes con pagos pendientes</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <style>
        @media (max-width: 640px) {
            .flex-wrap { flex-wrap: wrap; }
            .items-end { align-items: stretch !important; flex-direction: column; }
            .items-end > * { width: 100% !important; }
            .gap-3 { gap: 0.5rem; }
            .gap-2 { gap: 0.5rem; }
            .btn { font-size: 0.8125rem; padding: 0.5rem 0.75rem; width: 100%; }
            .btn-success, .btn-danger { width: calc(50% - 0.25rem); }
            .btn-sm { font-size: 0.75rem; padding: 0.25rem 0.5rem; }
            .text-sm { font-size: 0.8125rem; }
            .text-xs { font-size: 0.6875rem; }
            .text-2xl { font-size: 1.25rem; }
            .text-3xl { font-size: 1.5rem; }
            select, input[type="date"], input[type="month"] {
                font-size: 0.875rem;
                width: 100% !important;
                max-width: none;
                min-height: 2.25rem;
            }
            .flex.items-center.gap-2 {
                flex-direction: column;
                align-items: stretch;
                gap: 0.375rem;
            }
            .flex.items-center.gap-2 > * {
                width: 100%;
            }
            .flex.items-center.gap-2 .text-xs {
                display: none;
            }
            .grid-cols-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
            .rounded-2xl { border-radius: 0.75rem; }
            .p-5 { padding: 0.75rem; }
            .p-4 { padding: 0.75rem; }
            .py-6 { padding-top: 0.75rem; padding-bottom: 0.75rem; }
            .max-w-7xl { padding-left: 0.75rem; padding-right: 0.75rem; }
            .space-y-6 > * + * { margin-top: 0.75rem; }
            .gap-4 { gap: 0.5rem; }
            .gap-6 { gap: 0.75rem; }
            .sm\:flex-row { flex-direction: column; }
            canvas { max-width: 160px !important; max-height: 160px !important; }
            #chartClientes { max-height: 100px !important; }
            .relative.flex-shrink-0 { width: 160px !important; height: 160px !important; }
            .absolute.inset-0.flex.flex-col.items-center.justify-center span.text-lg { font-size: 0.875rem; }
            .absolute.inset-0.flex.flex-col.items-center.justify-center span.text-xs { font-size: 0.625rem; }
            .min-w-full { min-width: 100%; }
            .overflow-x-auto { overflow-x: auto; -webkit-overflow-scrolling: touch; }
            table { font-size: 0.75rem; }
            th, td { padding: 0.375rem 0.25rem; white-space: nowrap; }
            .truncate { max-width: 160px; }
            .lg\:col-span-2 { grid-column: span 1 / span 1; }
            .lg\:col-span-3 { grid-column: span 1 / span 1; }
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
    function adminDashboard(){
        return {
            loading: true,
            period: 'day',
            dayDate: new Date().toISOString().slice(0,10),
            weekFrom: null,
            weekTo: null,
            monthVal: null,
            validWeek: true,
            metrics: { metodos: [], clientes_nuevos: {day:0,week:0,month:0}, inventario_bajo: [], ventas_series: {labels:[], values:[]}, prepay_clients: [], cancelados_count: 0, cancelados: [], morosos: [], morosos_count: 0, baja_temporal_count: 0 },
            chartMetodos: null,
            chartClientes: null,
            metodoColors: ['#16a34a','#0ea5e9','#f59e0b','#ef4444','#8b5cf6','#06b6d4','#84cc16'],
            currentRequest: null,
            lastMetodos: null,
            lastClientes: null,
            init(){
                this.loadMetrics(true);
                // Reducir frecuencia de recarga a 2 minutos para mejorar rendimiento
                setInterval(() => this.loadMetrics(false), 120000);
            },
            money(v){ return '$' + Number(v ?? 0).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
            metodoPct(monto){
                const total = (this.metrics.metodos || []).reduce((s, m) => s + (m.monto || 0), 0);
                if(!total) return 0;
                return Math.round((monto / total) * 100);
            },
            exportar(fmt){
                const url = new URL('{{ route('admin.dashboard.export') }}', window.location.origin);
                url.searchParams.set('period', this.period);
                url.searchParams.set('format', fmt);
                if(this.period==='day'){
                    url.searchParams.set('date', this.dayDate);
                } else if(this.period==='week'){
                    url.searchParams.set('from', this.weekFrom);
                    url.searchParams.set('to', this.weekTo);
                } else if(this.period==='month'){
                    url.searchParams.set('month', this.monthVal);
                }
                window.location.href = url.toString();
            },
            loadMetrics(showLoader = true){
                if(showLoader) this.loading = true;
                const url = new URL('{{ route('admin.dashboard.metrics') }}', window.location.origin);
                url.searchParams.set('period', this.period);
                if(this.period==='day'){
                    url.searchParams.set('date', this.dayDate);
                } else if(this.period==='week'){
                    url.searchParams.set('date', this.weekFrom || new Date().toISOString().slice(0,10));
                } else if(this.period==='month'){
                    const d = (this.monthVal ? this.monthVal+'-01' : new Date().toISOString().slice(0,7)+'-01');
                    url.searchParams.set('date', d);
                }
                
                // Abort controller para cancelar peticiones anteriores
                if(this.currentRequest) this.currentRequest.abort();
                this.currentRequest = new AbortController();
                
                fetch(url, { signal: this.currentRequest.signal })
                    .then(r => r.json())
                    .then(data => {
                        if(!data.ok) {
                            this.loading = false;
                            return;
                        }
                        this.metrics = data;
                        // Solo renderizar gráficas si los datos cambiaron
                        if(JSON.stringify(data.metodos) !== JSON.stringify(this.lastMetodos)) {
                            this.renderMetodos();
                            this.lastMetodos = data.metodos;
                        }
                        if(JSON.stringify(data.clientes_nuevos) !== JSON.stringify(this.lastClientes)) {
                            this.renderClientes();
                            this.lastClientes = data.clientes_nuevos;
                        }
                        this.loading = false;
                    })
                    .catch(err => {
                        if(err.name !== 'AbortError') {
                            console.error(err);
                        }
                        this.loading = false;
                    });
            },
            onPeriodChange(){
                if(this.period==='week'){
                    const t = new Date();
                    const day = t.getDay() || 7;
                    const start = new Date(t); start.setDate(t.getDate() - (day-1));
                    const end = new Date(start); end.setDate(start.getDate() + 6);
                    this.weekFrom = start.toISOString().slice(0,10);
                    this.weekTo = end.toISOString().slice(0,10);
                    this.validWeek = true;
                } else if(this.period==='month'){
                    this.monthVal = new Date().toISOString().slice(0,7);
                }
                this.loadMetrics();
            },
            onWeekChange(which){
                if(this.weekFrom && (!this.weekTo || which==='from')){
                    const f = new Date(this.weekFrom);
                    const e = new Date(f); e.setDate(f.getDate()+6);
                    this.weekTo = e.toISOString().slice(0,10);
                }
                if(this.weekFrom && this.weekTo){
                    const f = new Date(this.weekFrom);
                    const t = new Date(this.weekTo);
                    const diff = Math.round((t - f)/(1000*60*60*24));
                    this.validWeek = (diff === 6);
                } else {
                    this.validWeek = false;
                }
            },
            onMonthChange(){
                this.loadMetrics();
            },
            isValidPeriod(){
                if(this.period==='day'){ return !!this.dayDate; }
                if(this.period==='week'){ return !!this.weekFrom && !!this.weekTo && this.validWeek; }
                if(this.period==='month'){ return !!this.monthVal; }
                return true;
            },
            renderMetodos(){
                const labels = (this.metrics.metodos || []).map(m => m.metodo || 'N/D');
                const values = (this.metrics.metodos || []).map(m => m.monto || 0);
                const ctx = document.getElementById('chartMetodos').getContext('2d');
                if(this.chartMetodos){ this.chartMetodos.destroy(); }
                this.chartMetodos = new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels,
                        datasets: [{
                            data: values,
                            backgroundColor: this.metodoColors,
                            borderWidth: 3,
                            borderColor: '#ffffff',
                            hoverOffset: 6,
                        }]
                    },
                    options: {
                        cutout: '72%',
                        plugins: { legend: { display: false } },
                        animation: { animateRotate: true, duration: 800 }
                    }
                });
            },
            renderClientes(){
                const labels = ['Hoy', 'Semana', 'Mes'];
                const values = [
                    this.metrics.clientes_nuevos?.day ?? 0,
                    this.metrics.clientes_nuevos?.week ?? 0,
                    this.metrics.clientes_nuevos?.month ?? 0,
                ];
                const ctx = document.getElementById('chartClientes').getContext('2d');
                if(this.chartClientes){ this.chartClientes.destroy(); }
                this.chartClientes = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels,
                        datasets: [{
                            data: values,
                            backgroundColor: [
                                'rgba(14,165,233,0.85)',
                                'rgba(22,163,74,0.85)',
                                'rgba(245,158,11,0.85)',
                            ],
                            borderRadius: 8,
                            borderSkipped: false,
                        }]
                    },
                    options: {
                        plugins: { legend: { display: false } },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: { precision: 0, color: '#9ca3af', font: { size: 11 } },
                                grid: { color: 'rgba(156,163,175,0.15)' },
                            },
                            x: {
                                ticks: { color: '#9ca3af', font: { size: 11 } },
                                grid: { display: false },
                            }
                        },
                        animation: { duration: 700, easing: 'easeOutQuart' }
                    }
                });
            },
            renderVentas(){
                const labels = this.metrics.ventas_series?.labels || [];
                const values = this.metrics.ventas_series?.values || [];
                const ctx = document.getElementById('chartVentas').getContext('2d');
                if(this.chartVentas){ this.chartVentas.destroy(); }
                this.chartVentas = new Chart(ctx, {
                    type: 'line',
                    data: { labels, datasets: [{ data: values, borderColor: '#22c55e', backgroundColor: 'rgba(34,197,94,0.2)', tension: 0.3, fill: true }] },
                    options: { plugins: { legend: { display:false } }, scales: { y: { beginAtZero:true } } }
                });
            },
        }
    }
    </script>
</x-app-layout>
