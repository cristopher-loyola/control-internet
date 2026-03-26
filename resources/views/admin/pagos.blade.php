<x-app-layout title="Pagos">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            {{ __('Pagos') }}
        </h2>
    </x-slot>

    <div class="py-6" x-data="pagosRecibo()" x-init="init && init()">
        <div class="max-w-6xl mx-auto sm:px-4 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <template x-if="readOnlyMode">
                        <div class="mb-3 px-3 py-2 rounded bg-amber-500/20 text-amber-900 border border-amber-400 text-sm not-print">
                            Vista en modo solo lectura: los campos están deshabilitados.
                        </div>
                    </template>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 not-print">
    <div class="md:col-span-2 bg-gray-50 dark:bg-gray-700 rounded-xl p-5 shadow-inner">
        <h3 class="text-sm font-bold uppercase tracking-wider text-gray-400 dark:text-gray-300 mb-4"> Buscar cliente</h3>
        <div class="grid grid-cols-2 gap-4">
            <div class="col-span-2">
                <label for="numero" class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">ID del cliente</label>
                <input id="numero" type="number" placeholder="Ingresa el ID..."
                    class="form-input w-full rounded-lg border-gray-300 dark:border-gray-600 shadow-sm text-base focus:border-indigo-400 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                    x-model.trim="form.numero" :disabled="readOnlyMode"
                    @change="!readOnlyMode && buscar()" @keydown.enter.prevent="!readOnlyMode && buscar()">
                <p class="text-xs text-red-500 mt-1" x-text="error" x-show="error"></p>
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">Recargo</label>
                <select class="form-select w-full rounded-lg border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-400 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                    x-model="form.recargo" :disabled="readOnlyMode" @change="inputChanged()">
                    <option value="no">No</option>
                    <option value="si">Sí</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">Pago anterior</label>
                <input type="number" step="1" placeholder="0.00"
                    class="form-input w-full rounded-lg border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-400 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                    x-model.number="form.pago_anterior" :disabled="readOnlyMode" @input="inputChanged()">
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">Método de pago</label>
                <select class="form-select w-full rounded-lg border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-400 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                    x-model="form.metodo" :disabled="readOnlyMode" required @change="inputChanged()">
                    <option value="">Selecciona...</option>
                    <option value="Tarjeta de Crédito">Tarjeta de Crédito</option>
                    <option value="Cheque">Cheque</option>
                    <option value="Deposito a cuenta">Depósito a cuenta</option>
                    <option value="Efectivo">Efectivo</option>
                </select>
            </div>

            <div class="col-span-2 grid grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">Pago por adelantado</label>
                    <select class="form-select w-full rounded-lg border-gray-300 dark:border-gray-600 shadow-sm"
                        x-model="form.prepay" :disabled="readOnlyMode" @change="inputChanged()">
                        <option value="no">No</option>
                        <option value="si">Sí</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">Otro</label>
                    <select class="form-select w-full rounded-lg border-gray-300 dark:border-gray-600 shadow-sm"
                        x-model="form.otro" :disabled="readOnlyMode" @change="inputChanged()">
                        <option value="no">No</option>
                        <option value="cancelacion">Cancelación de servicio</option>
                    </select>
                </div>
                <div x-show="form.prepay==='si'" x-cloak>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">Meses (1–12)</label>
                    <select class="form-select w-full rounded-lg border-gray-300 dark:border-gray-600 shadow-sm"
                        x-model.number="form.prepay_months" :disabled="readOnlyMode || form.prepay!=='si'" @change="inputChanged()">
                        <option value="1">1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                        <option value="4">4</option>
                        <option value="5">5</option>
                        <option value="6">6</option>
                        <option value="7">7</option>
                        <option value="8">8</option>
                        <option value="9">9</option>
                        <option value="10">10</option>
                        <option value="11">11</option>
                        <option value="12">12</option>
                    </select>
                </div>
                <div x-show="form.prepay==='si'" x-cloak>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">Total adelanto</label>
                    <input type="text" readonly class="form-input w-full rounded-lg border-gray-300 dark:border-gray-600 shadow-sm"
                        :value="moneda(totales.prepay_total || 0)">
                    <p class="text-[11px]" :class="prepayError ? 'text-red-600' : 'text-gray-500'" x-text="prepayError || prepayLegend"></p>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">Quién cobró</label>
                <input type="text" placeholder="Nombre del cobrador"
                    class="form-input w-full rounded-lg border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-400 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                    x-model="form.cobro" :disabled="readOnlyMode">
            </div>
        </div>
    </div>      
  <!-- <div class="flex items-end justify-end md:justify-start gap-2">
                            <button class="btn btn-secondary" @click="openHistorial()">Historial</button>
                            <button class="btn btn-secondary" @click="toggleEditor()"
                                x-text="editMode ? 'Cerrar editor de plantilla' : 'Editar plantilla'"></button>
                            <button class="btn btn-secondary" @click="resetLayout()">Restablecer</button> 
                            < <button class="btn btn-secondary" @click="saveAsDefault()">Guardar como predeterminado</button> 
                                x-text="editMode ? 'Cerrar editor' : 'Editar plantilla'"></button> 
                            <button class="btn btn-secondary" x-show="editMode" @click="resetLayout()">Restablecer</button>
                            <button class="btn btn-secondary" x-show="editMode" @click="saveAsDefault()">Guardar cambios</button>
                            <button class="btn btn-danger" @click="openConfirm()">Exportar a PDF</button>
                        </div>  NO ELIMINAR, SIRVE PARA EDITAR LA PLANTILLA      -->
  <div class="flex flex-col items-center justify-center gap-3 bg-gray-50 dark:bg-gray-700 rounded-xl p-5 shadow-inner">
    <h3 class="text-sm font-bold uppercase tracking-wider text-gray-400 dark:text-gray-300">Acciones</h3>

    <a class="btn btn-secondary w-full text-center shadow hover:shadow-md hover:brightness-110 active:scale-95 transition-all duration-150" href="{{ route('admin.pagos.historial') }}">📋 Historial</a>

    <button class="btn btn-success w-full shadow hover:shadow-md hover:brightness-110 active:scale-95 transition-all duration-150" @click="openDiscountModal()">🏷️ Aplicar Descuento</button>

    <button class="btn btn-primary w-full shadow hover:shadow-md hover:brightness-110 active:scale-95 transition-all duration-150" @click="metodoValido() && openConfirm('ticket')">🧾 Imprimir Ticket</button>

    <button class="btn btn-danger w-full shadow hover:shadow-md hover:brightness-110 active:scale-95 transition-all duration-150" @click="metodoValido() && openConfirm('receipt')">🖨️ Imprimir Recibo</button>
</div>
</div>

    <!-- Modal de Descuento -->
    <div x-show="discountModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 not-print">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-96">
            <div class="px-5 pt-5 pb-3 border-b border-gray-200 dark:border-gray-700">
                <div class="text-sm font-semibold text-gray-800 dark:text-gray-100 uppercase tracking-wider">
                    Aplicar Descuento
                </div>
            </div>
            <div class="px-5 py-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Monto del descuento</label>
                <input type="number" step="0.01" min="0" placeholder="0.00"
                    class="form-input w-full rounded-lg border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-400 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                    x-model.number="discountAmount">
                <p class="text-xs text-gray-500 mt-2">Este monto se restará del total a pagar</p>
            </div>
            <div class="px-5 pb-4 flex justify-end gap-2">
                <button class="btn btn-secondary" @click="discountModalOpen = false">Cancelar</button>
                <button class="btn btn-primary" @click="applyDiscount()">Aplicar</button>
            </div>
        </div>
    </div>

<!-- Información de Adeudos -->
<div x-show="!pagadoMesActual && adeudo && adeudo.meses>0" class="mt-4 mb-4 p-4 bg-red-50 border border-red-200 rounded-lg not-print">
    <div class="flex items-center gap-2 mb-2">
        <svg class="w-5 h-5 text-red-600" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
        </svg>
        <h3 class="text-lg font-semibold text-red-800">Información de Adeudos</h3>
    </div>
    <div class="text-sm text-red-700">
        <p class="mb-1">
            <strong>Cliente con adeudos:</strong> 
            <span x-text="`Adeuda desde ${new Date(adeudo.desde_label).toLocaleDateString('es-MX', { month: 'long', year: 'numeric' })}`"></span>
        </p>
        <p class="mb-1">
            <strong>Total a pagar incluyendo adeudos:</strong> 
            <span class="font-bold text-red-900" x-text="moneda(totales.total)"></span>
        </p>
        <p class="mb-1" x-show="appliedDiscount > 0">
            <strong>Descuento aplicado:</strong> 
            <span class="font-bold text-red-900" x-text="moneda(appliedDiscount)"></span>
        </p>
    </div>
</div>

<!-- Información de Pagos al Corriente -->
<div x-show="pagadoMesActual" class="mt-4 mb-4 p-4 bg-green-50 border border-green-200 rounded-lg not-print">
    <div class="flex items-center gap-2 mb-2">
        <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
        </svg>
        <h3 class="text-lg font-semibold text-green-800">Información de Pagos</h3>
    </div>
    <div class="text-sm text-green-700">
        <p>
            <strong>Cliente con pagos en regla:</strong> 
            <span>Sus pagos están al corriente</span>
        </p>
        <p>
            <strong>Total a pagar:</strong> 
            <span class="font-bold text-green-900" x-text="moneda(totales.total)"></span>
        </p>
    </div>
</div>

<br> 
                          {{-- <div class="flex items-end justify-end md:justify-start gap-2">
                             <button class="btn btn-secondary" @click="toggleEditor()"
                                x-text="editMode ? 'Cerrar editor de plantilla' : 'Editar plantilla'"></button>
                            <button class="btn btn-secondary" @click="resetLayout()">Restablecer</button>
                            <button class="btn btn-secondary" @click="saveAsDefault()">Guardar como predeterminado</button>
                        </div>  no eliminar este apartado, nos funciona para guardar la plantilla   --}}

                    <div class="mt-6 print-sheet" x-ref="sheet" x-show="layoutReady" x-cloak>
                        <div x-show="saveConfirmOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 not-print">
                            <div class="bg-white dark:bg-gray-800 rounded shadow p-6 w-96">
                                <div class="text-lg font-semibold mb-4">¿Desea guardar la información antes de generar el <span x-text="printType === 'ticket' ? 'ticket' : 'recibo'"></span>?</div>
                                <div class="flex justify-end gap-2">
                                    <button class="btn btn-secondary" @click="confirmSaveNo()" :disabled="isSaving">No</button>
                                    <button class="btn btn-primary flex items-center gap-2" @click="confirmSaveYes()" :disabled="isSaving">
                                        <template x-if="isSaving">
                                            <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                        </template>
                                        Sí
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div x-show="metodoModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 not-print">
                            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-96">
                                <div class="px-5 pt-5 pb-3 border-b border-gray-200 dark:border-gray-700">
                                    <div class="text-sm font-semibold text-gray-800 dark:text-gray-100 uppercase tracking-wider">
                                        Método de pago requerido
                                    </div>
                                </div>
                                <div class="px-5 py-4 text-sm text-gray-700 dark:text-gray-200">
                                    Por favor selecciona un método de pago antes de imprimir el ticket o el recibo.
                                </div>
                                <div class="px-5 pb-4 flex justify-end">
                                    <button class="btn btn-primary" @click="metodoModalOpen = false">Entendido</button>
                                </div>
                            </div>
                        </div>
                        
                        <template x-if="editMode">
                            <div class="text-xs text-gray-500 mb-2 not-print">Arrastra las imágenes para acomodarlas. Se guardará tu plantilla.</div>
                        </template>
                        <div class="sheet-abs">
                            @php $logoImg = public_path('images/logo.png'); @endphp
                            @if (file_exists($logoImg))
                            <div class="abs-img"
                                 :style="'left:'+layout.logo.left+'px; top:'+layout.logo.top+'px; width:'+layout.logo.w+'px'"
                                 :class="editMode ? 'draggable' : ''"
                                 @mousedown="startDrag('logo', $event)" @touchstart.prevent="startDrag('logo', $event.touches[0])">
                                <img src="{{ asset('images/logo.png') }}" class="block w-full h-auto" alt="Control Internet">
                                <template x-if="editMode">
                                    <span class="resize-handle" @mousedown.stop="startResize('logo', $event)" @touchstart.stop.prevent="startResize('logo', $event.touches[0])"></span>
                                </template>
                            </div>
                            @endif
                            <div class="abs-text"
                                 :style="'left:'+layout.mes.left+'px; top:'+layout.mes.top+'px; width:'+layout.mes.w+'px'"
                                 :class="editMode ? 'draggable text-box' : 'text-box'"
                                 @mousedown="startDrag('mes', $event)" @touchstart.prevent="startDrag('mes', $event.touches[0])">
                                <div class="text-3xl font-extrabold tracking-wide opacity-80 text-center" x-text="mesEnCurso()"></div>
                                <template x-if="editMode">
                                    <span class="resize-handle" @mousedown.stop="startResize('mes', $event)" @touchstart.stop.prevent="startResize('mes', $event.touches[0])"></span>
                                </template>
                            </div>
                            @php $sello = public_path('images/sello.png'); @endphp
                            @if (file_exists($sello))
                            <div class="abs-img"
                                 :style="'left:'+layout.sello.left+'px; top:'+layout.sello.top+'px; width:'+layout.sello.w+'px'"
                                 :class="editMode ? 'draggable' : ''"
                                 @mousedown="startDrag('sello', $event)" @touchstart.prevent="startDrag('sello', $event.touches[0])">
                                <img src="{{ asset('images/sello.png') }}" class="block w-full h-auto opacity-60" alt="Sello">
                                <template x-if="editMode">
                                    <span class="resize-handle" @mousedown.stop="startResize('sello', $event)" @touchstart.stop.prevent="startResize('sello', $event.touches[0])"></span>
                                </template>
                            </div>
                            @endif
                            @php $alerta = public_path('images/recibo-alerta.png'); @endphp
                            @if (file_exists($alerta))
                            <div class="abs-img"
                                 :style="'left:'+layout.alerta.left+'px; top:'+layout.alerta.top+'px; width:'+layout.alerta.w+'px'"
                                 :class="editMode ? 'draggable' : ''"
                                 @mousedown="startDrag('alerta', $event)" @touchstart.prevent="startDrag('alerta', $event.touches[0])">
                                <img src="{{ asset('images/recibo-alerta.png') }}" class="block w-full h-auto" alt="Contacto">
                                <template x-if="editMode">
                                    <span class="resize-handle" @mousedown.stop="startResize('alerta', $event)" @touchstart.stop.prevent="startResize('alerta', $event.touches[0])"></span>
                                </template>
                            </div>
                            @endif
                            @php $cuenta = public_path('images/cuenta.png'); @endphp
                            @if (file_exists($cuenta))
                            <div class="abs-img"
                                 :style="'left:'+layout.cuenta.left+'px; top:'+layout.cuenta.top+'px; width:'+layout.cuenta.w+'px'"
                                 :class="editMode ? 'draggable' : ''"
                                 @mousedown="startDrag('cuenta', $event)" @touchstart.prevent="startDrag('cuenta', $event.touches[0])">
                                <img src="{{ asset('images/cuenta.png') }}" class="block w-full h-auto" alt="Cuenta bancaria">
                                <template x-if="editMode">
                                    <span class="resize-handle" @mousedown.stop="startResize('cuenta', $event)" @touchstart.stop.prevent="startResize('cuenta', $event.touches[0])"></span>
                                </template>
                            </div>
                            @endif
                            @php $reportes = public_path('images/reportes.png'); @endphp
                            @if (file_exists($reportes))
                            <div class="abs-img"
                                 :style="'left:'+layout.reportes.left+'px; top:'+layout.reportes.top+'px; width:'+layout.reportes.w+'px'"
                                 :class="editMode ? 'draggable' : ''"
                                 @mousedown="startDrag('reportes', $event)" @touchstart.prevent="startDrag('reportes', $event.touches[0])">
                                <img src="{{ asset('images/reportes.png') }}" class="block w-full h-auto" alt="Reportes teléfono">
                                <template x-if="editMode">
                                    <span class="resize-handle" @mousedown.stop="startResize('reportes', $event)" @touchstart.stop.prevent="startResize('reportes', $event.touches[0])"></span>
                                </template>
                            </div>
                            @endif
                            @php $wha = public_path('images/wha.png'); @endphp
                            @if (file_exists($wha))
                            <div class="abs-img"
                                 :style="'left:'+layout.wha.left+'px; top:'+layout.wha.top+'px; width:'+layout.wha.w+'px'"
                                 :class="editMode ? 'draggable' : ''"
                                 @mousedown="startDrag('wha', $event)" @touchstart.prevent="startDrag('wha', $event.touches[0])">
                                <img src="{{ asset('images/wha.png') }}" class="block w-full h-auto" alt="WhatsApp">
                                <template x-if="editMode">
                                    <span class="resize-handle" @mousedown.stop="startResize('wha', $event)" @touchstart.stop.prevent="startResize('wha', $event.touches[0])"></span>
                                </template>
                            </div>
                            @endif
                        </div>

                        <div class="receipt">
                            <div class="ref-number" x-show="ref.numero">
                                <span x-text="refNumberPad()"></span>
                            </div>
                            <div class="text-right text-xs text-gray-500">Copia: COBRADOR</div>
                            <div class="receipt-head"></div>
                            <div class="id-band">
                                <span class="font-bold">ID</span>
                                <span class="font-bold" x-text="form.numero || '—'"></span>
                            </div>
                            <div class="receipt-grid">
                                <div>Nombre</div><div x-text="datos.nombre || '—'"></div>
                                <div>Mes</div><div x-text="mesEnCursoCompleto()"></div>
                                <div>Mensualidad de Internet</div><div x-text="moneda(datos.mensualidad)"></div>
                                <div>Otros</div><div x-text="form.otro === 'cancelacion' ? 'Cancelación de servicio' : 'No'"></div>
                                <div>Importe</div><div x-text="moneda(0)"></div>
                                <div x-show="appliedDiscount > 0">Descuento</div><div x-show="appliedDiscount > 0" x-text="moneda(appliedDiscount)"></div>
                                <div>Recargo</div><div x-text="form.recargo === 'si' ? 'SI' : 'NO'"></div>
                                <div>Costo de reconexión</div><div x-text="form.recargo === 'si' ? moneda(50) : moneda(0)"></div>
                                <div>Pago por adelantado</div><div x-text="form.prepay==='si' ? 'SÍ' : 'NO'"></div>
                                <div x-show="form.prepay==='si'">Meses adelantados</div><div x-show="form.prepay==='si'" x-text="`${form.prepay_months} (hasta ${mesFinalCobertura(form.prepay_months)})` || '-'"></div>
                                <div>Su pago anterior</div><div x-text="moneda(form.pago_anterior || 0)"></div>
                                <div>Fecha de pago anterior</div><div x-text="pagoAnteriorFecha || '—'"></div>
                                <div>Total a pagar en número</div><div x-text="moneda(totales.total)"></div>
                                <div class="col-span-1">Total a pagar en letra</div><div class="col-span-1" x-text="totales.letra"></div>
                                <div>Método de pago</div><div x-text="form.metodo || '—'"></div>
                                <div>Cobro</div><div x-text="form.cobro || '—'"></div>
                                <div>Fecha</div><div x-text="fecha()"></div>
                                <div>Hora</div><div x-text="hora()"></div>
                            </div>
                        </div>

                        <div class="divider-line"></div>

                        <div class="receipt client-receipt">
                            <div class="ref-number" x-show="ref.numero">
                                <span x-text="refNumberPad()"></span>
                            </div>
                            <div class="text-right text-xs text-gray-500">Copia: CLIENTE</div>
                            <div class="receipt-head"></div>
                            <div class="id-band">
                                <span class="font-bold">ID</span>
                                <span class="font-bold" x-text="form.numero || '—'"></span>
                            </div>
                           <div class="receipt-grid">
                                <div>Nombre</div><div x-text="datos.nombre || '—'"></div>
                                <div>Mes</div><div x-text="mesEnCursoCompleto()"></div>
                                <div>Mensualidad de Internet</div><div x-text="moneda(datos.mensualidad)"></div>
                                <div>Otros</div><div x-text="form.otro === 'cancelacion' ? 'Cancelación de servicio' : 'No'"></div>
                                <div>Importe</div><div x-text="moneda(0)"></div>
                                <div x-show="appliedDiscount > 0">Descuento</div><div x-show="appliedDiscount > 0" x-text="moneda(appliedDiscount)"></div>
                                <div>Recargo</div><div x-text="form.recargo === 'si' ? 'SI' : 'NO'"></div>
                                <div>Costo de reconexión</div><div x-text="form.recargo === 'si' ? moneda(50) : moneda(0)"></div>
                                <div>Pago por adelantado</div><div x-text="form.prepay==='si' ? 'SÍ' : 'NO'"></div>
                                <div x-show="form.prepay==='si'">Meses adelantados</div><div x-show="form.prepay==='si'" x-text="`${form.prepay_months} (hasta ${mesFinalCobertura(form.prepay_months)})` || '-'"></div>
                                <div>Su pago anterior</div><div x-text="moneda(form.pago_anterior || 0)"></div>
                                <div>Fecha de pago anterior</div><div x-text="pagoAnteriorFecha || '—'"></div>
                                <div>Total a pagar en número</div><div x-text="moneda(totales.total)"></div>
                                <div class="col-span-1">Total a pagar en letra</div><div class="col-span-1" x-text="totales.letra"></div>
                                <div>Método de pago</div><div x-text="form.metodo || '—'"></div>
                                <div>Cobro</div><div x-text="form.cobro || '—'"></div>
                                <div>Fecha</div><div x-text="fecha()"></div>
                                <div>Hora</div><div x-text="hora()"></div>
                            </div>
                            <div class="mt-4 text-center text-xs font-semibold text-gray-600 border-t pt-2">
                                <p>Recuerda que del 1 al 7 de mes se realizan los pagos correctamente, posterior a eso se cobrarán cargos por costo de reconexión.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        @media print{
            nav, header, .not-print{display:none!important}
            main, body{background:#fff!important;margin:0!important;padding:0!important;width:210mm;height:297mm;overflow:hidden}
            .print-sheet{padding:0!important;margin:0!important;page-break-inside:avoid;break-inside:avoid;break-after:avoid}
            .py-6,.py-12{padding:0!important}
            .p-6{padding:0!important}
            .shadow-sm,.sm\:rounded-lg,.overflow-hidden{box-shadow:none!important;border-radius:0!important;overflow:visible!important}
            .receipt{border:0!important;border-radius:0!important}
            .divider-line{display:none!important}
            .print-sheet::after{content:'';position:absolute;left:0;right:0;top:calc(50% - 0.3mm);height:0.6mm;background:#111;z-index:50}
        }
        .print-sheet{position:relative;width:210mm;max-width:none;margin:0 auto;transform:none;height:297mm;background:#fff}       
        .sheet-abs{position:absolute;inset:0;z-index:20;pointer-events:none}
        .receipt{position:relative;height:calc((297mm - 0.6mm)/2);border:1px solid #d1d5db;border-radius:8px;padding:6mm;background:#fff;overflow:hidden}
        .divider-line{height:0.6mm;background:#111;margin:0}
        .client-receipt{padding-top:8mm}
        .ref-number{position:absolute;top:2mm;left:6mm;font-weight:700;font-size:12px;color:#111;z-index:30}
        .id-band{background:#fde047;border:1px solid #eab308;border-radius:4px;padding:4px 8px;display:inline-flex;gap:10px;margin:10px 0;width:fit-content;max-width:60%}
        .receipt-grid{display:grid;grid-template-columns:1fr 1fr;gap:4px 12px;font-size:13px}
        .receipt-head img{max-height:120px;object-fit:contain}
        .logo-center{display:inline-block;max-width:680px;width:90%}
        .abs-img,.abs-text{position:absolute;pointer-events:none}
        .draggable{outline:1px dashed #888;pointer-events:auto;cursor:move;background:transparent}
        .text-box{max-width:680px}
        .resize-handle{position:absolute;right:-6px;bottom:-6px;width:14px;height:14px;background:#fff;border:1px solid #666;border-radius:2px;box-shadow:0 0 0 2px rgba(255,255,255,.6);cursor:se-resize}
        @page{size:A4;margin:0}
        html,body{-webkit-print-color-adjust:exact;print-color-adjust:exact}
        /* Responsive (pantallas pequeñas) */
        @media (max-width: 640px){
            .max-w-6xl{ max-width: 100%; }
            .print-sheet{ width: 100vw; height: auto; aspect-ratio: 210 / 297; margin: 0; }
            .receipt{ height: auto; padding: 12px; border-radius: 6px; }
            .divider-line{ height: 1px; margin: 12px 0; }
            .sheet-abs{ display: none; } /* Oculta overlays en móvil; la impresión no se afecta */
            .receipt-grid{ font-size: 0.95rem; line-height: 1.2; }
            .id-band{ margin: 8px 0; }
        }
    </style>

    <script>
    function pagosRecibo(){
        const toWords = (num) => {
            const unidades = ['','UNO','DOS','TRES','CUATRO','CINCO','SEIS','SIETE','OCHO','NUEVE'];
            const decenas = ['','DIEZ','VEINTE','TREINTA','CUARENTA','CINCUENTA','SESENTA','SETENTA','OCHENTA','NOVENTA'];
            const especiales = {11:'ONCE',12:'DOCE',13:'TRECE',14:'CATORCE',15:'QUINCE'};
            const centenas = ['','CIEN','DOSCIENTOS','TRESCIENTOS','CUATROCIENTOS','QUINIENTOS','SEISCIENTOS','SETECIENTOS','OCHOCIENTOS','NOVECIENTOS'];
            const n = Math.floor(num);
            const c2 = (n) => {
                if(n===0) return 'CERO';
                let s='';
                if(n>=100){ const c=Math.floor(n/100); s+= (c===1 && n%100===0)?'CIEN':centenas[c]; n%=100; if(n) s+=' '; }
                if(n>=10 && n<=15) return s+especiales[n];
                if(n>=16 && n<=19) return s+'DIECI'+unidades[n-10].toLowerCase();
                if(n===20) return s+'VEINTE';
                if(n>20 && n<30) return s+'VEINTI'+unidades[n-20].toLowerCase();
                if(n>=30){ const d=Math.floor(n/10); s+=decenas[d]; n%=10; if(n) s+=' Y '; }
                if(n>0) s+=unidades[n];
                return s;
            };
            const miles = Math.floor(n/1000); const resto = n%1000;
            let txt='';
            if(miles){ txt += miles===1 ? 'MIL' : c2(miles)+' MIL'; if(resto) txt+=' '; }
            txt += c2(resto);
            const cents = Math.round((num - Math.floor(num))*100).toString().padStart(2,'0');
            return txt + ' PESOS ' + cents + '/100 M.N.';
        };
        const defaultLayout = (()=>{
            const baseDefaults = {
                logo:{ left:60, top:130, w:680 },      // centrado cercano a la división
                mes:{ left:580, top:118, w:180 },      // texto del mes a la derecha
                sello:{ left:600, top:40, w:120 },     // sello parte superior derecha
                alerta:{ left:260, top:146, w:280 },   // banda de contacto bajo el logo
                cuenta:{ left:120, top:430, w:460 },   // datos de cuenta
                reportes:{ left:140, top:470, w:420 },  // teléfono de reportes
                wha:{ left:560, top:460, w:180 }
            };
            const d = localStorage.getItem('reciboLayoutDefault');
            if (d) {
                try {
                    const parsed = JSON.parse(d);
                    const merged = JSON.parse(JSON.stringify(baseDefaults));
                    if (parsed && typeof parsed === 'object') {
                        Object.keys(parsed).forEach(k=>{
                            merged[k] = Object.assign(merged[k]||{}, parsed[k]||{});
                        });
                    }
                    return merged;
                } catch(_) {}
            }
            return baseDefaults;
        })();
        return {
            readOnlyMode: false,
            form:{ numero:'', recargo:'no', pago_anterior:0, metodo:'', cobro:'', prepay:'no', prepay_months:6, otro:'no' },
            pagoAnteriorFecha:'',
            datos:{ nombre:'', mensualidad:0 },
            totales:{ total:0, letra:'' },
            adeudo:null,
            pagadoMesActual: false,
            prepayConfig:{ enabled:{}, matrix:{} },
            prepayError:'',
            discountModalOpen: false,
            discountAmount: 0,
            appliedDiscount: 0, // Nueva variable para almacenar el descuento aplicado
            get prepayLegend(){
                if(this.form.prepay!=='si') return '';
                const m = this.form.prepay_months||6;
                // Para meses 1-5 no hay descuento
                if(m <= 5) return 'Sin descuento';
                const info = this.prepayConfig.matrix?.[m];
                if(!info) return '';
                return `Descuento ${info.percent}%`;
            },
            ref:{ numero:null, id:null, created_at:null },
            saveConfirmOpen:false,
            isSaving:false,
            printType: 'receipt', // 'ticket' or 'receipt'
            metodoModalOpen:false,
            isPrinting:false,
            historial:[],
            printTimerId:null,
            layoutSaveTimer:null,
            error:'',
            layoutReady:false,
            editMode:false,
            defaultLayoutRef: JSON.parse(JSON.stringify(defaultLayout)),
            layout: (()=>{
                const saved = JSON.parse(localStorage.getItem('reciboLayout') || 'null');
                const base = JSON.parse(JSON.stringify(defaultLayout));
                if (saved && typeof saved === 'object') {
                    Object.keys(saved).forEach(k=>{
                        base[k] = Object.assign(base[k]||{}, saved[k]||{});
                    });
                }
                return base;
            })(),
            dragging:null, dragRef:null, dragStart:{x:0,y:0}, orig:{x:0,y:0},
            _moveB:null, _upB:null, _moveTouchB:null,
            resizing:false, resizeKey:null, resizeStart:{x:0,w:0}, _resizeB:null, _resizeTouchB:null,
            moneda(v){ return new Intl.NumberFormat('es-MX',{style:'currency',currency:'MXN'}).format(v||0) },
            mesEnCurso(){ return new Date().toLocaleDateString('es-MX',{month:'long'}).charAt(0).toUpperCase() + new Date().toLocaleDateString('es-MX',{month:'long'}).slice(1) },
            mesEnCursoCompleto(){ const d = this.ref.created_at ? new Date(this.ref.created_at) : new Date(); return d.toLocaleDateString('es-MX',{month:'long'})+' de '+d.getFullYear() },
            fecha(){ const d = this.ref.created_at ? new Date(this.ref.created_at) : new Date(); return d.toLocaleDateString('es-MX',{weekday:'long',year:'numeric',month:'long',day:'numeric'}) },
            hora(){ const d = this.ref.created_at ? new Date(this.ref.created_at) : new Date(); return d.toLocaleTimeString('es-MX') },
            mesFinalCobertura(meses){
                if(!meses) return '';
                const d = this.ref.created_at ? new Date(this.ref.created_at) : new Date();
                d.setMonth(d.getMonth() + Number(meses));
                const mes = d.toLocaleDateString('es-MX', { month: 'long' });
                const year = d.getFullYear();
                return `${mes} de ${year}`;
            },
            fechaLocal(d){ try{ if(!d) return ''; const dt=new Date(d); return dt.toLocaleDateString('es-MX',{year:'numeric',month:'long',day:'numeric'});}catch(_){ return String(d) } },
            toggleEditor(){
                this.editMode = !this.editMode;
                if(this.editMode){
                    this._moveB = this._moveHandler.bind(this);
                    this._upB = this._upHandler.bind(this);
                    this._moveTouchB = this._moveTouchHandler.bind(this);
                    this._resizeB = this._resizeHandler.bind(this);
                    this._resizeTouchB = this._resizeTouchHandler.bind(this);
                    document.addEventListener('mousemove', this._moveB);
                    document.addEventListener('mouseup', this._upB);
                    document.addEventListener('touchmove', this._moveTouchB, {passive:false});
                    document.addEventListener('touchend', this._upB);
                    document.addEventListener('mousemove', this._resizeB);
                    document.addEventListener('touchmove', this._resizeTouchB, {passive:false});
                }else{
                    document.removeEventListener('mousemove', this._moveB);
                    document.removeEventListener('mouseup', this._upB);
                    document.removeEventListener('touchmove', this._moveTouchB);
                    document.removeEventListener('touchend', this._upB);
                    document.removeEventListener('mousemove', this._resizeB);
                    document.removeEventListener('touchmove', this._resizeTouchB);
                    this._moveB=this._upB=this._moveTouchB=null;
                    this._resizeB=this._resizeTouchB=null;
                    this.saveLayout();
                }
            },
            resetLayout(){ this.layout = JSON.parse(JSON.stringify(this.defaultLayoutRef)); this.saveLayout(); },
            saveAsDefault(){
                // Guardar en local
                localStorage.setItem('reciboLayoutDefault', JSON.stringify(this.layout));
                this.defaultLayoutRef = JSON.parse(JSON.stringify(this.layout));
                
                // Guardar en servidor
                this.saveServerLayout();
                this.editMode = false;
            },
            layoutSaveDebounced(){
                if(this.layoutSaveTimer){ clearTimeout(this.layoutSaveTimer); }
                this.layoutSaveTimer = setTimeout(()=>{
                    this.saveServerLayout();
                    this.layoutSaveTimer = null;
                }, 500);
            },
            async saveServerLayout(){
                try{
                    const token = document.querySelector('meta[name=csrf-token]')?.getAttribute('content') || '';
                    await fetch('{{ route('admin.pagos.layout.store') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': token
                        },
                        body: JSON.stringify({ layout: this.layout })
                    });
                }catch(_){}
            },
            async loadServerLayout(){
                try{
                    const r = await fetch('{{ route('admin.pagos.layout.get') }}', {
                        headers: {'Accept': 'application/json'}
                    });
                    const j = await r.json();
                    if(j.ok && j.layout){
                        this.layout = j.layout;
                        this.defaultLayoutRef = JSON.parse(JSON.stringify(j.layout));
                        localStorage.setItem('reciboLayoutDefault', JSON.stringify(j.layout));
                        localStorage.setItem('reciboLayout', JSON.stringify(j.layout));
                    }
                }catch(_){}
                this.layoutReady = true;
            },
            saveLayout(){ localStorage.setItem('reciboLayout', JSON.stringify(this.layout)); },
            async init(){
                await this.loadServerLayout();
                try{
                    const params = new URLSearchParams(window.location.search);
                    const folio = Number(params.get('folio') || '');
                    this.readOnlyMode = (params.get('readonly') || '') === '1';
                    const asTicket = (params.get('ticket') || '') === '1';
                    if(folio && folio>0){
                        (async ()=>{
                            try{
                                const r = await fetch('{{ route('admin.pagos.facturas.by_folio', ['ref'=>'__REF__']) }}'.replace('__REF__', folio),{headers:{'Accept':'application/json'}});
                                const j = await r.json();
                                if(!r.ok || !j?.ok){ return }
                                const d = j.data;
                                this.ref.numero = d.reference_number;
                                this.ref.id = d.id;
                                this.ref.created_at = d.created_at || null;
                                this.form.numero = d.numero_servicio || '';
                                const p = d.payload || {};
                                this.datos.nombre = p.nombre || '';
                                this.datos.mensualidad = Number(p.mensualidad)||0;
                                this.form.recargo = p.recargo || 'no';
                                this.form.pago_anterior = p.pago_anterior || 0;
                                this.form.metodo = p.metodo || '';
                                this.form.cobro = p.cobro || '';
                                this.form.otro = p.otro || 'no';
                                this.form.prepay = p.prepay || 'no';
                                this.form.prepay_months = p.prepay_months || null;
                                this.form.prepay = p.prepay || 'no';
                                this.form.prepay_months = p.prepay_months || null;
                                this.form.prepay = p.prepay || 'no';
                                this.form.prepay_months = p.prepay_months || null;
                                this.totales.total = Number(d.total) || 0;
                                this.totales.letra = toWords(this.totales.total);
                                if(asTicket){
                                    // Pequeña espera para asegurar layoutReady y cómputos
                                    await new Promise(r=>setTimeout(r,50));
                                    this.printThermal();
                                }else{
                                    await this.doPrintOnce();
                                }
                            }catch(_){}
                        })();
                    }
                }catch(_){}
                // cargar config de pago adelantado
                try{
                    const r = await fetch('{{ route('admin.dashboard.prepay.settings') }}', { headers:{'Accept':'application/json'} });
                    const j = await r.json();
                    if(j?.ok){ this.prepayConfig = j; }
                }catch(_){}
            },
            async ensurePrintableReady(){
                const sheet = document.querySelector('.print-sheet');
                if(sheet){
                    sheet.style.transform = 'none';
                    sheet.style.display = 'block';
                    sheet.style.visibility = 'visible';
                }
                const isVisible = (el)=>{
                    if(!el) return false;
                    const st = getComputedStyle(el);
                    return st.display!=='none' && st.visibility!=='hidden' && el.offsetWidth>0 && el.offsetHeight>0;
                };
                let tries = 0;
                while((!this.layoutReady || !isVisible(sheet)) && tries < 200){
                    tries++;
                    await new Promise(r=>setTimeout(r,25));
                }
                if(!this.layoutReady || !isVisible(sheet)){
                    // último intento: forzar visibilidad
                    if(sheet){
                        sheet.style.display = 'block';
                        sheet.style.visibility = 'visible';
                    }
                    await new Promise(r=>setTimeout(r,50));
                    if(!sheet || !isVisible(sheet)){
                        throw new Error('print-sheet no está listo/visible');
                    }
                }
                await this.$nextTick();
                await this.waitForImages();
                if(document.fonts && document.fonts.ready){
                    try{ await document.fonts.ready }catch(_){}
                }
                await new Promise(r=>setTimeout(r,120));
            },
            async doPrintOnce(){
                if(this.isPrinting) return;
                this.isPrinting = true;
                try{
                    await this.ensurePrintableReady();
                    const sheet = document.querySelector('.print-sheet');
                    const w = window.open('', '_blank', 'width=900,height=700');
                    if(!w){ this.isPrinting=false; return; }
                    // Copiar estilos actuales (incluye Tailwind inyectado por Vite y estilos inline)
                    const styleNodes = Array.from(document.querySelectorAll('style,link[rel="stylesheet"]'));
                    const headFrag = styleNodes.map(node=>{
                        if(node.tagName.toLowerCase()==='style'){
                            return `<style>${node.textContent||''}</style>`;
                        }else{
                            const href = node.getAttribute('href');
                            if(href){ return `<link rel="stylesheet" href="${href}">`; }
                            return '';
                        }
                    }).join('\n');
                    // HTML a imprimir: clon literal de la hoja
                    const html = `
<!doctype html>
<html>
<head>
<meta charset="utf-8">
${headFrag}
<style>
@page{size:A4;margin:0}
html,body{-webkit-print-color-adjust:exact;print-color-adjust:exact;margin:0;padding:0;background:#fff}
.print-sheet{position:relative;width:210mm;height:297mm;margin:0 auto;transform:none}
</style>
</head>
<body>${sheet ? sheet.outerHTML : ''}</body>
</html>`;
                    w.document.open(); w.document.write(html); w.document.close();
                    // Esperar imágenes y fuentes en el popup
                    const waitReady = async ()=>{
                        const imgs = Array.from(w.document.images);
                        await Promise.all(imgs.map(img=>img.complete?Promise.resolve():new Promise(res=>{
                            img.addEventListener('load',res,{once:true});
                            img.addEventListener('error',res,{once:true});
                        })));
                        if(w.document.fonts && w.document.fonts.ready){
                            try{ await w.document.fonts.ready }catch(_){}
                        }
                    };
                    try{ await waitReady(); }catch(_){}
                    setTimeout(()=>{ try{ w.focus(); w.print(); } finally { setTimeout(()=>{ try{ w.close(); }catch(_){} }, 250); this.isPrinting=false; } }, 80);
                }catch(e){
                    this.isPrinting=false;
                    this.error = 'No se pudo preparar la impresión. Intenta de nuevo.';
                }
            },
            async waitForImages(){
                const sheet = document.querySelector('.print-sheet');
                if(!sheet) return;
                const imgs = Array.from(sheet.querySelectorAll('img'));
                await Promise.all(imgs.map(img=>{
                    if(img.complete) return Promise.resolve();
                    return new Promise(res=>{
                        img.addEventListener('load', res, {once:true});
                        img.addEventListener('error', res, {once:true});
                    });
                }));
            },
            startDrag(key, e){
                if(!this.editMode) return;
                if(this.resizing) return; 
                this.dragging = key;
                this.dragRef = this.$refs.sheet;
                this.dragStart = { x: e.clientX, y: e.clientY };
                this.orig = { x: this.layout[key].left, y: this.layout[key].top };
            },
            startResize(key, e){
                if(!this.editMode) return;
                this.resizing = true;
                this.resizeKey = key;
                this.resizeStart = { x: e.clientX, w: this.layout[key].w };
            },
            _moveHandler(ev){
                if(!this.dragging || !this.dragRef) return;
                ev.preventDefault();
                const dx = ev.clientX - this.dragStart.x;
                const dy = ev.clientY - this.dragStart.y;
                this.layout[this.dragging].left = Math.max(0, this.orig.x + dx);
                this.layout[this.dragging].top = Math.max(0, this.orig.y + dy);
            },
            _moveTouchHandler(tev){
                const t = tev.touches && tev.touches[0];
                if(!t || !this.dragging) return;
                this._moveHandler({ clientX:t.clientX, clientY:t.clientY, preventDefault: () => {} });
            },
            _resizeHandler(ev){
                if(!this.resizing || !this.resizeKey) return;
                ev.preventDefault();
                const dx = ev.clientX - this.resizeStart.x;
                const min = this.resizeKey==='logo' ? 200 : (this.resizeKey==='mes' ? 120 : 60);
                const max = 780; // límite de ancho hoja
                let w = this.resizeStart.w + dx;
                w = Math.max(min, Math.min(max, w));
                this.layout[this.resizeKey].w = w;
            },
            _resizeTouchHandler(tev){
                const t = tev.touches && tev.touches[0];
                if(!t || !this.resizing) return;
                this._resizeHandler({ clientX:t.clientX, preventDefault: () => {} });
            },
            _upHandler(){
                if(this.resizing){ this.resizing=false; this.resizeKey=null; this.saveLayout(); return; }
                this.dragging=null; this.dragRef=null; this.saveLayout(); this.layoutSaveDebounced();
            },
            recalcular(){
                if(this.readOnlyMode && this.ref.id) return;
                const mensualidad = Number(this.datos.mensualidad)||0;
                const rec = this.form.recargo==='si'?50:0;
                let total = 0;
                
                if(this.form.prepay === 'si'){
                    const months = Number(this.form.prepay_months||6);
                    const pkg = mensualidad;
                    
                    // Para meses 1-5: sin descuento, solo multiplicar
                    if(months <= 5){
                        this.totales.prepay_total = Math.round((pkg * months) * 100) / 100;
                    }else{
                        // Para meses 6-12: mantener lógica actual de descuentos
                        const info = this.prepayConfig?.matrix?.[months];
                        const totals = info?.totals || {};
                        const expected = totals[String(pkg)] ?? totals[pkg];
                        if(expected !== undefined){
                            this.totales.prepay_total = Number(expected);
                        }else{
                            const percent = Number(info?.percent||0);
                            const base = mensualidad * months;
                            this.totales.prepay_total = Math.round((base * (1 - percent/100)) * 100) / 100;
                        }
                    }
                    total = this.totales.prepay_total;
                }else{
                    if (this.adeudo && this.adeudo.meses > 0) {
                        const base = mensualidad * this.adeudo.meses;
                        const pagado = this.adeudo.pagado_parcial || 0;
                        total = Math.round((Math.max(0, base - pagado) + rec) * 100) / 100;
                    } else {
                        total = mensualidad + rec;
                    }
                }
                
                this.totales.total = total;
                this.totales.letra = toWords(this.totales.total);
            },
            async inputChanged(){
                if(this.readOnlyMode) return;
                this.ref = { numero: null, id: null, created_at: null };
                this.recalcular();
            },
            async fetchAdeudo(){
                this.adeudo = null;
                const numero = String(this.form.numero||'').trim();
                if(!numero) return;
                try{
                    const r = await fetch('{{ route('admin.pagos.deuda') }}?numero='+encodeURIComponent(numero), { headers:{'Accept':'application/json'} });
                    const j = await r.json();
                    if(r.ok && j?.ok){
                        const m = j.pendiente||0;
                        const meses = j.meses_adeudo||0;
                        if(isFinite(m) && m>0 && meses>0){
                            this.adeudo = {
                                desde_periodo: j.desde_periodo,
                                desde_label: j.desde_mes_label || '',
                                meses: meses,
                                pendiente: Math.max(0, Number(m)||0),
                                recargo: Number(j.recargo||0),
                                pagado_parcial: Number(j.pagado_parcial||0)
                            };
                            // Sincronizar el recargo del formulario con el del servidor si hay adeudo y no ha pagado este mes
                            if (!this.pagadoMesActual) {
                                this.form.recargo = this.adeudo.recargo > 0 ? 'si' : 'no';
                            }
                        } else {
                            this.adeudo = { desde_periodo:j.desde_periodo, desde_label:j.desde_mes_label||'', meses:meses, pendiente:0, recargo:Number(j.recargo||0), pagado_parcial:0 };
                        }
                        this.recalcular();
                    }
                }catch(_){}
            },
            openDiscountModal(){
                this.discountModalOpen = true;
                this.discountAmount = 0;
            },
            applyDiscount(){
                const discount = Number(this.discountAmount) || 0;
                if (discount <= 0) {
                    this.discountModalOpen = false;
                    return;
                }
                
                // Aplicar el descuento al total
                this.totales.total = Math.max(0, this.totales.total - discount);
                this.totales.letra = toWords(this.totales.total);
                
                // Guardar el descuento aplicado de forma persistente
                this.appliedDiscount = discount;
                
                // Cerrar el modal y resetear solo el campo temporal
                this.discountModalOpen = false;
                this.discountAmount = 0;
            },
            refNumberPad(){
                const n = this.ref.numero;
                if(!n) return '';
                return String(n).padStart(8,'0');
            },
            async fetchPagoAnterior(){
                this.pagoAnteriorFecha = '';
                try{
                    const r = await fetch('{{ route('admin.pagos.prev') }}?numero='+encodeURIComponent(this.form.numero), { headers:{'Accept':'application/json'} });
                    const j = await r.json();
                    if(r.ok && j?.ok){
                        this.form.pago_anterior = Number(j.data.monto)||0;
                        this.pagoAnteriorFecha = this.fechaLocal(j.data.fecha || j.data.created_at);
                        // Auto-recargo a partir del día 8 si NO hay pago de este mes
                        const now = new Date();
                        const day = now.getDate();
                        let paidThisMonth = false;
                        try {
                            const paidDate = j.data.created_at ? new Date(j.data.created_at) : (j.data.fecha ? new Date(j.data.fecha) : null);
                            if (paidDate) {
                                paidThisMonth = (paidDate.getFullYear() === now.getFullYear() && paidDate.getMonth() === now.getMonth());
                            }
                        } catch(_) {}
                        this.pagadoMesActual = paidThisMonth;
                        if (day >= 8 && !paidThisMonth) {
                            this.form.recargo = 'si';
                        } else if (day < 8) {
                            this.form.recargo = 'no';
                        }
                    }else{
                        this.form.pago_anterior = 0;
                        this.pagoAnteriorFecha = '';
                        this.pagadoMesActual = false;
                        // Si no hay pagos previos y estamos después del día 7, aplicar recargo
                        const now = new Date();
                        if (now.getDate() >= 8) {
                            this.form.recargo = 'si';
                        } else {
                            this.form.recargo = 'no';
                        }
                    }
                }catch(_){
                    this.form.pago_anterior = 0;
                    this.pagoAnteriorFecha = '';
                    this.pagadoMesActual = false;
                    const now = new Date();
                    if (now.getDate() >= 8) {
                        this.form.recargo = 'si';
                    } else {
                        this.form.recargo = 'no';
                    }
                }
                this.recalcular();
                await this.fetchAdeudo();
            },
            metodoValido(){
                const m = String(this.form.metodo || '').trim();
                if(!m){
                    this.metodoModalOpen = true;
                    return false;
                }
                return true;
            },
            openConfirm(type = 'receipt'){ 
                this.printType = type;
                this.saveConfirmOpen = true;
            },
            async confirmSaveYes(){
                if(this.isSaving) return;
                this.isSaving = true;
                try {
                    if (!this.ref || !this.ref.id) {
                        await this.emitirFactura();
                    }
                    
                    if (this.printType === 'ticket') {
                        await this.printThermal();
                    } else {
                        await this.doPrintOnce();
                    }
                } catch(e) {
                    console.error(e);
                } finally {
                    this.isSaving = false;
                    this.saveConfirmOpen = false;
                }
            },
            confirmSaveNo(){
                if(this.printTimerId){ clearTimeout(this.printTimerId); this.printTimerId=null; }
                this.saveConfirmOpen = false;
            },
            
            async reimprimir(id){
                try{
                    const r = await fetch('{{ route('admin.pagos.facturas.show', ['id'=>'__ID__']) }}'.replace('__ID__', id),{headers:{'Accept':'application/json'}});
                    const j = await r.json();
                    if(r.ok && j?.ok){
                        const d = j.data;
                        this.ref.numero = d.reference_number;
                        this.ref.id = d.id;
                        this.ref.created_at = d.created_at || null;
                        this.form.numero = d.numero_servicio || '';
                        const p = d.payload || {};
                        this.datos.nombre = p.nombre || '';
                        this.datos.mensualidad = Number(p.mensualidad)||0;
                                this.form.recargo = p.recargo || 'no';
                                this.form.pago_anterior = p.pago_anterior || 0;
                                this.form.metodo = p.metodo || '';
                                this.form.cobro = p.cobro || '';
                                this.form.otro = p.otro || 'no';
                                this.form.prepay = p.prepay || 'no';
                                this.form.prepay_months = p.prepay_months || null;
                                this.totales.total = Number(d.total) || 0;
                        this.totales.letra = toWords(this.totales.total);
                        await this.doPrintOnce();
                    }
                }catch(_){}
            },
            async emitirFactura(){
                try{
                    const token = document.querySelector('meta[name=csrf-token]')?.getAttribute('content') || '';
                    const r = await fetch('{{ route('admin.pagos.facturas.store') }}', {
                        method:'POST',
                        headers:{
                            'Content-Type':'application/json',
                            'Accept':'application/json',
                            'X-CSRF-TOKEN': token
                        },
                        body: JSON.stringify({
                            numero_servicio: this.form.numero || null,
                            usuario_id: null,
                            total: this.totales.total || 0,
                            payload: {
                                nombre: this.datos.nombre,
                                mensualidad: this.datos.mensualidad,
                                recargo: this.form.recargo,
                                prepay: this.form.prepay,
                                prepay_months: this.form.prepay==='si'? this.form.prepay_months : null,
                                prepay_total: this.form.prepay==='si'? this.totales.total : null,
                                pago_anterior: this.form.pago_anterior,
                                metodo: this.form.metodo,
                                cobro: this.form.cobro,
                                otro: this.form.otro,
                                fecha: this.fecha(),
                                hora: this.hora()
                            }
                        })
                    });
                    const j = await r.json();
                    if(r.ok && j?.ok){
                        this.ref.numero = j.referencia;
                        this.ref.id = j.id;
                        this.ref.created_at = new Date().toISOString();
                        await this.fetchPagoAnterior();
                        await this.fetchAdeudo();
                        // Actualizar el estado del cliente después del pago
                        await this.buscar();
                    }
                }catch(_){}
            },
            async prepareAndPrint(){
                try{
                    const token = document.querySelector('meta[name=csrf-token]')?.getAttribute('content') || '';
                    const r = await fetch('{{ route('admin.pagos.facturas.store') }}', {
                        method:'POST',
                        headers:{
                            'Content-Type':'application/json',
                            'Accept':'application/json',
                            'X-CSRF-TOKEN': token
                        },
                        body: JSON.stringify({
                            numero_servicio: this.form.numero || null,
                            usuario_id: null,
                            total: this.totales.total || 0,
                            payload: {
                                nombre: this.datos.nombre,
                                mensualidad: this.datos.mensualidad,
                                recargo: this.form.recargo,
                                prepay: this.form.prepay,
                                prepay_months: this.form.prepay==='si'? this.form.prepay_months : null,
                                prepay_total: this.form.prepay==='si'? this.totales.total : null,
                                pago_anterior: this.form.pago_anterior,
                                metodo: this.form.metodo,
                                cobro: this.form.cobro,
                                otro: this.form.otro,
                                fecha: this.fecha(),
                                hora: this.hora()
                            }
                        })
                    });
                    const j = await r.json();
                    if(r.ok && j?.ok){
                        this.ref.numero = j.referencia;
                        this.ref.id = j.id;
                        this.ref.created_at = new Date().toISOString();
                    }
                }catch(_){}
                await this.doPrintOnce();
            },
            async printThermal(){
                if(!this.ref || !this.ref.id){
                    await this.emitirFactura();
                }
                const w = window.open('', '_blank', 'width=400,height=700');
                if(!w) return;
                const logo = '{{ asset('images/logo.png') }}';
                const banner = '{{ asset('images/reportes.png') }}';
                const nombre = this.datos.nombre || '—';
                const id = this.form.numero || '—';
                const mes = this.mesEnCursoCompleto();
                const otros = '—';
                const importe = this.moneda(0);
                const recargo = this.form.recargo === 'si' ? 'SI' : 'NO';
                const totalNum = this.moneda(this.totales.total);
                const totalLetra = this.totales.letra || '';
                const metodo = this.form.metodo || '—';
                const cobro = this.form.cobro || '—';
                const fecha = this.fecha();
                const hora = this.hora();
                const folio = this.refNumberPad();
                const prepayLabel = this.form.prepay === 'si' ? 'SÍ' : 'NO';
                const prepayLine = this.form.prepay === 'si' ? `<div class="line"><div class="l">Meses adelantados</div><div>${this.form.prepay_months} (hasta ${this.mesFinalCobertura(this.form.prepay_months)})</div></div><div class="sep"></div>` : '';
                const discountLine = this.appliedDiscount > 0 ? `<div class="line"><div class="l">Descuento</div><div>${this.moneda(this.appliedDiscount)}</div></div>` : '';
                const html = `
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Recibo</title>
<style>
@page{ size:80mm auto; margin:0 }
html,body{ margin:0; padding:0 }
.ticket{ width:80mm; max-width:80mm; padding:8px 10px; font-family: Arial, sans-serif; font-size:12px; color:#111; }
.logo{ text-align:center; margin-bottom:6px }
.logo img{ max-width:70mm; height:auto }
.banner{ text-align:center; margin-top:8px }
.banner img{ max-width:70mm; height:auto }
.center{ text-align:center }
.title{ font-weight:700; font-size:14px; margin:6px 0 }
.line{ display:flex; justify-content:space-between; gap:8px; margin:2px 0 }
.line .l{ font-weight:600 }
.sep{ border-top:1px dotted #555; margin:6px 0 }
.folio{ font-weight:700; font-size:12px; margin-bottom:4px }
</style>
</head>
<body>
<div class="ticket">
  <div class="logo"><img src="${logo}" onerror="this.style.display='none'"></div>
  ${folio ? `<div class="folio">Folio: ${folio}</div>` : ''}
  <div class="title center">Recibo de pago</div>
  <div class="line"><div class="l">ID</div><div>${id}</div></div>
  <div class="line"><div class="l">Nombre</div><div>${nombre}</div></div>
  <div class="line"><div class="l">Mes</div><div>${mes}</div></div>
  <div class="line"><div class="l">Otros</div><div>${otros}</div></div>
  <div class="line"><div class="l">Importe</div><div>${importe}</div></div>
  <div class="line"><div class="l">Descuento</div><div>${this.appliedDiscount > 0 ? this.moneda(this.appliedDiscount) : '—'}</div></div>
  <div class="line"><div class="l">Recargo</div><div>${recargo}</div></div>
  <div class="line"><div class="l">Pago por adelantado</div><div>${prepayLabel}</div></div>
  <div class="sep"></div>
  ${prepayLine}
  <div class="line"><div class="l">Total (número)</div><div>${totalNum}</div></div>
  <div class="line"><div class="l">Total (letra)</div><div style="max-width:42mm;text-align:right">${totalLetra}</div></div>
  <div class="sep"></div>
  <div class="line"><div class="l">Método de pago</div><div>${metodo}</div></div>
  <div class="line"><div class="l">Quién cobró</div><div>${cobro}</div></div>
  <div class="line"><div class="l">Fecha</div><div>${fecha}</div></div>
  <div class="line"><div class="l">Hora</div><div>${hora}</div></div>
  <div class="sep"></div>
  <div class="center" style="font-size:10px; font-weight:600; color:#555; margin-top:6px; padding-top:6px; border-top:1px solid #999;">
    Recuerda que del 1 al 7 de mes se realizan los pagos correctamente, posterior a eso se cobrarán cargos por costo de reconexión.
  </div>
  <div class="banner"><img src="${banner}" onerror="this.style.display='none'"></div>
</div>
<script>
(function(){
  function waitImages(){
    const imgs = Array.from(document.images);
    return Promise.all(imgs.map(img => img.complete ? Promise.resolve() : new Promise(res => {
      img.addEventListener('load', res, {once:true});
      img.addEventListener('error', res, {once:true});
    })));
  }
  (async function(){
    try{ await waitImages(); }catch(_){}
    setTimeout(function(){ try{ window.print(); } finally { setTimeout(function(){ window.close && window.close(); }, 300); } }, 50);
  })();
})();
<\/script>
</body>
</html>`;
                w.document.open();
                w.document.write(html);
                w.document.close();
            },
            async buscar(){
                this.error='';
                if(!this.form.numero){ this.error='Ingresa el ID'; return }
                
                // Reset states for the new client search
                this.ref = { numero: null, id: null, created_at: null };
                this.adeudo = null;
                this.pagadoMesActual = false;
                this.pagoAnteriorFecha = '';
                this.appliedDiscount = 0; // Resetear descuento aplicado
                this.datos = { nombre: '', mensualidad: 0 };
                this.form.recargo = 'no';
                this.form.pago_anterior = 0;
                this.form.metodo = '';
                this.form.prepay = 'no';
                this.form.otro = 'no';
                
                try{
                    const r = await fetch('{{ route('admin.pagos.lookup') }}?numero='+encodeURIComponent(this.form.numero));
                    const j = await r.json();
                    if(!j.ok){ this.error = j.message || 'No encontrado'; this.datos={nombre:'',mensualidad:0}; this.recalcular(); return }
                    this.datos.nombre = j.data.nombre_cliente || '';
                    const rawTarifa = j.data.tarifa ?? '';
                    const numTarifa = Number(String(rawTarifa).replace(/[^\d.]/g, '')) || 0;
                    const pkg = Number(String(j.data.paquete ?? '').replace(/[^\d]/g,'')) || 0;
                    this.datos.mensualidad = numTarifa || pkg || 0;
                    this.recalcular();
                    await this.fetchPagoAnterior();
                    await this.fetchAdeudo();
                }catch(e){
                    this.error='Error de conexión';
                }
            },
        }
    }
    </script>
</x-app-layout>
