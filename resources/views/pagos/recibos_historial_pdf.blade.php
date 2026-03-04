<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Historial de recibos</title>
    <style>
        :root{--header-bg:#2e7d32;--header-fg:#fff;--border:#888}
        html,body{-webkit-print-color-adjust:exact;print-color-adjust:exact}
        body{font-family:Arial, Helvetica, sans-serif;font-size:12px;color:#111;margin:0;padding:10px}
        h2{margin:0 0 8px 0}
        table{border-collapse:collapse;width:100%}
        colgroup col:nth-child(1){width:90px}
        colgroup col:nth-child(2){width:160px}
        colgroup col:nth-child(3){width:110px}
        colgroup col:nth-child(4){width:260px}
        colgroup col:nth-child(5){width:110px}
        colgroup col:nth-child(6){width:110px}
        colgroup col:nth-child(7){width:160px}
        thead th{background:var(--header-bg);color:var(--header-fg)}
        th,td{border:1px solid var(--border);padding:6px 8px;text-align:left;vertical-align:middle}
        .money{text-align:right}
        .mono{font-family:Consolas, 'Courier New', monospace}
        .cancelado{background:#fee2e2}
        .totalbar{margin-top:8px; padding:6px 10px; background:#1e3a8a; color:#fff; font-weight:700; display:inline-block; border-radius:4px}
        @media print{.no-print{display:none}}
    </style>
    <script>
        (async function(){
            function waitImages(){
                const imgs = Array.from(document.querySelectorAll('img'));
                return Promise.all(imgs.map(img=>{
                    if(img.complete) return Promise.resolve();
                    return new Promise(res=>{
                        img.addEventListener('load',res,{once:true});
                        img.addEventListener('error',res,{once:true});
                    });
                }));
            }
            try{
                if(document.fonts && document.fonts.ready){ await document.fonts.ready }
                await waitImages();
                await new Promise(r=>setTimeout(r,50));
            }catch(_){}
            window.print();
        })();
        window.onafterprint = ()=>{ if (window.close) setTimeout(()=>window.close(), 150); };
    </script>
    </head>
<body>
    <div class="no-print" style="margin-bottom:8px">
        <button onclick="window.print()">Imprimir / Guardar como PDF</button>
    </div>
    <h2 style="margin:0 0 8px 0">Historial de recibos</h2>
    <div style="margin-bottom:8px">
        @if($from) <span><strong>Desde:</strong> {{ $from }}</span> @endif
        @if($to) <span style="margin-left:12px"><strong>Hasta:</strong> {{ $to }}</span> @endif
        @if($cliente) <span style="margin-left:12px"><strong>Cliente/Número:</strong> {{ $cliente }}</span> @endif
    </div>
    <table>
        <colgroup>
            <col><col><col><col><col><col>
        </colgroup>
        <thead>
            <tr>
                <th>Folio</th>
                <th>Fecha</th>
                <th>Monto</th>
                <th>Cliente</th>
                <th>Número</th>
                <th>Estado</th>
                <th>Motivo</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $f)
                @php
                    $payload = is_array($f->payload) ? $f->payload : (is_string($f->payload) ? @json_decode($f->payload, true) : []);
                    $nombre = is_array($payload) ? ($payload['nombre'] ?? '') : '';
                    $k = ($f->numero_servicio ?? '').'|'.($f->periodo ?? '');
                    $motivo = $reasons[$k] ?? '';
                @endphp
                <tr class="{{ $f->deleted_at ? 'cancelado' : '' }}">
                    <td class="mono">{{ str_pad((string)$f->reference_number, 8, '0', STR_PAD_LEFT) }}</td>
                    <td>{{ optional($f->created_at)->format('d/m/Y H:i') }}</td>
                    <td class="money">${{ number_format((float)$f->total, 2) }}</td>
                    <td>{{ $nombre }}</td>
                    <td class="mono">{{ $f->numero_servicio }}</td>
                    <td>{{ $f->deleted_at ? 'Cancelado' : 'Vigente' }}</td>
                    <td>{{ $motivo }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <div class="totalbar">Total: ${{ number_format((float) ($total ?? 0), 2) }}</div>
</body>
</html>
