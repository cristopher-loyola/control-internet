import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import vm from 'node:vm';

const views = ['admin/pagos', 'pagos/recibos', 'chivato/pagos', 'rosalito/pagos', 'pozo_hondo/pagos'];

function methods(view, names = ['recalcular', 'recargoMonto'], globals = {}) {
    const source = readFileSync(new URL(`../../resources/views/${view}.blade.php`, import.meta.url), 'utf8')
        .replace(/\{\{[^]*?\}\}/g, '/test');
    const extract = (name) => {
        const signature = new RegExp(`\\n            (?:async )?${name}\\(`).exec(source);
        assert.ok(signature, `Método ${name} en ${view}`);
        const start = signature.index + '\n            '.length;
        const end = source.indexOf('\n            },', start);
        return source.slice(start, end + '\n            }'.length);
    };
    return vm.runInNewContext(`({${names.map(extract).join(',')}})`, {
        toWords: String,
        ...globals,
    });
}

for (const view of views) {
    test(`${view}: adeudo más meses futuros, con descuento solo al adelanto`, () => {
        const component = {
            ...methods(view), ref: {}, datos: { mensualidad: 300 }, totales: {},
            form: { prepay: 'si', prepay_months: 1, recargo: 'no' },
            adeudo: { pendiente: 300, recargo: 0 }, adeudoCobro: 300,
            recargoMonto: () => 0,
            prepayConfig: { matrix: { 6: { percent: 10, totals: { 300: 1620 } } } },
        };
        component.recalcular();
        assert.equal(component.totales.total, 600);
        component.adeudoCobro = component.adeudo.pendiente = 10;
        component.recalcular();
        assert.equal(component.totales.total, 310);
        component.form.prepay_months = 6;
        component.recalcular();
        assert.equal(component.totales.total, 1630);
    });

    test(`${view}: fechas de adelantos nuevos y recibos anteriores`, () => {
        const muestraSiguiente = ['admin/pagos', 'pagos/recibos'].includes(view);
        const name = muestraSiguiente ? 'mesSiguientePago' : 'mesFinalCobertura';
        const component = { ...methods(view, [name]), ref: { created_at: '2026-09-30T12:00:00' }, prepayNextMonth: true };
        assert.match(component[name](1), muestraSiguiente ? /noviembre de 2026/ : /octubre de 2026/);
        component.prepayNextMonth = false;
        assert.match(component[name](1), muestraSiguiente ? /octubre de 2026/ : /septiembre de 2026/);
    });

    test(`${view}: el recargo se puede quitar y volver a aplicar sin duplicarlo`, () => {
        const component = {
            ...methods(view), ref: {}, datos: { mensualidad: 300 }, totales: {},
            form: { prepay: 'si', prepay_months: 1, recargo: 'si' },
            adeudo: { pendiente: 350, recargo: 50 }, adeudoCobro: 350,
        };
        component.recalcular();
        assert.equal(component.totales.total, 650);
        component.form.recargo = 'no';
        component.recalcular();
        assert.equal(component.totales.total, 600);
        component.form.recargo = 'si';
        component.recalcular();
        assert.equal(component.totales.total, 650);
        component.adeudo = { pendiente: 0, recargo: 0 };
        component.adeudoCobro = 0;
        component.form.recargo = 'no';
        component.recalcular();
        assert.equal(component.totales.total, 300);
    });
}

for (const view of ['chivato/pagos', 'rosalito/pagos', 'pozo_hondo/pagos']) {
    test(`${view}: consulta el saldo ajustado y no agrega recargo a un cliente al corriente`, async () => {
        let deuda = { ok: true, pendiente: 10, recargo: 0, meses_adeudo: 0, cubierto_este_mes: false };
        const component = {
            ...methods(view, ['fetchAdeudo', 'recalcular', 'recargoMonto'], {
                fetch: async () => ({ ok: true, json: async () => deuda }),
            }),
            ref: {}, datos: { mensualidad: 300 }, totales: {},
            form: { numero: '8400', prepay: 'si', prepay_months: 1, recargo: 'si' },
        };
        await component.fetchAdeudo();
        assert.equal(component.totales.total, 310);
        deuda = { ...deuda, pendiente: 0, cubierto_este_mes: true };
        component.form.recargo = 'si';
        await component.fetchAdeudo();
        assert.equal(component.form.recargo, 'no');
        assert.equal(component.totales.total, 300);
    });

    test(`${view}: permite adelantar después de emitir el pago del mes`, async () => {
        const component = {
            ...methods(view, ['onPrepayChange', 'inputChanged', 'recalcular', 'recargoMonto']),
            ref: { id: 5, numero: 5 }, datos: { mensualidad: 300 }, totales: { total: 300 },
            form: { prepay: 'si', prepay_months: 2, recargo: 'no' },
            adeudo: { pendiente: 0, recargo: 0 }, adeudoCobro: 350,
        };
        component.onPrepayChange();
        assert.equal(component.ref.id, null);
        assert.equal(component.totales.total, 600);
        assert.equal(component.adeudoCobro, 0);
    });
}
