import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import vm from 'node:vm';

for (const zona of ['chivato', 'pozo_hondo', 'rosalito']) {
    for (const failure of ['respuesta negativa', 'error de red']) {
        test(`${zona}: reintenta confirmar tras ${failure} sin repetir el pago ni cambiar el recargo`, async () => {
            const source = readFileSync(new URL(`../../resources/views/${zona}/pagos.blade.php`, import.meta.url), 'utf8')
                .replace(/\{\{[^]*?\}\}/g, expression => expression.includes('facturas.verificar') ? '/verificar/__ID__'
                    : expression.includes('recibos.prev') ? '/previo'
                    : expression.includes('recibos.deuda') ? '/deuda' : '/test');
            const extract = name => {
                const signature = new RegExp(`\\n            (?:async )?${name}\\(`).exec(source);
                assert.ok(signature, name);
                const start = signature.index + '\n            '.length;
                const end = source.indexOf('\n            },', start);
                return source.slice(start, end + '\n            }'.length);
            };
            const methods = ['confirmSaveYes', 'emitirFactura', 'confirmarFacturaEnCorte', 'fetchPagoAnterior', 'fetchAdeudo', 'recalcular', 'showPrintFail'];
            let posts = 0;
            let verifications = 0;
            let prints = 0;
            let saved;
            const json = data => ({ ok: true, json: async () => data });
            const component = vm.runInNewContext(`({${methods.map(extract).join(',')}})`, {
                document: { querySelector: () => null }, toWords: String, console: { error: assert.fail },
                Date: class extends Date {
                    constructor(...args) { super(...(args.length ? args : ['2026-10-29T12:00:00'])); }
                },
                fetch: async (url, options = {}) => {
                    if (options.method === 'POST') {
                        posts++;
                        saved = JSON.parse(options.body);
                        return json({ ok: true, id: 20, referencia: 12226 });
                    }
                    if (url === '/verificar/20') {
                        verifications++;
                        if (verifications === 1) {
                            if (failure === 'error de red') throw new Error('Sin conexión');
                            return json({ ok: true, existe: false });
                        }
                        return json({ ok: true, existe: true });
                    }
                    if (url.startsWith('/previo')) return json({ ok: true, data: { monto: 300, periodo: '2026-09', fecha: '2026-09-07' } });
                    if (url.startsWith('/deuda')) return json({ ok: true, pendiente: 0, recargo: 0, meses_adeudo: 0, cubierto_este_mes: true });
                    assert.fail(`Petición inesperada: ${url}`);
                },
            });
            Object.assign(component, {
                ref: {}, form: { numero: '1019', prepay: 'si', prepay_months: 1, recargo: 'no', otro: 'no', metodo: 'Efectivo' },
                datos: { nombre: 'Cliente de prueba', mensualidad: 300 },
                totales: { total: 300, prepay_total: 300 }, adeudoCobro: 0, prepayNextMonth: true,
                printType: 'ticket', fecha: () => '', hora: () => '', fechaLocal: String, otroLabel: () => '',
                fetchPrepayStatus: async () => { component.prepayActivo = true; },
                printThermal: async () => { prints++; },
            });

            await component.confirmSaveYes();
            assert.equal(component.ref.id, 20);
            assert.equal(component.ref.confirmed, false);
            assert.equal(component.printFailOpen, true);
            assert.equal(prints, 0);
            assert.equal(component.form.recargo, 'no');

            await component.confirmSaveYes();
            assert.equal(component.ref.confirmed, true);
            assert.equal(verifications, 2);
            assert.equal(prints, 1);
            assert.equal(posts, 1);
            assert.equal(saved.total, 300);
            assert.equal(saved.payload.recargo, 'no');
            assert.equal(component.totales.total, 300);
            assert.equal(component.form.recargo, 'no');

            await component.confirmSaveYes();
            assert.equal(prints, 2);
            assert.equal(posts, 1);
            assert.equal(verifications, 2);
        });
    }
}
