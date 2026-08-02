<?php

/**
 * Reconciliación julio 2026.
 *
 * Lista de números de servicio cuyos pagos de JULIO 2026 se cobraron fuera del
 * sistema (transferencia / efectivo registrado en Excel) pero NO se capturaron
 * como factura en el sistema. El sistema los contaba como mes impago, inflando
 * su adeudo en 1 mensualidad adicional al llegar agosto.
 *
 * Origen: comparación Excel "total a pagar" vs adeudo del sistema (2026-08-02).
 * Verificado: 112 clientes, diferencia = exactamente 1 mensualidad cada uno
 * (sistema = julio + agosto, Excel = solo agosto).
 *
 * Consumido por: php artisan reconciliar:julio
 */

return [
    1038, 1059, 1136, 1144, 1164, 1179,
    2036, 2049, 2055, 2074, 2078, 2084, 2101, 2104, 2130, 2149, 2154, 2164, 2170, 2179,
    3008, 3023, 3041, 3048, 3053, 3060, 3092, 3118, 3171, 3177, 3188,
    4068, 4090, 4785, 4790, 4794, 4801, 4811, 4815, 4897, 4898, 4957,
    5018, 5038, 5062, 5069, 5074, 5293, 5305, 5319, 5362, 5366, 5370, 5391, 5399, 5400,
    5453, 5457, 5458, 5470, 5478, 5502, 5637, 5638, 5641, 5646, 5706, 5711, 5716, 5725,
    5733, 5734, 5759, 5787, 5939, 5980,
    6016, 6020, 6029, 6032, 6142, 6204, 6270, 6347, 6368, 6397, 6484, 6524, 6530, 6560,
    6564, 6637, 6725, 6756, 6773, 6783, 6794, 6806, 6808,
    7015, 7051, 7056, 7075, 7086, 7105, 7113, 7213, 7281, 7288, 7355, 7412, 7495,
];
