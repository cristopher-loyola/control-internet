<?php

/**
 * Corrección de proximo_pago para clientes que pagaron por adelantado
 * pero el pago se capturó con "Modificar total" + nota de texto (o no se
 * capturó en absoluto), en vez del campo real de "Pago por adelantado".
 * El sistema no podía leer la nota, así que no sabía hasta cuándo estaba
 * cubierto el cliente.
 *
 * Origen: comparación Excel "total a pagar" vs adeudo del sistema (2026-08-02),
 * clientes con nota "Adelanto..." en el Excel. Fecha de cobertura parseada
 * a mano de la nota de cada cliente.
 *
 * Excluye #3161, #3169, #6014, #6184: su nota trae "?" (incertidumbre del
 * propio registro), no se corrigen automáticamente.
 * Excluye #6964 "Pago anual de Marzo-2027": fecha ambigua (¿inicia o termina
 * en marzo 2027?), pendiente de confirmar con el negocio.
 *
 * Consumido por: php artisan corregir:adelantos-agosto2026
 */

return [
    // numero_servicio => proximo_pago (primer mes YA NO cubierto)
    '1018' => '2027-01', // Adelanto hasta Diciembre 2026
    '1118' => '2027-03', // Pago anual Marzo 2026 - Febrero 2027
    '2028' => '2026-10', // Pago Anual: Octubre-2025 a Septiembre-2026
    '5188' => '2027-01', // Pago hasta Diciembre-2026
    '1052' => '2026-09', // Adelanto Agosto 2026
    '2015' => '2026-09', // Adelanto julio-agosto
    '2061' => '2026-09', // Adelanto Agosto
    '4086' => '2026-09', // Adelanto hasta agosto 2026
    // '5382' => '2026-08', // ya está correcto, sin cambio
    '5405' => '2027-01', // Adelanto hasta Diciembre 2026
    '5484' => '2026-09', // Adelanto hasta Agosto-2026
    '5535' => '2026-09', // Adelanto Agosto 2026
    '5799' => '2026-10', // Adelanto hasta Sept-2026
    '6028' => '2027-01', // Adelanta Enero - Diciembre 2026
    '6073' => '2026-09', // Adelanta Agosto 2026
    '6079' => '2027-02', // Adelanta hasta enero 2027
    '6085' => '2027-01', // Adelanta hasta Diciembre 2026
    '6147' => '2026-11', // Adelanto hasta Octubre-2026
    '6237' => '2026-10', // Adelanto hasta Septiembre-2026
    '6348' => '2027-03', // Adelanto hasta Febrero-2027
    '6360' => '2027-01', // Adelanta hasta Diciembre 2026
    '6509' => '2026-08', // Adelanto Mayo, Junio, Julio 2026
    '6599' => '2027-02', // Adelanta hasta Enero 2027
    '6712' => '2027-01', // Adelanto hasta Diciembre 2026
    '6857' => '2027-01', // Adelanto hasta Diciembre de 2026
    '6991' => '2026-10', // Adelanta 6 Meses de Marzo a Septiembre-2026 (nota inconsistente: rango real es 7 meses)
    '7003' => '2026-11', // Adelanta 6 Meses Mayo a Octubre 2026
    '7170' => '2027-01', // Adelanta 6 meses Julio-Diciembre 2026
    '7185' => '2027-01', // Pago Adelantado: Marzo 2026 a Diciembre 2026
    '7256' => '2027-01', // Adelanta 8 meses. Mayo a Diciembre 2026
];
