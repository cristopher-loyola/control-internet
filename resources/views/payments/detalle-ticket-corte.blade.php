<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Detalle de pagos - Corte #{{ $corte->id }} - {{ $titulo }}</title>
    <style>
        @page { margin: 5mm; }
        * { box-sizing: border-box; }
        body { margin: 0 auto; padding: 12px; max-width: 80mm; color: #000; background: #fff; font: 12px 'Courier New', monospace; }
        h1 { font-size: 19px; margin: 12px 0 4px; }
        header { text-align: center; }
        p { margin: 6px 0; overflow-wrap: anywhere; }
        .pago { border-top: 1px dashed #000; padding: 9px 0; break-inside: avoid; }
        .fila { display: flex; justify-content: space-between; gap: 8px; }
        .resumen { border-top: 2px solid #000; padding-top: 8px; break-inside: avoid; }
        button { padding: 8px 12px; cursor: pointer; }
        @media print { body { padding: 0; } .acciones { display: none; } }
    </style>
</head>
<body>
    <div class="acciones"><button type="button" onclick="window.print()">Imprimir ticket</button></div>
    <header>
        <h1>DETALLE DE PAGOS</h1>
        <strong>{{ $titulo }}</strong>
        <p>Corte #{{ $corte->id }}</p>
    </header>
    <p>Cajero: {{ $corte->user?->name ?? 'Sin usuario' }}</p>
    <p>Inicio: {{ $corte->fecha_inicio?->format('d/m/Y H:i') }}</p>
    <p>Fin: {{ $corte->fecha_fin?->format('d/m/Y H:i') ?? 'Activo' }}</p>
    <p>Impresión: {{ now()->format('d/m/Y H:i') }}</p>
    <p>Número de pagos: {{ $pagos->count() }}</p>
    <p>Orden: número de servicio de menor a mayor</p>
    @forelse($pagos as $pago)
        <article class="pago">
            <div class="fila"><strong>Servicio {{ $pago['numero_servicio'] }}</strong><strong>${{ number_format($pago['total'], 2) }}</strong></div>
            <p>{{ $pago['nombre'] }}</p>
            <p>Folio: {{ $pago['folio'] }}</p>
            <p>{{ $pago['fecha'] }}</p>
        </article>
    @empty
        <p>Este corte no tiene pagos.</p>
    @endforelse
    <footer class="resumen">
        <p class="fila"><span>Total en caja</span><strong>${{ number_format($total, 2) }}</strong></p>
        <p class="fila"><span>Comisión recibos</span><span>${{ number_format($comision, 2) }}</span></p>
        <p class="fila"><strong>Total a entregar</strong><strong>${{ number_format($total - $comision, 2) }}</strong></p>
    </footer>
    <script>window.addEventListener('load', () => window.print());</script>
</body>
</html>
