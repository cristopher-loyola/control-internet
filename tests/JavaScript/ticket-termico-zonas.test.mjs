import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import vm from 'node:vm';

function ticket(view, overrides = {}) {
    const source = readFileSync(new URL(`../../resources/views/${view}/pagos.blade.php`, import.meta.url), 'utf8')
        .replace(/\{\{\s*asset\('([^']+)'\)\s*\}\}/g, '/$1')
        .replace(/\{\{[^]*?\}\}/g, '/test');
    const extract = (name) => {
        const signature = new RegExp(`\\n            (?:async )?${name}\\(`).exec(source);
        assert.ok(signature, `Método ${name} en ${view}`);
        const start = signature.index + '\n            '.length;
        const end = source.indexOf('\n            },', start);
        return source.slice(start, end + '\n            }'.length);
    };
    let html = '';
    let closed = false;
    const events = [];
    const popup = {
        document: { open() {}, write(value) { html = value; }, close() {} },
        location: {}, close() { closed = true; },
    };
    const methods = ['printThermal', 'otroLabel', 'manualReasonForPrint', 'mesSiguientePago', 'adeudoPeriodoLabel', 'mesYearLabel', 'bajaTemporalImporte'];
    const component = vm.runInNewContext(`({${methods.map(extract).join(',')}})`, {
        window: { open() { events.push('open'); return popup; } },
        Blob: class { constructor(parts) { html = parts.join(''); } },
        URL: { createObjectURL: () => 'blob:ticket', revokeObjectURL() {} },
        setTimeout() {}, alert: assert.fail, console: { error: assert.fail },
    });
    Object.assign(component, {
        ref: { id: 840, numero: 840, created_at: '2026-09-30T12:00:00' },
        form: { numero: '8400', prepay: 'si', prepay_months: 1, recargo: 'no', otro: 'no', metodo: 'Efectivo', cobro: 'Cajero de prueba' },
        datos: { nombre: 'Cliente de prueba', mensualidad: 300 },
        totales: { total: 600, prepay_total: 300, letra: 'Seiscientos pesos' },
        prepayNextMonth: true, adeudo: null, adeudoListaMeses: [],
        adeudoCobro: 300, saldoDespues: 0, appliedDiscount: 0,
        moneda: (value) => `$${Number(value).toFixed(2)}`,
        mesEnCursoCompleto: () => 'septiembre de 2026',
        fecha: () => '30 de septiembre de 2026', hora: () => '12:00:00',
        refNumberPad: () => '00000840',
        emitirFactura: async () => { events.push('save'); return false; },
        showPrintFail: () => { events.push('error'); },
        ...overrides,
    });

    return { component, html: () => html, closed: () => closed, events };
}

const scenarios = {
    adelanto: {},
    'adelanto anterior': { prepayNextMonth: false },
    'adelanto con adeudos y descuento': {
        appliedDiscount: 10, adeudoListaMeses: ['julio 2026', 'agosto 2026'],
        adeudo: { meses: 2, desde_periodo: '2026-07' },
    },
    'pago ordinario sin recargo': {
        form: { numero: '8400', prepay: 'no', recargo: 'no', otro: 'no', metodo: 'Efectivo', cobro: 'Cajero de prueba' },
    },
    'pago ordinario con recargo': {
        form: { numero: '8400', prepay: 'no', recargo: 'si', otro: 'no', metodo: 'Efectivo', cobro: 'Cajero de prueba' },
    },
};

for (const zona of ['chivato', 'pozo_hondo', 'rosalito']) {
    for (const [scenario, overrides] of Object.entries(scenarios)) {
        test(`${zona}: ticket de ${scenario} igual al administrador`, async () => {
            const admin = ticket('admin', overrides);
            const zone = ticket(zona, overrides);
            await admin.component.printThermal();
            await zone.component.printThermal();
            // El transporte de impresión difiere en móviles; el documento impreso es el mismo.
            const document = (rendered) => rendered.split('<script>')[0].split('\n').map(line => line.trimEnd()).join('\n');
            assert.equal(document(zone.html()), document(admin.html()));
            assert.match(zone.html(), /size:80mm auto/);
            assert.match(zone.html(), /<div class="l">Estatus<\/div><div>PAGADO/);
            assert.doesNotMatch(zone.html(), /Adeudo pendiente|Saldo pendiente/);
            if (zone.component.form.prepay === 'si') {
                const next = overrides.prepayNextMonth === false ? 'octubre' : 'noviembre';
                assert.match(zone.html(), new RegExp(`<div class="l">Siguiente pago<\\/div><div>${next} de 2026`));
                assert.match(zone.html(), /Pago adelantado \(1 mes\)/);
            }
        });
    }

    test(`${zona}: abre la ventana móvil antes de guardar y no imprime si falla el pago`, async () => {
        const zone = ticket(zona, { ref: {} });
        await zone.component.printThermal();
        assert.deepEqual(zone.events, ['open', 'save', 'error']);
        assert.equal(zone.html(), '');
        assert.equal(zone.closed(), true);
    });
}
