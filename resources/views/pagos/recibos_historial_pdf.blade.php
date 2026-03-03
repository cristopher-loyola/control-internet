<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Historial de recibos</title>
    <style>
        body{font-family:ui-sans-serif,system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial,'Apple Color Emoji','Segoe UI Emoji';font-size:12px;color:#111}
        table{border-collapse:collapse;width:100%}
        th,td{border:1px solid #ddd;padding:6px 8px;text-align:left}
        th{background:#f3f4f6}
        .cancelado{background:#fee2e2}
        @media print{.no-print{display:none}}
    </style>
    <script>
        (async function(){
            const debug = new URLSearchParams(location.search).has('debugprint');
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
                if(debug) console.debug('[print] pdf: waiting fonts');
                if(document.fonts && document.fonts.ready){ await document.fonts.ready }
                if(debug) console.debug('[print] pdf: waiting images');
                await waitImages();
                if(debug) console.debug('[print] pdf: small delay');
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
        <thead>
            <tr>
                <th>Folio</th>
                <th>Fecha</th>
                <th>Monto</th>
                <th>Cliente</th>
                <th>Número</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $f)
                @php
                    $payload = is_array($f->payload) ? $f->payload : (is_string($f->payload) ? @json_decode($f->payload, true) : []);
                    $nombre = is_array($payload) ? ($payload['nombre'] ?? '') : '';
                @endphp
                <tr class="{{ $f->deleted_at ? 'cancelado' : '' }}">
                    <td>{{ str_pad((string)$f->reference_number, 8, '0', STR_PAD_LEFT) }}</td>
                    <td>{{ optional($f->created_at)->format('d/m/Y H:i') }}</td>
                    <td>${{ number_format((float)$f->total, 2) }}</td>
                    <td>{{ $nombre }}</td>
                    <td>{{ $f->numero_servicio }}</td>
                    <td>{{ $f->deleted_at ? 'Cancelado' : 'Vigente' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
