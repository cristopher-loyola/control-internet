# Auto-elevar a administrador si no tiene permisos
if (-not ([Security.Principal.WindowsPrincipal][Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) {
    Start-Process powershell -ArgumentList "-ExecutionPolicy Bypass -File `"$PSCommandPath`"" -Verb RunAs
    exit
}

$mysql        = "C:\Program Files\MySQL\MySQL Server 8.0\bin\mysql.exe"
$defaultsFile = "C:\ProgramData\MySQL\MySQL Server 8.0\my.ini"
$utf8NoBom    = New-Object System.Text.UTF8Encoding $false

function Write-MyIni($extraLines) {
    $content = [System.IO.File]::ReadAllText($defaultsFile)
    # Limpiar entradas previas
    $content = $content -replace "\nskip-grant-tables", ""
    $content = $content -replace "\nenable-named-pipe", ""
    if ($extraLines) {
        $content = $content -replace "\[mysqld\]", "[mysqld]`n$extraLines"
    }
    [System.IO.File]::WriteAllText($defaultsFile, $content, $utf8NoBom)
}

# --- PASO 1: Limpiar my.ini y arrancar MySQL normal ---
Write-Host "Limpiando my.ini y arrancando MySQL..." -ForegroundColor Yellow
Write-MyIni $null
net stop MySQL80 2>&1 | Out-Null
Start-Sleep -Seconds 2
net start MySQL80
Start-Sleep -Seconds 5

# Verificar si ya funciona con password 'root'
$test = & $mysql -u root -proot -e "SELECT 'OK';" 2>&1
if ($test -match "OK") {
    Write-Host "Conexion exitosa con password 'root'. Todo listo." -ForegroundColor Green
    Write-Host ""; Read-Host "Presiona Enter para cerrar"
    exit
}

# --- PASO 2: Activar skip-grant-tables + named pipe ---
Write-Host "Activando skip-grant-tables con named pipe..." -ForegroundColor Yellow
Write-MyIni "skip-grant-tables`nenable-named-pipe"

net stop MySQL80
Start-Sleep -Seconds 3
net start MySQL80
Start-Sleep -Seconds 6

# Esperar conexion por named pipe
Write-Host "Esperando MySQL por named pipe..." -ForegroundColor Yellow
$ready = $false
for ($i = 0; $i -lt 15; $i++) {
    Start-Sleep -Seconds 2
    $test = & $mysql -u root --protocol=pipe -e "SELECT 1;" 2>&1
    if ($test -notmatch "Can't connect|ERROR 2003|ERROR 2002|ERROR 2006") {
        $ready = $true
        break
    }
    Write-Host "  Intento $($i+1)..." -ForegroundColor Gray
}

if ($ready) {
    Write-Host "Reseteando contrasena a 'root'..." -ForegroundColor Yellow
    & $mysql -u root --protocol=pipe -e "FLUSH PRIVILEGES; ALTER USER 'root'@'localhost' IDENTIFIED BY 'root';" 2>&1
    Write-Host "Contrasena reseteada." -ForegroundColor Green
} else {
    Write-Host "No se pudo conectar por named pipe." -ForegroundColor Red
}

# --- PASO 3: Restaurar my.ini y reiniciar ---
Write-Host "Restaurando my.ini y reiniciando MySQL..." -ForegroundColor Yellow
Write-MyIni $null
net stop MySQL80
Start-Sleep -Seconds 3
net start MySQL80
Start-Sleep -Seconds 5

Write-Host "Verificando conexion final..." -ForegroundColor Yellow
$final = & $mysql -u root -proot -e "SELECT 'Conexion exitosa' AS resultado;" 2>&1
Write-Host $final

Write-Host ""; Read-Host "Presiona Enter para cerrar"
