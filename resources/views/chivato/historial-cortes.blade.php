<x-app-sidebar>
    <x-slot name="headerTitle">Historial de Cortes - Chivato</x-slot>

    <div class="min-h-screen bg-gray-100 py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-800 mb-2">Historial de Cortes de Caja</h2>
                        <p class="text-sm text-gray-600">Lista de todos los cortes de caja cerrados</p>
                    </div>
                    <div class="p-3 bg-indigo-100 rounded-lg">
                        <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Tabla de Cortes -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-500">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-100">
                            <tr>
                                <th class="px-4 py-3"># Corte</th>
                                <th class="px-4 py-3">Inicio</th>
                                <th class="px-4 py-3">Fin</th>
                                <th class="px-4 py-3">Cobros</th>
                                <th class="px-4 py-3">Total Recaudado</th>
                                <th class="px-4 py-3">Estado</th>
                                <th class="px-4 py-3">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($cortes as $index => $corte)
                                <tr class="bg-white border-b hover:bg-gray-50">
                                    <td class="px-4 py-3 font-medium text-gray-900">
                                        #{{ $corte->id }}
                                    <td class="px-4 py-3">
                                        <div class="text-gray-900 font-medium">
                                            {{ $corte->fecha_inicio->format('d/m/Y') }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            {{ $corte->fecha_inicio->format('H:i') }} hrs
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($corte->fecha_fin)
                                            <div class="text-gray-900 font-medium">
                                                {{ $corte->fecha_fin->format('d/m/Y') }}
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                {{ $corte->fecha_fin->format('H:i') }} hrs
                                            </div>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            {{ $corte->total_pagos }} cobro{{ $corte->total_pagos !== 1 ? 's' : '' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 font-medium text-green-600">
                                        ${{ number_format($corte->total_recaudado, 2) }}
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($corte->estado === 'cerrado')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                Cerrado
                                            </span>
                                        @elseif($corte->estado === 'activo')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                Activo
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                {{ $corte->estado }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-1.5">
                                            @if($index === 0 && !$corteActivo && $corte->estado === 'cerrado')
                                                <button onclick="reanudarCorte({{ $corte->id }})"
                                                        class="inline-flex items-center px-3 py-1.5 bg-yellow-500 hover:bg-yellow-600 text-white text-xs font-medium rounded-lg transition-colors duration-200">
                                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                                    </svg>
                                                    Reanudar
                                                </button>
                                            @endif
                                            <button onclick="imprimirTicketCorte({{ $corte->id }})" title="Imprimir ticket de corte"
                                                    class="inline-flex items-center px-2.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded-lg transition-colors duration-200">
                                                🧾
                                            </button>
                                            <button onclick="imprimirDetallePagos({{ $corte->id }})" title="Imprimir detalle de pagos"
                                                    class="inline-flex items-center px-2.5 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-medium rounded-lg transition-colors duration-200">
                                                📋
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                                        <div class="flex flex-col items-center">
                                            <svg class="w-12 h-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                            </svg>
                                            <p>No hay cortes registrados en el historial.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Script para reanudar corte -->
            <script>
                function reanudarCorte(corteId) {
                    Swal.fire({
                        title: '¿Reanudar corte?',
                        text: 'Podrás seguir agregando pagos a este corte.',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#EAB308',
                        cancelButtonColor: '#6B7280',
                        confirmButtonText: 'Sí, reanudar',
                        cancelButtonText: 'Cancelar',
                        reverseButtons: true
                    }).then((result) => {
                        if (result.isConfirmed) {
                            ejecutarReanudar(corteId);
                        }
                    });
                }

                function ejecutarReanudar(corteId) {
                    fetch('/chivato/corte/reanudar', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({})
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.ok) {
                            Swal.fire({
                                title: '¡Reanudado!',
                                text: 'El corte ha sido reanudado correctamente.',
                                icon: 'success',
                                confirmButtonColor: '#10B981',
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire({
                                title: 'Error',
                                text: data.message || 'Error al reanudar el corte',
                                icon: 'error',
                                confirmButtonColor: '#EF4444'
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            title: 'Error',
                            text: 'Error al reanudar el corte',
                            icon: 'error',
                            confirmButtonColor: '#EF4444'
                        });
                    });
                }

                function fmtMoney(n) {
                    return '$' + Number(n).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                }

                async function obtenerDetalleCorte(corteId) {
                    const r = await fetch('/chivato/corte/' + corteId + '/detalle', { headers: { 'Accept': 'application/json' } });
                    const j = await r.json();
                    if (!j.ok) {
                        Swal.fire({ title: 'Error', text: j.message || 'No se pudo obtener el corte', icon: 'error', confirmButtonColor: '#EF4444' });
                        return null;
                    }
                    return j;
                }

                async function imprimirTicketCorte(corteId) {
                    const d = await obtenerDetalleCorte(corteId);
                    if (!d) return;
                    const totalCaja = d.total_caja;
                    const comisionRecibo = d.total_comision_recibo;
                    const totalEntregar = totalCaja - comisionRecibo;
                    const numPagos = d.pagos.length;
                    const cobrador = d.cobrador;
                    const zona = 'CHIVATO';
                    const fecha = new Date().toLocaleString('es-MX', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });

                    const html = `
                    <html><head><meta charset="UTF-8">
                    <style>
                      @page { margin: 10mm; }
                      * { margin:0; padding:0; box-sizing:border-box }
                      body { font-family:'Courier New', monospace; font-size:30px; color:#000; width:100% }
                      .center { text-align:center } .right { text-align:right }
                      table { width:100%; border-collapse:collapse }
                      td { padding:14px 0; font-size:28px }
                      .lbl { font-size:26px; font-weight:bold } .muted { font-size:22px }
                      .bold { font-weight:bold } .total { font-size:48px; font-weight:bold }
                      .dash { border-top:2px solid #000; margin:22px 0 } .solid { border-top:4px solid #000; margin:22px 0 }
                    </style></head><body>
                    <div class="center">
                      <div class="bold" style="font-size:56px">TICKET DE CORTE</div>
                      <div style="font-size:30px;letter-spacing:2px;font-weight:bold">${zona}</div>
                    </div>
                    <hr class="dash">
                    <table>
                      <tr><td class="lbl">Fecha</td><td class="right">${fecha}</td></tr>
                      <tr><td class="lbl">Cobrador</td><td class="right bold">${cobrador}</td></tr>
                      <tr><td class="lbl">N° de pagos</td><td class="right bold">${numPagos}</td></tr>
                    </table>
                    <hr class="dash">
                    <table>
                      <tr><td class="lbl">Total en caja</td><td class="right">${fmtMoney(totalCaja)}</td></tr>
                      <tr><td class="lbl">(-) Comisión recibo</td><td class="right">${fmtMoney(comisionRecibo)}</td></tr>
                    </table>
                    <hr class="solid">
                    <table>
                      <tr><td class="bold" style="font-size:36px">TOTAL A ENTREGAR</td><td class="right total">${fmtMoney(totalEntregar)}</td></tr>
                    </table>
                    </body></html>`;

                    const w = window.open('', '_blank', 'width=850,height=1100,toolbar=0');
                    w.document.write(html);
                    w.document.close();
                    setTimeout(() => { w.print(); w.close(); }, 600);
                }

                async function imprimirDetallePagos(corteId) {
                    const d = await obtenerDetalleCorte(corteId);
                    if (!d) return;
                    const cobrador = d.cobrador;
                    const zona = 'CHIVATO';
                    const fechaImpresion = new Date().toLocaleString('es-MX', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });

                    const filas = d.pagos.map(p => `
                      <div style="display:flex;justify-content:space-between;margin-top:18px">
                        <span class="bold" style="font-size:30px">${p.numero_servicio}</span>
                        <span class="bold" style="font-size:30px">${fmtMoney(p.total)}</span>
                      </div>
                      <div style="font-size:26px">${p.nombre || '-'}</div>
                      <div class="muted" style="font-size:22px">${p.fecha}</div>
                      <hr class="dash">
                    `).join('');

                    const html = `
                    <html><head><meta charset="UTF-8">
                    <style>
                      @page { margin: 10mm; }
                      * { margin:0; padding:0; box-sizing:border-box }
                      body { font-family:'Courier New', monospace; font-size:26px; color:#000; width:100% }
                      .center { text-align:center } .right { text-align:right }
                      table { width:100%; border-collapse:collapse }
                      .resumen td { padding:12px 0; font-size:28px }
                      .lbl { font-size:26px; font-weight:bold } .muted { font-size:22px }
                      .bold { font-weight:bold }
                      .dash { border-top:2px solid #000; margin:18px 0 } .solid { border-top:4px solid #000; margin:22px 0 }
                    </style></head><body>
                    <div class="center">
                      <div class="bold" style="font-size:52px">DETALLE DE PAGOS</div>
                      <div style="font-size:30px;letter-spacing:2px;font-weight:bold">${zona}</div>
                    </div>
                    <hr class="dash">
                    <table class="resumen">
                      <tr><td class="lbl">Fecha impresión</td><td class="right">${fechaImpresion}</td></tr>
                      <tr><td class="lbl">Cobrador</td><td class="right bold">${cobrador}</td></tr>
                      <tr><td class="lbl">N° de pagos</td><td class="right bold">${d.pagos.length}</td></tr>
                    </table>
                    <hr class="solid">
                    ${filas}
                    </body></html>`;

                    const w = window.open('', '_blank', 'width=850,height=1100,toolbar=0');
                    w.document.write(html);
                    w.document.close();
                    setTimeout(() => { w.print(); w.close(); }, 600);
                }
            </script>

            <!-- Resumen -->
            @if($cortes->count() > 0)
                <div class="mt-6">
                    <div class="bg-white rounded-lg shadow-sm p-6 max-w-md">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600">Resumen del Historial</p>
                                <p class="text-xs text-gray-500 mt-1">
                                    {{ $cortes->count() }} corte{{ $cortes->count() !== 1 ? 's' : '' }} registrado{{ $cortes->count() !== 1 ? 's' : '' }}
                                </p>
                            </div>
                            <div class="p-3 bg-indigo-100 rounded-lg">
                                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-sidebar>
