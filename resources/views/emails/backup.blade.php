<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; }
        .header { color: #4f46e5; border-bottom: 2px solid #4f46e5; padding-bottom: 10px; }
        .info { margin: 20px 0; }
        .info-row { display: flex; justify-content: space-between; padding: 12px; background: #f9fafb; margin: 8px 0; border-radius: 6px; }
        .footer { margin-top: 30px; font-size: 12px; color: #6b7280; text-align: center; }
        ul { line-height: 1.8; }
        li { color: #374151; }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="header">Backup Automatico - Control Internet</h2>

        <div class="info">
            <div class="info-row">
                <strong>Fecha:</strong>
                <span>{{ $fecha }}</span>
            </div>
            <div class="info-row">
                <strong>Archivo:</strong>
                <span>{{ $archivo }}</span>
            </div>
            <div class="info-row">
                <strong>Tamano:</strong>
                <span>{{ $tamano }}</span>
            </div>
        </div>

        <p>El archivo adjunto contiene:</p>
        <ul>
            <li>Base de datos completa (con triggers, procedures, etc.)</li>
            <li>Imagenes y archivos subidos</li>
            <li>Retencion: Se mantiene en Gmail hasta que borres el email</li>
        </ul>

        <div class="footer">
            Este backup se genera automaticamente cada dia a las 2:00 AM<br>
            Sistema Control Internet
        </div>
    </div>
</body>
</html>
