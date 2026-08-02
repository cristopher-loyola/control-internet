<?php

/**
 * Corrección de proximo_pago para clientes con "Baja temporal" / "Suspensión"
 * capturada solo con nota de texto (o, en el caso de #5469, con el mecanismo
 * real del sistema pero antes del fix a aplicarBajaTemporal() que ahora sí
 * fija proximo_pago). Sin esto, el sistema no sabe cuándo termina la pausa
 * y sigue acumulando adeudo de los meses pausados.
 *
 * Origen: comparación Excel "total a pagar" vs adeudo del sistema (2026-08-02),
 * clientes con nota "Suspende/Baja temporal..." en el Excel. Fecha de fin
 * parseada a mano de la nota de cada cliente.
 *
 * Excluye #1108, #3029, #4051, #5687, #6827: nota sin duración clara o
 * contradictoria con los pagos registrados, pendiente de confirmar.
 *
 * Consumido por: php artisan corregir:baja-temporal-agosto2026
 */

return [
    // numero_servicio => proximo_pago (primer mes YA NO cubierto)
    '2027' => '2027-01', // Suspende servicio hasta Diciembre
    '4018' => '2026-06', // Suspender Marzo, Abril y Mayo 2026
    '5029' => '2027-01', // Suspende servicio temporal hasta Diciembre
    '5469' => '2026-08', // Baja temporal Junio, Julio 2026
    '5967' => '2026-08', // Baja temporal Julio 2026
    '6132' => '2026-05', // suspender en mes de Abril-2026
    '7034' => '2026-08', // Suspende hasta Julio-2026
];
