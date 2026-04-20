# Guía de Métricas para Tarjetas de Pagos

## Endpoint: `DashboardController::metrics`

Ambos dashboards (admin y pagos) deben devolver las siguientes métricas en el endpoint `metrics`:

### Métricas Existentes (ya funcionan)
```json
{
  "metodos": [],
  "clientes_nuevos": {"day":0,"week":0,"month":0},
  "inventario_bajo": [],
  "ventas_series": {"labels":[],"values":[]},
  "prepay_clients": [],
  "cancelados_count": 0,
  "cancelados": [],
  "morosos": [],
  "morosos_count": 0,
  "baja_temporal_count": 0,
  "clientes_activos": 0,
  "clientes_activos_label": "Activado",
  "clientes_desactivados": 0,
  "ventas_total": 0,
  "ventas_count": 0,
  "ingresos": 0
}
```

### Nuevas Métricas Requeridas (para las tarjetas de pagos)
```json
{
  "rosalito_pagos": 0,
  "rosalito_count": 0,
  "rosalito_promedio": 0,
  "chivato_pagos": 0,
  "chivato_count": 0,
  "chivato_promedio": 0,
  "pozo_hondo_pagos": 0,
  "pozo_hondo_count": 0,
  "pozo_hondo_promedio": 0
}
```

## Estructura Completa del Response
```json
{
  "ok": true,
  "metodos": [],
  "clientes_nuevos": {"day":0,"week":0,"month":0},
  "inventario_bajo": [],
  "ventas_series": {"labels":[],"values":[]},
  "prepay_clients": [],
  "cancelados_count": 0,
  "cancelados": [],
  "morosos": [],
  "morosos_count": 0,
  "baja_temporal_count": 0,
  "clientes_activos": 0,
  "clientes_activos_label": "Activado",
  "clientes_desactivados": 0,
  "ventas_total": 0,
  "ventas_count": 0,
  "ingresos": 0,
  "rosalito_pagos": 0,
  "rosalito_count": 0,
  "rosalito_promedio": 0,
  "chivato_pagos": 0,
  "chivato_count": 0,
  "chivato_promedio": 0,
  "pozo_hondo_pagos": 0,
  "pozo_hondo_count": 0,
  "pozo_hondo_promedio": 0
}
```

## Cálculos Sugeridos
- `*_pagos`: Suma total de pagos para esa zona
- `*_count`: Número de pagos para esa zona  
- `*_promedio`: `*_pagos / *_count` (si count > 0, sino 0)

## Implementación en Backend
Asegúrate de agregar estas métricas en el método `metrics()` del `DashboardController` para que ambos dashboards funcionen correctamente.
