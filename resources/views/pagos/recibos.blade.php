<x-app-layout title="Recibos">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            {{ __('Recibos') }}
        </h2>
    </x-slot>

    <div class="py-6" x-data="pagosRecibo()" x-init="init && init()">
        <div class="max-w-6xl mx-auto sm:px-4 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 not-print">
                        <div class="md:col-span-2 grid grid-cols-2 gap-3">
                            <div class="col-span-2">
                                <label class="text-xs uppercase text-gray-500 dark:text-gray-400">Buscar por</label>
                                <div class="text-sm font-semibold">ID</div>
                            </div>
                            <div class="col-span-2">
                                <label for="numero" class="text-xs uppercase text-gray-500 dark:text-gray-400">Ingresa el ID</label>
                                <div class="relative">
                                   
                                    <input id="numero" type="number" class="form-input pl-10 mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" x-model.trim="form.numero" @change="buscar()" @keydown.enter.prevent="buscar()">
                                </div>
                                <p class="text-xs text-red-500 mt-1" x-text="error" x-show="error"></p>
                            </div>
                            <div class="col-span-2 grid grid-cols-2 gap-3">
                                      <div>
                                    <label class="text-xs uppercase text-gray-500 dark:text-gray-400">Recargo</label>
                                    <select class="form-select mt-1 w-full" x-model="form.recargo" @change="recalcular()">
                                        <option value="no">No</option>
                                        <option value="si">Sí</option>
                                    </select>
                                </div>
                                    <select class="hidden" x-model="form.recargo"><option value="no">No</option><option value="si">Sí</option></select>
                                </div>
                                <div>
                                    <label class="text-xs uppercase text-gray-500 dark:text-gray-400">Pago anterior</label>
                                    <div class="relative mt-1">
                                        <input type="number" step="1" class="form-input pl-7 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" placeholder="0.00" x-model.number="form.pago_anterior" @input="recalcular()">
                                    </div>
                                </div>
                                <div>
                                    <label class="text-xs uppercase text-gray-500 dark:text-gray-400">Método de pago</label>
                                    <select class="form-select mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" x-model="form.metodo">
                                        <option value="">Selecciona</option>
                                        <option value="Tarjeta de Crédito">Tarjeta de Crédito</option>
                                        <option value="Cheque">Cheque</option>
                                        <option value="Deposito a cuenta">Deposito a cuenta</option>
                                        <option value="Efectivo">Efectivo</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="text-xs uppercase text-gray-500 dark:text-gray-400">Quién cobró</label>
                                    <div class="relative mt-1">
                                        <input type="text" class="form-input pl-10 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"  x-model="form.cobro">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-end justify-end md:justify-start gap-2 not-print">
                            <a class="btn btn-secondary" href="{{ route('pagos.recibos.historial') }}">Historial</a>
                            <!-- <button class="btn btn-secondary" @click="toggleEditor()"
                                x-text="editMode ? 'Cerrar editor de plantilla' : 'Editar plantilla'"></button>
                            <button class="btn btn-secondary" @click="resetLayout()">Restablecer</button> -->
                            <!-- <button class="btn btn-secondary" @click="saveAsDefault()">Guardar como predeterminado</button> -->
                            <button class="btn btn-danger" @click="openConfirm()">Exportar a PDF</button>
                        </div>
                    </div>

                    <div class="mt-6 print-sheet" x-ref="sheet" x-show="layoutReady" x-cloak>
                        <div x-show="saveConfirmOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 not-print">
                            <div class="bg-white dark:bg-gray-800 rounded shadow p-6 w-96">
                                <div class="text-lg font-semibold mb-4">¿Guardar información?</div>
                                <div class="flex justify-end gap-2">
                                    <button class="btn btn-secondary" @click="confirmSaveNo()">No</button>
                                    <button class="btn btn-primary" @click="confirmSaveYes()">Sí</button>
                                </div>
                            </div>
                        </div>
                        <template x-if="editMode">
                            <div class="text-xs text-gray-500 mb-2 not-print">Arrastra las imágenes para acomodarlas. Se guardará tu plantilla.</div>
                        </template>
                        <!-- Capa de imágenes única para toda la hoja -->
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
                                <div>Otros</div><div>—</div>
                                <div>Importe</div><div x-text="moneda(0)"></div>
                                <div>Recargo</div><div x-text="form.recargo === 'si' ? 'SI' : 'NO'"></div>
                                <div>Costo de reconexión</div><div x-text="form.recargo === 'si' ? moneda(50) : moneda(0)"></div>
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
                                <div>Otros</div><div>—</div>
                                <div>Importe</div><div x-text="moneda(0)"></div>
                                <div>Recargo</div><div x-text="form.recargo === 'si' ? 'SI' : 'NO'"></div>
                                <div>Costo de reconexión</div><div x-text="form.recargo === 'si' ? moneda(50) : moneda(0)"></div>
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
        .print-sheet{position:relative;width:210mm;max-width:none;margin:0;transform:none;height:297mm;background:#fff}
        .sheet-abs{position:absolute;inset:0;z-index:20;pointer-events:none}
        .receipt{position:relative;height:calc((297mm - 0.6mm)/2);border:1px solid #d1d5db;border-radius:8px;padding:6mm;background:#fff;overflow:hidden}
        .divider-line{height:0.6mm;background:#111;margin:0}
        .client-receipt{padding-top:8mm}
        .ref-number{position:absolute;top:2mm;left:6mm;font-weight:700;font-size:12px;color:#111;z-index:30}
        .id-band{background:#fde047;border:1px solid #eab308;border-radius:4px;padding:4px 8px;display:inline-flex;gap:10px;margin:10px 0;width:fit-content;max-width:60%}
        .receipt-grid{display:grid;grid-template-columns:1fr 1fr;gap:6px 20px;font-size:0.95rem}
        .receipt-head img{max-height:120px;object-fit:contain}
        .logo-center{display:inline-block;max-width:680px;width:90%}
        .abs-img,.abs-text{position:absolute;pointer-events:none}
        .draggable{outline:1px dashed #888;pointer-events:auto;cursor:move;background:transparent}
        .text-box{max-width:680px}
        .resize-handle{position:absolute;right:-6px;bottom:-6px;width:14px;height:14px;background:#fff;border:1px solid #666;border-radius:2px;box-shadow:0 0 0 2px rgba(255,255,255,.6);cursor:se-resize}
        @page{size:A4;margin:0}
        html,body{-webkit-print-color-adjust:exact;print-color-adjust:exact}
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
            form:{ numero:'', recargo:'no', pago_anterior:0, metodo:'', cobro:'' },
            pagoAnteriorFecha:'',
            datos:{ nombre:'', mensualidad:0 },
            totales:{ total:0, letra:'' },
            ref:{ numero:null, id:null },
            saveConfirmOpen:false,
            isPrinting:false,
            historial:[],
            printTimerId:null,
            error:'',
            editMode:false,
            layoutReady:false,
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
            mesEnCursoCompleto(){ const d=new Date(); return d.toLocaleDateString('es-MX',{month:'long'})+' de '+d.getFullYear() },
            fecha(){ return new Date().toLocaleDateString('es-MX',{weekday:'long',year:'numeric',month:'long',day:'numeric'}) },
            hora(){ return new Date().toLocaleTimeString('es-MX') },
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
                localStorage.setItem('reciboLayoutDefault', JSON.stringify(this.layout));
                this.defaultLayoutRef = JSON.parse(JSON.stringify(this.layout));
            },
            async loadServerLayout(){
                try{
                    const r = await fetch('{{ route('pagos.recibos.layout.get') }}', {
                        headers: {'Accept': 'application/json'}
                    });
                    const j = await r.json();
                    if(j.ok && j.layout){
                        this.layout = j.layout;
                        this.defaultLayoutRef = JSON.parse(JSON.stringify(j.layout));
                        // Actualizar local para este usuario también
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
                    if(folio && folio>0){
                        (async ()=>{
                            try{
                                const r = await fetch('{{ route('pagos.recibos.facturas.by_folio', ['ref'=>'__REF__']) }}'.replace('__REF__', folio),{headers:{'Accept':'application/json'}});
                                const j = await r.json();
                                if(!r.ok || !j?.ok){ return }
                                const d = j.data;
                                this.ref.numero = d.reference_number;
                                this.ref.id = d.id;
                                this.form.numero = d.numero_servicio || '';
                                const p = d.payload || {};
                                this.datos.nombre = p.nombre || '';
                                this.datos.mensualidad = Number(p.mensualidad)||0;
                                this.form.recargo = p.recargo || 'no';
                                this.form.pago_anterior = p.pago_anterior || 0;
                                this.form.metodo = p.metodo || '';
                                this.form.cobro = p.cobro || '';
                                this.recalcular();
                                await this.doPrintOnce();
                            }catch(_){}
                        })();
                    }
                }catch(_){}
            },
            async ensurePrintableReady(){
                const sheet = document.querySelector('.print-sheet');
                if(sheet){ sheet.style.transform = 'none'; }
                const isVisible = (el)=>{
                    if(!el) return false;
                    const st = getComputedStyle(el);
                    return st.display!=='none' && st.visibility!=='hidden' && el.offsetWidth>0 && el.offsetHeight>0;
                };
                let tries = 0;
                while((!this.layoutReady || !isVisible(sheet)) && tries < 50){
                    tries++;
                    await new Promise(r=>setTimeout(r,20));
                }
                if(!this.layoutReady || !isVisible(sheet)){
                    throw new Error('print-sheet no está listo/visible');
                }
                await this.$nextTick();
                await this.waitForImages();
                if(document.fonts && document.fonts.ready){
                    try{ await document.fonts.ready }catch(_){}
                }
                await new Promise(r=>setTimeout(r,50));
            },
            async doPrintOnce(){
                if(this.isPrinting) return;
                this.isPrinting = true;
                const handler = ()=>{ this.isPrinting=false; window.removeEventListener('afterprint', handler); };
                window.addEventListener('afterprint', handler, {once:true});
                try{
                    await this.ensurePrintableReady();
                    setTimeout(()=>window.print(),0);
                }catch(e){
                    this.isPrinting=false;
                    this.error = 'No se pudo preparar la impresión. Intenta de nuevo.';
                }
            },
            async waitForImages(){
                const sheet = document.querySelector('.print-sheet');
                if(!sheet) return;
                const imgs = Array.from(sheet.querySelectorAll('img'));
                const promises = imgs.map(img=>{
                    if(img.complete) return Promise.resolve();
                    return new Promise(res=>{
                        img.addEventListener('load', res, {once:true});
                        img.addEventListener('error', res, {once:true});
                    });
                });
                await Promise.all(promises);
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
                this.dragging=null; this.dragRef=null; this.saveLayout();
            },
            recalcular(){
                const rec = this.form.recargo==='si'?50:0;
                const total = (Number(this.datos.mensualidad)||0) + rec;
                this.totales.total = total;
                this.totales.letra = toWords(total);
            },
            refNumberPad(){
                const n = this.ref.numero;
                if(!n) return '';
                return String(n).padStart(8,'0');
            },
            async fetchPagoAnterior(){
                this.pagoAnteriorFecha = '';
                try{
                    const r = await fetch('{{ route('pagos.recibos.prev') }}?numero='+encodeURIComponent(this.form.numero), { headers:{'Accept':'application/json'} });
                    const j = await r.json();
                    if(r.ok && j?.ok){
                        this.form.pago_anterior = Number(j.data.monto)||0;
                        this.pagoAnteriorFecha = this.fechaLocal(j.data.fecha || j.data.created_at);
                    }else{
                        this.form.pago_anterior = 0;
                        this.pagoAnteriorFecha = '';
                    }
                }catch(_){
                    this.form.pago_anterior = 0;
                    this.pagoAnteriorFecha = '';
                }
                this.recalcular();
            },
            openConfirm(){ this.saveConfirmOpen = true },
            async confirmSaveYes(){
                this.saveConfirmOpen = false;
                await this.emitirFactura();
                await this.doPrintOnce();
            },
            confirmSaveNo(){
                if(this.printTimerId){ clearTimeout(this.printTimerId); this.printTimerId=null; }
                this.saveConfirmOpen = false;
            },
            async reimprimir(id){
                try{
                    const r = await fetch('{{ route('pagos.recibos.facturas.show', ['id'=>'__ID__']) }}'.replace('__ID__', id),{headers:{'Accept':'application/json'}});
                    const j = await r.json();
                    if(r.ok && j?.ok){
                        const d = j.data;
                        this.ref.numero = d.reference_number;
                        this.ref.id = d.id;
                        this.form.numero = d.numero_servicio || '';
                        const p = d.payload || {};
                        this.datos.nombre = p.nombre || '';
                        this.datos.mensualidad = Number(p.mensualidad)||0;
                        this.form.recargo = p.recargo || 'no';
                        this.form.pago_anterior = p.pago_anterior || 0;
                        this.form.metodo = p.metodo || '';
                        this.form.cobro = p.cobro || '';
                        this.recalcular();
                        await this.doPrintOnce();
                    }
                }catch(_){}
            },
            async emitirFactura(){
                try{
                    const token = document.querySelector('meta[name=csrf-token]')?.getAttribute('content') || '';
                    const r = await fetch('{{ route('pagos.recibos.facturas.store') }}', {
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
                                pago_anterior: this.form.pago_anterior,
                                metodo: this.form.metodo,
                                cobro: this.form.cobro,
                                fecha: this.fecha(),
                                hora: this.hora()
                            }
                        })
                    });
                    const j = await r.json();
                    if(r.ok && j?.ok){
                        this.ref.numero = j.referencia;
                        this.ref.id = j.id;
                        await this.fetchPagoAnterior();
                    }
                }catch(_){}
            },
            async prepareAndPrint(){
                // Asegura plantilla más reciente antes de imprimir
                if(this.loadServerLayout){ await this.loadServerLayout(); }
                try{
                    const token = document.querySelector('meta[name=csrf-token]')?.getAttribute('content') || '';
                    const r = await fetch('{{ route('pagos.recibos.facturas.store') }}', {
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
                                pago_anterior: this.form.pago_anterior,
                                metodo: this.form.metodo,
                                cobro: this.form.cobro,
                                fecha: this.fecha(),
                                hora: this.hora()
                            }
                        })
                    });
                    const j = await r.json();
                    if(r.ok && j?.ok){
                        this.ref.numero = j.referencia;
                        this.ref.id = j.id;
                    }
                }catch(_){}
                await this.doPrintOnce();
            },
            async buscar(){
                this.error='';
                if(!this.form.numero){ this.error='Ingresa el ID'; return }
                try{
                    const r = await fetch('{{ route('pagos.recibos.lookup') }}?numero='+encodeURIComponent(this.form.numero));
                    const j = await r.json();
                    if(!j.ok){ this.error = j.message || 'No encontrado'; this.datos={nombre:'',mensualidad:0}; this.recalcular(); return }
                    this.datos.nombre = j.data.nombre_cliente || '';
                    this.datos.mensualidad = Number(j.data.tarifa)||0;
                    this.recalcular();
                    await this.fetchPagoAnterior();
                }catch(e){
                    this.error='Error de conexión';
                }
            }
        }
    }
    </script>
</x-app-layout>
