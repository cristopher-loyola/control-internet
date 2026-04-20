// Lógica centralizada para las tarjetas de pagos
window.PaymentsDashboard = {
    // Configuración de colores para cada zona
    zonaColors: {
        rosalito: 'rgba(59,130,246,0.12)',
        chivato: 'rgba(245,158,11,0.12)', 
        pozo_hondo: 'rgba(139,92,246,0.12)'
    },
    
    zonaIconColors: {
        rosalito: 'text-blue-600',
        chivato: 'text-amber-600',
        pozo_hondo: 'text-violet-600'
    },

    // Inicializar el dashboard con la configuración específica
    init(config = {}) {
        const defaultConfig = {
            endpoint: null, // URL del endpoint de métricas
            period: 'day',
            dayDate: new Date().toISOString().slice(0,10),
            weekFrom: null,
            weekTo: null,
            monthVal: null,
            validWeek: true,
            autoRefresh: true,
            refreshInterval: 120000 // 2 minutos
        };
        
        return {
            ...defaultConfig,
            ...config,
            loading: true,
            metrics: {
                // Métricas existentes
                metodos: [],
                clientes_nuevos: {day:0,week:0,month:0},
                inventario_bajo: [],
                ventas_series: {labels:[], values:[]},
                prepay_clients: [],
                cancelados_count: 0,
                cancelados: [],
                morosos: [],
                morosos_count: 0,
                baja_temporal_count: 0,
                clientes_activos: 0,
                clientes_activos_label: 'Activado',
                clientes_desactivados: 0,
                ventas_total: 0,
                ventas_count: 0,
                ingresos: 0,
                
                // Métricas de zonas
                rosalito_pagos: 0,
                rosalito_count: 0,
                rosalito_promedio: 0,
                chivato_pagos: 0,
                chivato_count: 0,
                chivato_promedio: 0,
                pozo_hondo_pagos: 0,
                pozo_hondo_count: 0,
                pozo_hondo_promedio: 0
            },
            chartMetodos: null,
            chartClientes: null,
            metodoColors: ['#16a34a','#0ea5e9','#f59e0b','#ef4444','#8b5cf6','#06b6d4','#84cc16'],
            currentRequest: null,
            lastMetodos: null,
            lastClientes: null,
            
            // Métodos
            money(v){ 
                return '$' + Number(v ?? 0).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); 
            },
            
            metodoPct(monto){
                const total = (this.metrics.metodos || []).reduce((s, m) => s + (m.monto || 0), 0);
                if(!total) return 0;
                return Math.round((monto / total) * 100);
            },
            
            exportar(fmt){
                if(!this.endpoint) return;
                
                const url = new URL(this.endpoint, window.location.origin);
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
                if(!this.endpoint) return;
                
                if(showLoader) this.loading = true;
                const url = new URL(this.endpoint, window.location.origin);
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
                        this.metrics = { ...this.metrics, ...data };
                        this.renderCharts();
                        this.loading = false;
                    })
                    .catch(err => {
                        if(err.name !== 'AbortError') {
                            console.error(err);
                        }
                        this.loading = false;
                    });
            },
            
            renderCharts(){
                // Solo renderizar gráficas si los datos cambiaron
                if(JSON.stringify(this.metrics.metodos) !== JSON.stringify(this.lastMetodos)) {
                    this.renderMetodos();
                    this.lastMetodos = this.metrics.metodos;
                }
                if(JSON.stringify(this.metrics.clientes_nuevos) !== JSON.stringify(this.lastClientes)) {
                    this.renderClientes();
                    this.lastClientes = this.metrics.clientes_nuevos;
                }
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
                const ctx = document.getElementById('chartMetodos')?.getContext('2d');
                if(!ctx) return;
                
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
                const ctx = document.getElementById('chartClientes')?.getContext('2d');
                if(!ctx) return;
                
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
            
            // Inicialización
            init(){
                this.loadMetrics(true);
                if(this.autoRefresh) {
                    setInterval(() => this.loadMetrics(false), this.refreshInterval);
                }
            }
        };
    }
};
