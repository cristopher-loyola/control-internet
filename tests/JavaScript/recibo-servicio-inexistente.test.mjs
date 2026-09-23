import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import vm from 'node:vm';

const views = ['admin/pagos', 'pagos/recibos', 'rosalito/pagos', 'chivato/pagos', 'pozo_hondo/pagos'];

for (const view of views) {
    for (const printType of ['ticket', 'receipt']) {
        test(`${view}: no imprime ${printType} cuando el servidor rechaza el servicio`, async () => {
            const source = readFileSync(new URL(`../../resources/views/${view}.blade.php`, import.meta.url), 'utf8')
                .replace(/\{\{[^]*?\}\}/g, '/test');
            const extract = (name) => {
                const signature = new RegExp(`\\n            (?:async )?${name}\\(`).exec(source);
                assert.ok(signature, `Método ${name}`);
                const start = signature.index + '\n            '.length;
                const end = source.indexOf('\n            },', start);
                return source.slice(start, end + '\n            }'.length);
            };
            const message = 'El número de servicio no existe. No se puede imprimir el recibo.';
            let requests = 0;
            const component = vm.runInNewContext(`({${['confirmSaveYes', 'emitirFactura', 'showPrintFail'].map(extract).join(',')}})`, {
                document: { querySelector: () => null },
                fetch: async (url, options) => {
                    requests++;
                    assert.equal(options.method, 'POST');
                    assert.equal(JSON.parse(options.body).numero_servicio, '2');
                    return { ok: false, status: 422, json: async () => ({ message, errors: { numero_servicio: [message] } }) };
                },
                console: { error: assert.fail },
            });
            Object.assign(component, {
                printType, ref: {}, datos: { nombre: '', mensualidad: 0 }, totales: { total: 50 },
                form: { numero: '2', recargo: 'si', metodo: 'Efectivo', prepay: 'no', otro: 'no' },
                saveConfirmOpen: true, isSaving: false, printFailOpen: false,
                fecha: () => '', hora: () => '', otroLabel: () => 'No',
                printThermal: () => assert.fail('No debe abrir la impresión del ticket'),
                doPrintOnce: () => assert.fail('No debe abrir la impresión del recibo'),
                clearManualEdit: () => {},
            });

            await component.confirmSaveYes();

            assert.equal(requests, 1);
            assert.equal(component.printFailOpen, true);
            assert.equal(component.printFailMsg, message);
            assert.equal(component.saveConfirmOpen, false);
            assert.equal(component.isSaving, false);
            assert.equal(component.ref.id, undefined);
        });
    }
}
