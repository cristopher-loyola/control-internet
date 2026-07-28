<x-app-layout title="Pagos por adelantado">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            Pagos por adelantado
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 rounded shadow p-4" x-data="prepayPage()">
                <div class="flex items-center justify-between mb-4">
                    <div class="text-sm text-gray-600 dark:text-gray-400">Total: {{ $clients->total() }}</div>
                    <a href="{{ auth()->user()->role === 'pagos' ? route('pagos.index') : route('admin.index') }}" class="btn btn-primary btn-sm">Regresar al dashboard</a>
                </div>

                <div class="flex flex-col sm:flex-row sm:items-center gap-2 mb-4">
                    <div class="flex-1">
                        <input
                            type="text"
                            class="w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 text-sm"
                            placeholder="Número o nombre de cliente"
                            x-model="q"
                            @input="onInput()"
                        >
                        <div class="mt-1 text-xs text-red-500" x-show="error" x-text="error"></div>
                    </div>
                    <button type="button" class="btn btn-secondary btn-sm" @click="clearSearch()" x-show="q">Limpiar</button>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500 border-b border-gray-100 dark:border-gray-700">
                                <th class="py-2 font-medium">Número</th>
                                <th class="py-2 font-medium">Nombre</th>
                                <th class="py-2 font-medium text-center">Meses</th>
                                <th class="py-2 font-medium text-center">Desde</th>
                                <th class="py-2 font-medium text-center">Hasta</th>
                                <th class="py-2 font-medium text-center">Estado</th>
                                <th class="py-2 font-medium text-right">Total Pagado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-gray-700/50" x-show="!isSearching">
                            @forelse($clients as $p)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors {{ ($p->vencido ?? false) ? 'bg-red-50/60 dark:bg-red-900/10' : '' }}">
                                    <td class="py-3 font-bold text-gray-800 dark:text-white">{{ $p->numero }}</td>
                                    <td class="py-3 text-gray-700 dark:text-gray-300 {{ ($p->vencido ?? false) ? 'text-red-700 dark:text-red-300' : '' }}">{{ $p->nombre }}</td>
                                    <td class="py-3 text-center">
                                        <span class="px-2 py-0.5 rounded bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 text-xs font-bold">
                                            {{ $p->meses }} meses
                                        </span>
                                    </td>
                                    <td class="py-3 text-center">
                                        <span class="px-2 py-0.5 rounded-full bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400 text-[11px] font-bold">
                                            {{ $p->desde }}
                                        </span>
                                    </td>
                                    <td class="py-3 text-center">
                                        <span class="px-2 py-0.5 rounded-full {{ ($p->vencido ?? false) ? 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300' : 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400' }} text-[11px] font-bold">
                                            {{ $p->hasta }}
                                        </span>
                                    </td>
                                    <td class="py-3 text-center">
                                        <span class="px-2 py-0.5 rounded-full {{ ($p->vencido ?? false) ? 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300' : 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300' }} text-[11px] font-bold">
                                            {{ $p->estado ?? (($p->vencido ?? false) ? 'Vencido' : 'Activo') }}
                                        </span>
                                        @if (($p->expira_pronto ?? false) && !($p->vencido ?? false))
                                            <div class="mt-1">
                                                <span class="px-2 py-0.5 rounded-full bg-yellow-50 dark:bg-yellow-900/20 text-yellow-700 dark:text-yellow-300 text-[11px] font-bold">
                                                    Vence pronto
                                                </span>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="py-3 text-right font-semibold text-gray-800 dark:text-white">
                                        ${{ number_format($p->monto, 2) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="py-10 text-center text-gray-400 italic">No hay pagos adelantados registrados</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tbody class="divide-y divide-gray-50 dark:divide-gray-700/50" x-show="isSearching">
                            <template x-for="r in results" :key="r.numero">
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors" :class="r.vencido ? 'bg-red-50/60 dark:bg-red-900/10' : ''">
                                    <td class="py-3 font-bold text-gray-800 dark:text-white" x-text="r.numero"></td>
                                    <td class="py-3 text-gray-700 dark:text-gray-300" :class="r.vencido ? 'text-red-700 dark:text-red-300' : ''" x-text="r.nombre"></td>
                                    <td class="py-3 text-center">
                                        <span class="px-2 py-0.5 rounded bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 text-xs font-bold" x-text="`${r.meses} meses`"></span>
                                    </td>
                                    <td class="py-3 text-center">
                                        <span class="px-2 py-0.5 rounded-full bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400 text-[11px] font-bold" x-text="r.desde"></span>
                                    </td>
                                    <td class="py-3 text-center">
                                        <span class="px-2 py-0.5 rounded-full text-[11px] font-bold"
                                              :class="r.vencido ? 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300' : 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400'"
                                              x-text="r.hasta"></span>
                                    </td>
                                    <td class="py-3 text-center">
                                        <span class="px-2 py-0.5 rounded-full text-[11px] font-bold"
                                              :class="r.vencido ? 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300' : 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300'"
                                              x-text="r.estado"></span>
                                        <template x-if="r.expira_pronto && !r.vencido">
                                            <div class="mt-1">
                                                <span class="px-2 py-0.5 rounded-full bg-yellow-50 dark:bg-yellow-900/20 text-yellow-700 dark:text-yellow-300 text-[11px] font-bold">
                                                    Vence pronto
                                                </span>
                                            </div>
                                        </template>
                                    </td>
                                    <td class="py-3 text-right font-semibold text-gray-800 dark:text-white" x-text="money(r.monto)"></td>
                                </tr>
                            </template>
                            <tr x-show="searched && results.length === 0">
                                <td colspan="7" class="py-10 text-center text-gray-400 italic">Sin resultados</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-4" x-show="!isSearching">
                    {{ $clients->links() }}
                </div>
            </div>
        </div>
    </div>
    <script>
        function prepayPage(){
            return {
                q: '',
                timer: null,
                results: [],
                error: '',
                searched: false,
                get isSearching(){ return String(this.q||'').trim().length >= 3; },
                money(v){ try{ return new Intl.NumberFormat('es-MX',{style:'currency',currency:'MXN'}).format(Number(v||0)); }catch(_){ return '$'+Number(v||0).toFixed(2); } },
                clearSearch(){ this.q=''; this.results=[]; this.error=''; this.searched=false; if(this.timer){ clearTimeout(this.timer); this.timer=null; } },
                onInput(){
                    this.error='';
                    const val = String(this.q||'').trim();
                    if(val.length < 3){ this.results=[]; this.searched=false; return; }
                    const isNum = /^\d+$/.test(val);
                    if(isNum){
                        if(val.length < 3 || val.length > 10){ this.error='Longitud inválida'; this.results=[]; this.searched=true; return; }
                    }else{
                        if(val.length > 80){ this.error='Longitud inválida'; this.results=[]; this.searched=true; return; }
                        const ok = /^[a-zA-Z0-9ÁÉÍÓÚÜÑáéíóúüñ\s.'-]+$/.test(val);
                        if(!ok){ this.error='Caracteres inválidos'; this.results=[]; this.searched=true; return; }
                    }
                    if(this.timer){ clearTimeout(this.timer); }
                    this.timer = setTimeout(()=>this.search(val), 250);
                },
                async search(val){
                    try{
                        const url = new URL('{{ auth()->user()->role === 'pagos' ? route('pagos.dashboard.prepay.search') : route('admin.dashboard.prepay.search') }}', window.location.origin);
                        url.searchParams.set('q', val);
                        const r = await fetch(url.toString(), { headers:{'Accept':'application/json'} });
                        const j = await r.json();
                        if(!r.ok || !j?.ok){
                            this.error = j?.message || 'Error de búsqueda';
                            this.results = [];
                            this.searched = true;
                            return;
                        }
                        this.results = Array.isArray(j.data) ? j.data : [];
                        this.searched = true;
                    }catch(_){
                        this.error = 'Error de conexión';
                        this.results = [];
                        this.searched = true;
                    }
                }
            }
        }
    </script>
</x-app-layout>
