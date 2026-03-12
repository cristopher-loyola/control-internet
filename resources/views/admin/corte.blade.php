<x-app-layout title="Corte de caja">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            Corte de caja
        </h2>
    </x-slot>

    <div class="py-6" x-data="corteCaja()">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Toolbar --}}
            <div class="flex items-center gap-3">
                <input type="date" x-model="date" @change="load()"
                       class="rounded border-gray-300 text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500" />
                <span class="text-xs text-gray-400" x-show="lastUpdate" x-text="'Actualizado a las ' + lastUpdate"></span>
            </div>

            {{-- Metric cards --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 border-l-4 border-green-500">
                    <div class="text-gray-500 dark:text-gray-400 text-sm">Ingresos</div>
                    <div class="text-2xl font-bold text-green-600 dark:text-green-400 tabular-nums" x-text="money(metrics.ingresos)"></div>
                    <div class="text-xs text-gray-400 mt-0.5" x-text="(metrics.ventas?.length ?? 0) + ' venta(s)'"></div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 border-l-4 border-red-400">
                    <div class="text-gray-500 dark:text-gray-400 text-sm">Recibos cancelados</div>
                    <div class="text-2xl font-bold text-red-500 dark:text-red-400 tabular-nums"
                         x-text="(metrics.cancelados?.length ?? 0)"></div>
                    <div class="text-xs text-gray-400 mt-0.5"
                         x-text="money(metrics.cancelados?.reduce((s,c) => s + Number(c.monto ?? 0), 0) ?? 0) + ' en cancelaciones'"></div>
                </div>
            </div>

            {{-- Ventas --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2">
                        <span class="font-semibold text-gray-700 dark:text-gray-200">Ventas</span>
                        <span class="text-xs bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300 px-2 py-0.5 rounded-full font-medium"
                              x-text="(metrics.ventas?.length ?? 0) + ' registro(s)'"></span>
                    </div>
                    <button @click="exportCSV()"
                            x-show="metrics.ventas?.length > 0"
                            class="text-xs text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 hover:underline flex items-center gap-1 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/>
                        </svg>
                        Exportar Excel
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide border-b border-gray-200 dark:border-gray-700">
                                <th class="pb-2 pr-4 font-semibold">Folio</th>
                                <th class="pb-2 pr-4 font-semibold">Fecha</th>
                                <th class="pb-2 pr-4 font-semibold">Monto</th>
                                <th class="pb-2 pr-4 font-semibold">Cliente</th>
                                <th class="pb-2 pr-4 font-semibold">Nº Servicio</th>
                                <th class="pb-2 font-semibold">Método</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="v in metrics.ventas" :key="v.folio">
                                <tr class="border-t border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                    <td class="py-2 pr-4 font-mono text-xs text-indigo-600 dark:text-indigo-400 font-semibold" x-text="v.folio"></td>
                                    <td class="py-2 pr-4 text-gray-500 dark:text-gray-400" x-text="v.fecha"></td>
                                    <td class="py-2 pr-4 font-semibold text-green-600 dark:text-green-400 tabular-nums" x-text="money(v.monto)"></td>
                                    <td class="py-2 pr-4 text-gray-700 dark:text-gray-300" x-text="v.cliente ?? '—'"></td>
                                    <td class="py-2 pr-4 text-gray-500 dark:text-gray-400" x-text="v.numero_servicio ?? '—'"></td>
                                    <td class="py-2">
                                        <span class="text-xs px-2 py-0.5 rounded-full font-medium"
                                              :class="{
                                                'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300':     v.metodo?.toLowerCase().includes('tarjeta') || v.metodo?.toLowerCase().includes('card'),
                                                'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300': v.metodo?.toLowerCase().includes('efectivo') || v.metodo?.toLowerCase().includes('cash'),
                                                'bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-300': v.metodo?.toLowerCase().includes('transfer'),
                                                'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400': !v.metodo
                                              }"
                                              x-text="v.metodo ?? 'N/A'">
                                        </span>
                                    </td>
                                </tr>
                            </template>

                            {{-- Fila total --}}
                            <template x-if="metrics.ventas?.length > 0">
                                <tr class="border-t-2 border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/40 font-semibold">
                                    <td colspan="2" class="py-2 pr-4 text-xs text-gray-400 uppercase tracking-wide">Total</td>
                                    <td class="py-2 pr-4 text-green-600 dark:text-green-400 tabular-nums"
                                        x-text="money(metrics.ventas.reduce((s,v) => s + Number(v.monto ?? 0), 0))"></td>
                                    <td colspan="3"></td>
                                </tr>
                            </template>

                            <tr x-show="!metrics.ventas || metrics.ventas.length === 0">
                                <td colspan="6" class="py-6 text-center text-gray-400 text-sm">Sin ventas registradas para esta fecha</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Compras --}}
            <template x-if="metrics.compras && metrics.compras.length > 0">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="font-semibold text-gray-700 dark:text-gray-200">Compras</span>
                        <span class="text-xs bg-red-100 text-red-600 dark:bg-red-900 dark:text-red-300 px-2 py-0.5 rounded-full font-medium"
                              x-text="metrics.compras.length + ' registro(s)'"></span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide border-b border-gray-200 dark:border-gray-700">
                                    <th class="pb-2 pr-4 font-semibold">Folio</th>
                                    <th class="pb-2 pr-4 font-semibold">Fecha</th>
                                    <th class="pb-2 pr-4 font-semibold">Monto</th>
                                    <th class="pb-2 pr-4 font-semibold">Proveedor</th>
                                    <th class="pb-2 font-semibold">Concepto</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(c, i) in metrics.compras" :key="i">
                                    <tr class="border-t border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                        <td class="py-2 pr-4 font-mono text-xs text-red-500 font-semibold" x-text="c.folio ?? '—'"></td>
                                        <td class="py-2 pr-4 text-gray-500 dark:text-gray-400" x-text="c.fecha ?? '—'"></td>
                                        <td class="py-2 pr-4 font-semibold text-red-500 tabular-nums" x-text="money(c.monto)"></td>
                                        <td class="py-2 pr-4 text-gray-700 dark:text-gray-300" x-text="c.proveedor ?? '—'"></td>
                                        <td class="py-2 text-gray-500 dark:text-gray-400" x-text="c.concepto ?? '—'"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </template>

            {{-- Gastos --}}
            <template x-if="metrics.gastos && metrics.gastos.length > 0">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="font-semibold text-gray-700 dark:text-gray-200">Gastos</span>
                        <span class="text-xs bg-red-100 text-red-600 dark:bg-red-900 dark:text-red-300 px-2 py-0.5 rounded-full font-medium"
                              x-text="metrics.gastos.length + ' registro(s)'"></span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide border-b border-gray-200 dark:border-gray-700">
                                    <th class="pb-2 pr-4 font-semibold">Fecha</th>
                                    <th class="pb-2 pr-4 font-semibold">Monto</th>
                                    <th class="pb-2 pr-4 font-semibold">Concepto</th>
                                    <th class="pb-2 font-semibold">Responsable</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(g, i) in metrics.gastos" :key="i">
                                    <tr class="border-t border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                        <td class="py-2 pr-4 text-gray-500 dark:text-gray-400" x-text="g.fecha ?? '—'"></td>
                                        <td class="py-2 pr-4 font-semibold text-red-500 tabular-nums" x-text="money(g.monto)"></td>
                                        <td class="py-2 pr-4 text-gray-700 dark:text-gray-300" x-text="g.concepto ?? '—'"></td>
                                        <td class="py-2 text-gray-500 dark:text-gray-400" x-text="g.responsable ?? '—'"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </template>

            {{-- Devoluciones --}}
            <template x-if="metrics.devoluciones && metrics.devoluciones.length > 0">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="font-semibold text-gray-700 dark:text-gray-200">Devoluciones</span>
                        <span class="text-xs bg-orange-100 text-orange-600 dark:bg-orange-900 dark:text-orange-300 px-2 py-0.5 rounded-full font-medium"
                              x-text="metrics.devoluciones.length + ' registro(s)'"></span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide border-b border-gray-200 dark:border-gray-700">
                                    <th class="pb-2 pr-4 font-semibold">Folio</th>
                                    <th class="pb-2 pr-4 font-semibold">Fecha</th>
                                    <th class="pb-2 pr-4 font-semibold">Monto</th>
                                    <th class="pb-2 font-semibold">Motivo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(d, i) in metrics.devoluciones" :key="i">
                                    <tr class="border-t border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                        <td class="py-2 pr-4 font-mono text-xs text-orange-500 font-semibold" x-text="d.folio ?? '—'"></td>
                                        <td class="py-2 pr-4 text-gray-500 dark:text-gray-400" x-text="d.fecha ?? '—'"></td>
                                        <td class="py-2 pr-4 font-semibold text-red-500 tabular-nums" x-text="money(d.monto)"></td>
                                        <td class="py-2 text-gray-500 dark:text-gray-400" x-text="d.motivo ?? '—'"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </template>

        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/exceljs/4.3.0/exceljs.min.js"></script>
    <script>
    function corteCaja() {
        return {
            loading: false,
            date: new Date().toISOString().slice(0, 10),
            lastUpdate: null,
            metrics: { ventas: [], compras: [], gastos: [], devoluciones: [], cancelados: [], ingresos: 0, egresos: 0, diferencia: 0 },

            init() { this.load(); },

            money(v) {
                return '$' + Number(v ?? 0).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            },

            async exportCSV() {
                if (!this.metrics.ventas?.length) return;

                const wb = new ExcelJS.Workbook();
                wb.creator = 'Sistema';
                const ws = wb.addWorksheet('Ventas');

                // ── Columnas con anchos ──
                ws.columns = [
                    { header: 'Folio',       key: 'folio',           width: 10 },
                    { header: 'Fecha',        key: 'fecha',           width: 26 },
                    { header: 'Monto',        key: 'monto',           width: 16 },
                    { header: 'Cliente',      key: 'cliente',         width: 30 },
                    { header: 'Nº Servicio',  key: 'numero_servicio', width: 16 },
                    { header: 'Método',       key: 'metodo',          width: 24 },
                ];

                // ── Estilo encabezado ──
                const headerRow = ws.getRow(1);
                headerRow.height = 20;
                ws.columns.forEach((col, idx) => {
                    const cell = headerRow.getCell(idx + 1);
                    cell.value = col.header;
                    cell.font      = { bold: true, color: { argb: 'FFFFFFFF' }, size: 11, name: 'Arial' };
                    cell.fill      = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF1A7A3C' } };
                    cell.alignment = { horizontal: 'center', vertical: 'middle' };
                    cell.border    = {
                        top:    { style: 'medium', color: { argb: 'FF155724' } },
                        bottom: { style: 'medium', color: { argb: 'FF155724' } },
                        left:   { style: 'medium', color: { argb: 'FF155724' } },
                        right:  { style: 'medium', color: { argb: 'FF155724' } },
                    };
                });

                // ── Filas de datos ──
                const thinBorder = {
                    top:    { style: 'thin', color: { argb: 'FFAAAAAA' } },
                    bottom: { style: 'thin', color: { argb: 'FFAAAAAA' } },
                    left:   { style: 'thin', color: { argb: 'FFAAAAAA' } },
                    right:  { style: 'thin', color: { argb: 'FFAAAAAA' } },
                };

                this.metrics.ventas.forEach((v, i) => {
                    const row = ws.addRow({
                        folio:           v.folio ?? '',
                        fecha:           v.fecha ?? '',
                        monto:           Number(v.monto ?? 0),
                        cliente:         v.cliente ?? '',
                        numero_servicio: v.numero_servicio ?? '',
                        metodo:          v.metodo ?? '',
                    });
                    row.height = 16;

                    // Fondo alternado sutil
                    const bgColor = i % 2 === 0 ? 'FFFFFFFF' : 'FFF5FBF7';

                    row.eachCell({ includeEmpty: true }, (cell, colNumber) => {
                        cell.border    = thinBorder;
                        cell.font      = { size: 10, name: 'Arial' };
                        cell.fill      = { type: 'pattern', pattern: 'solid', fgColor: { argb: bgColor } };
                        cell.alignment = { vertical: 'middle', horizontal: colNumber === 3 ? 'right' : 'left' };
                    });

                    // Formato moneda en columna Monto
                    row.getCell(3).numFmt = '"$"#,##0.00';
                });

                // ── Fila de total ──
                const total = this.metrics.ventas.reduce((s, v) => s + Number(v.monto ?? 0), 0);
                const totalRow = ws.addRow({ folio: '', fecha: 'TOTAL', monto: total, cliente: '', numero_servicio: '', metodo: '' });
                totalRow.height = 18;
                totalRow.eachCell({ includeEmpty: true }, (cell, colNumber) => {
                    cell.font      = { bold: true, size: 11, name: 'Arial' };
                    cell.fill      = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFD6F0DF' } };
                    cell.alignment = { vertical: 'middle', horizontal: colNumber === 3 ? 'right' : 'left' };
                    cell.border    = {
                        top:    { style: 'medium', color: { argb: 'FF1A7A3C' } },
                        bottom: { style: 'medium', color: { argb: 'FF1A7A3C' } },
                        left:   { style: 'thin',   color: { argb: 'FFAAAAAA' } },
                        right:  { style: 'thin',   color: { argb: 'FFAAAAAA' } },
                    };
                });
                totalRow.getCell(3).numFmt = '"$"#,##0.00';

                // ── Descargar ──
                const buffer = await wb.xlsx.writeBuffer();
                const blob   = new Blob([buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
                const a      = document.createElement('a');
                a.href       = URL.createObjectURL(blob);
                a.download   = `ventas-${this.date}.xlsx`;
                a.click();
                URL.revokeObjectURL(a.href);
            },

            load() {
                this.loading = true;
                const url = new URL('{{ route('admin.dashboard.corte') }}', window.location.origin);
                url.searchParams.set('date', this.date);
                fetch(url)
                    .then(r => r.json())
                    .then(d => {
                        if (d.ok) {
                            this.metrics = d;
                            this.lastUpdate = new Date().toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' });
                        }
                    })
                    .catch(console.error)
                    .finally(() => { this.loading = false; });
            },
        };
    }
    </script>
</x-app-layout>