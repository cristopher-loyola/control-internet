import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import vm from 'node:vm';

const views = ['admin/pagos', 'pagos/recibos'];

test('el modal de monto fijo conserva la base al volver a abrir un saldo con recargo', () => {
    const source = readFileSync(new URL('../../resources/views/admin/clientes/index.blade.php', import.meta.url), 'utf8');
    const method = source.match(/montoBaseProximoPago\(\) \{[^]*?\n                \}/)[0];
    const modal = vm.runInNewContext(`({${method}})`);
    modal.ppDeuda = { pendiente: 1050, recargo: 50 };
    assert.equal(modal.montoBaseProximoPago(), 1000);
    modal.ppDeuda = { pendiente: 1000, recargo: 0 };
    assert.equal(modal.montoBaseProximoPago(), 1000);
});

function component(view, fetch) {
    const source = readFileSync(new URL(`../../resources/views/${view}.blade.php`, import.meta.url), 'utf8')
        .replace(/\{\{[^]*?\}\}/g, '/test');
    const extract = (name) => {
        const signature = new RegExp(`\\n            (?:async )?${name}\\(`).exec(source);
        assert.ok(signature, `Método ${name} en ${view}`);
        const start = signature.index + '\n            '.length;
        const end = source.indexOf('\n            },', start);
        return source.slice(start, end + '\n            }'.length);
    };
    const methods = ['adeudoTexto', 'recargoMonto', 'recalcular', 'fetchAdeudo', 'reimprimir', 'emitirFactura', 'prepareAndPrint'];
    return Object.assign(vm.runInNewContext(`({${methods.map(extract).join(',')}})`, {
        fetch, toWords: String,
        document: { querySelector: () => null },
    }), {
        ref: {}, datos: { mensualidad: 500 }, totales: {},
        form: { numero: '6738', recargo: 'no', otro: 'no', prepay: 'no' },
        adeudo: null, descripcionManual: '', recargoManual: false,
        adeudoCobro: 0, adeudoListaMeses: [],
        fecha: () => '', hora: () => '', otroLabel: () => 'No',
        fetchPagoAnterior: async () => {}, fetchPrepayStatus: async () => {},
        doPrintOnce: async () => {}, showPrintFail: assert.fail,
    });
}

function deuda() {
    return {
        ok: true, pendiente: 1050, recargo: 50, meses_adeudo: 1,
        desde_periodo: '2026-09', desde_mes_label: 'septiembre 2026',
        descripcion_manual: 'Agosto', lista_meses: [], cubierto_este_mes: false,
    };
}

for (const view of views) {
    test(`${view}: monto fijo activa recargo y muestra la descripción del modal`, async () => {
        const state = component(view, async () => ({ ok: true, json: async () => deuda() }));
        await state.fetchAdeudo();
        assert.equal(state.form.recargo, 'si');
        assert.equal(state.totales.total, 1050);
        assert.equal(state.adeudoTexto(), 'Adeuda desde Agosto');

        state.form.recargo = 'no';
        state.recargoManual = true;
        await state.fetchAdeudo();
        assert.equal(state.form.recargo, 'no');
        assert.equal(state.totales.total, 1000);
    });

    test(`${view}: texto completo, mes calculado y descripción ausente`, () => {
        const state = component(view);
        state.descripcionManual = 'Adeuda agosto y septiembre';
        assert.equal(state.adeudoTexto(), 'Adeuda agosto y septiembre');
        state.descripcionManual = 'Ajuste autorizado';
        assert.equal(state.adeudoTexto(), 'Ajuste autorizado');
        state.descripcionManual = '';
        state.adeudo = { desde_label: 'abril 2026' };
        assert.equal(state.adeudoTexto(), 'Adeuda desde abril 2026');
        state.adeudo = null;
        assert.equal(state.adeudoTexto(), 'Descripción de adeudo no disponible');
    });

    for (const saveMethod of ['emitirFactura', 'prepareAndPrint']) {
        test(`${view}: ${saveMethod} conserva la descripción al guardar y reabrir`, async () => {
            let saved;
            const state = component(view, async (url, options = {}) => {
                if (options.method === 'POST') {
                    saved = JSON.parse(options.body);
                    return { ok: true, json: async () => ({ ok: true, referencia: 955, id: 955 }) };
                }
                if (url.includes('?numero=')) {
                    return { ok: true, json: async () => ({ ...deuda(), pendiente: 0, recargo: 0, descripcion_manual: '', cubierto_este_mes: true }) };
                }
                return { ok: true, json: async () => ({ ok: true, data: { ...saved, id: 955, reference_number: 955 } }) };
            });
            state.descripcionManual = 'Agosto';
            state.adeudo = { pendiente: 1050, recargo: 50, desde_label: 'Agosto', lista_meses: [] };
            state.adeudoCobro = 1050;
            state.form.recargo = 'si';
            state.recalcular();
            await state[saveMethod]();
            assert.equal(saved.total, 1050);
            assert.equal(saved.payload.adeudo_descripcion, 'Agosto');
            assert.equal(saved.payload.adeudo_desde_label, 'Agosto');

            state.descripcionManual = 'Texto de otro cliente';
            await state.reimprimir(955);
            assert.equal(state.adeudoTexto(), 'Adeuda desde Agosto');
            assert.equal(state.totales.total, 1050);
            assert.equal(state.form.recargo, 'si');
        });
    }
}
