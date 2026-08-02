<?php

/**
 * Limpieza de adeudo_monto obsoleto (agosto 2026).
 *
 * Clientes cuyo adeudo_monto (saldo importado del Excel de junio) ya se
 * saldó fuera del sistema, pero la nota nunca se limpió — el sistema
 * seguía cobrándolo encima de las mensualidades normales.
 *
 * Evidencia: en los 34 casos la diferencia contra el Excel es EXACTAMENTE
 * el adeudo_monto, y la descripción del Excel ya avanzó de mes respecto a
 * la del sistema (ej. sistema "Adeuda Junio" vs Excel "Adeuda Julio"),
 * señal de que ese mes viejo quedó saldado.
 *
 * Se limpia el campo en vez de crear una factura de reconciliación porque
 * adeudo_monto es un saldo acumulado sin periodo propio: inventarle una
 * factura distorsionaría los reportes de corte/caja con dinero que no
 * entró por el sistema en esa fecha.
 *
 * El comando guarda los valores previos en audit_logs, así que es
 * reversible si aparece evidencia de que alguna deuda sí era vigente.
 *
 * Total liberado: $12,230.00 en 34 clientes.
 *
 * Origen: comparación Excel "total a pagar" vs adeudo del sistema (2026-08-02).
 *
 * NO incluye los 6 casos donde la diferencia NO cuadra exacto con el
 * adeudo_monto (#3137, #4085, #5329, #5437, #5974, #6628): esos necesitan
 * revisión individual.
 *
 * Consumido por: php artisan corregir:adeudos-obsoletos-agosto2026
 */

return [
    // Segunda tanda (misma causa, detectados aparte porque su fila del Excel
    // no traía nota; verificado: al limpiar el adeudo quedan en $300, que es
    // exactamente lo que dice el Excel).
    '2164', // $300.00 - Adeuda Junio (ya tiene factura de julio pagada)
    '2170', // $600.00 - Adeuda Junio (ya tiene factura de julio pagada)
    '7113', // $600.00 - Adeuda Mayo, Junio (ya tiene factura de julio pagada)

    '1020', // $80.00 - Junio $80.00 Julio $300.00. Adeuda Junio
    '3149', // $600.00 - Adeuda Mayo, Junio
    '3160', // $300.00 - Adeuda Junio
    '4045', // $300.00 - Adeuda Junio
    '4047', // $400.00 - Adeuda Junio
    '4983', // $600.00 - Adeuda Junio
    '4991', // $300.00 - Adeuda Mayo
    '5126', // $600.00 - Adeuda Mayo, Junio
    '5372', // $300.00 - Adeuda Junio
    '5483', // $300.00 - Adeuda Junio
    '5588', // $300.00 - Adeuda Junio
    '5793', // $300.00 - Adeuda Junio
    '5863', // $300.00 - Adeuda Junio
    '5955', // $300.00 - Adeuda Junio
    '5960', // $300.00 - Adeuda Junio
    '5984', // $50.00 - Adeuda Recargo de Junio 27-Junio (CORTADO)
    '5989', // $300.00 - Adeuda Mayo o Junio
    '5998', // $300.00 - Adeuda Junio
    '6075', // $600.00 - Adeuda Mayo, Junio
    '6139', // $300.00 - Adeuda Mayo o Junio
    '6191', // $300.00 - Adeuda Junio
    '6223', // $300.00 - Adeuda Junio
    '6381', // $300.00 - Adeuda Junio
    '6453', // $300.00 - Adeuda Junio
    '6633', // $600.00 - Adeuda Mayo, Junio
    '6665', // $300.00 - sin descripcion
    '7043', // $900.00 - Adeuda desde Abril-2026
    '7109', // $600.00 - Adeuda Mayo, Junio
    '7147', // $300.00 - Adeuda Junio
    '7148', // $300.00 - Adeuda Junio
    '7377', // $300.00 - Adeuda Junio
    '7414', // $300.00 - Adeuda Mayo o Junio
    '7438', // $300.00 - Adeuda Junio
    '7452', // $300.00 - Adeuda Junio
];
