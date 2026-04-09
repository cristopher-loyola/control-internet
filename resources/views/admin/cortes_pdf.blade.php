<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $titulo }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            color: #333;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .header h1 {
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        .header p {
            font-size: 12px;
            color: #666;
        }
        .summary {
            background: #f5f5f5;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
        }
        .summary strong {
            color: #d32f2f;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th {
            background: #d32f2f;
            color: white;
            font-weight: bold;
            padding: 8px 5px;
            text-align: left;
            font-size: 9px;
            text-transform: uppercase;
        }
        td {
            padding: 6px 5px;
            border-bottom: 1px solid #ddd;
            font-size: 9px;
        }
        tr:nth-child(even) {
            background: #f9f9f9;
        }
        .id-cell {
            font-weight: bold;
            width: 50px;
        }
        .nombre-cell {
            width: 150px;
        }
        .zona-cell {
            width: 80px;
        }
        .ip-cell {
            width: 100px;
            color: #1976d2;
        }
        .mac-cell {
            width: 110px;
            font-family: monospace;
            font-size: 8px;
        }
        .cortador-cell {
            width: 100px;
        }
        .estado-cell {
            width: 80px;
            font-weight: bold;
        }
        .estado-cortado {
            color: #d32f2f;
        }
        .estado-offline {
            color: #f57c00;
        }
        .estado-ya-cortado {
            color: #388e3c;
        }
        .estado-no-estaba {
            color: #7b1fa2;
        }
        .estado-vacio {
            color: #999;
            font-style: italic;
        }
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 8px;
            color: #999;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
        }
        .badge-pendiente {
            background: #ffebee;
            color: #d32f2f;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $titulo }}</h1>
        <p>Generado el {{ now()->format('d/m/Y H:i:s') }}</p>
    </div>

    <div class="summary">
        <strong>Total de usuarios por cortar: {{ $usuariosPorCortar->count() }}</strong>
    </div>

    <table>
        <thead>
            <tr>
                <th class="id-cell">ID</th>
                <th class="nombre-cell">Nombre Cliente</th>
                <th class="zona-cell">Zona</th>
                <th class="ip-cell">IP</th>
                <th class="mac-cell">MAC</th>
                <th class="cortador-cell">Cortador</th>
                <th class="estado-cell">Estado</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($usuariosPorCortar as $u)
                <tr>
                    <td class="id-cell">{{ $u->numero_servicio }}</td>
                    <td class="nombre-cell">{{ $u->nombre_cliente }}</td>
                    <td class="zona-cell">{{ $u->zona ?? '-' }}</td>
                    <td class="ip-cell">{{ $u->ip ?? '-' }}</td>
                    <td class="mac-cell">{{ $u->mac ?? '-' }}</td>
                    <td class="cortador-cell">{{ $u->cortador?->nombre ?? '-' }}</td>
                    <td class="estado-cell">
                        @if($u->estado_corte === 'Cortado')
                            <span class="estado-cortado">{{ $u->estado_corte }}</span>
                        @elseif($u->estado_corte === 'Offline')
                            <span class="estado-offline">{{ $u->estado_corte }}</span>
                        @elseif($u->estado_corte === 'Ya cortado')
                            <span class="estado-ya-cortado">{{ $u->estado_corte }}</span>
                        @elseif($u->estado_corte === 'NO_ESTABA')
                            <span class="estado-no-estaba">{{ $u->estado_corte }}</span>
                        @elseif(empty($u->estado_corte))
                            <span class="badge badge-pendiente">POR CORTAR</span>
                        @else
                            {{ $u->estado_corte }}
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center; padding: 20px;">
                        No hay usuarios por cortar en este momento.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Sistema de Control de Internet - Reporte generado automáticamente
    </div>
</body>
</html>
