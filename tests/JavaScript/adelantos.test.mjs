import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import vm from 'node:vm';

const views = ['admin/pagos', 'pagos/recibos', 'chivato/pagos', 'rosalito/pagos', 'pozo_hondo/pagos'];

function methods(view) {
    const source = readFileSync(new URL(`../../resources/views/${view}.blade.php`, import.meta.url), 'utf8');
    const extract = (name) => {
        const start = source.indexOf(`\n            ${name}(`) + '\n            '.length;
        const end = source.indexOf('\n            },', start);
        return source.slice(start, end + '\n            }'.length);
    };
    return vm.runInNewContext(`({${extract('recalcular')},${extract('mesFinalCobertura')}})`, {
        toWords: String,
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

    test(`${view}: un mes futuro termina en octubre y un recibo antiguo conserva septiembre`, () => {
        const component = { ...methods(view), ref: { created_at: '2026-09-30T12:00:00' }, prepayNextMonth: true };
        assert.match(component.mesFinalCobertura(1), /octubre de 2026/);
        component.prepayNextMonth = false;
        assert.match(component.mesFinalCobertura(1), /septiembre de 2026/);
    });
}
