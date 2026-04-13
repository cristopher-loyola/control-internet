@echo off
chcp 65001 >nul
echo =========================================
echo  Verificando configuracion del Scheduler
echo =========================================
echo.

REM Verificar si la tarea existe
echo [1] Verificando tarea programada...
schtasks /query /tn "Laravel Scheduler" >nul 2>&1
if %errorlevel% equ 0 (
    echo     [OK] Tarea "Laravel Scheduler" existe
echo.
    schtasks /query /tn "Laravel Scheduler" /fo list | findstr /i "proxima hora estado"
) else (
    echo     [X] Tarea NO encontrada - ejecuta setup-scheduler.bat primero
)

echo.
echo [2] Probando comando manualmente...
cd /d "%~dp0"
php artisan schedule:run --dry-run 2>nul || php artisan schedule:run

echo.
echo [3] Proximas tareas programadas:
php artisan schedule:list 2>nul || echo     No se pudo obtener la lista

echo.
echo =========================================
echo  Para que funcione automaticamente:
echo  - La tarea debe aparecer en el paso 1
echo  - Debe mostrar el backup a las 16:45
echo =========================================
pause
