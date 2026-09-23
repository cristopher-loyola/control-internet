<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historial de modificaciones - {{ $mes }}</title>
    <style>
        table { border-collapse: collapse; font-family: Calibri, sans-serif; font-size: 11pt; }
        th, td { border: 1px solid #aaa; padding: 6px 8px; }
        th { background: #1e3a8a; color: white; }
        .texto { mso-number-format: "\@"; }
    </style>
</head>
<body>
    <h2>Historial de modificaciones de adeudo — {{ ucfirst($mesLabel) }}</h2>
    <p>{{ $cantidad }} modificaciones mediante “Modificar total”. Incluye recibos cancelados.</p>
    @include('admin.modificaciones.tabla')
</body>
</html>
