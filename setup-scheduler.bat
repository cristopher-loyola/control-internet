@echo off
chcp 65001 >nul
echo =========================================
echo  Configurando Laravel Task Scheduler
echo =========================================
echo.

REM Obtener la ruta del proyecto
set "PROJECT_PATH=%~dp0"
set "PROJECT_PATH=%PROJECT_PATH:~0,-1%"

REM Detectar ruta de PHP en Laragon
if exist "C:\laragon\bin\php\php.exe" (
    set "PHP_PATH=C:\laragon\bin\php\php.exe"
) else (
    for /d %%i in (C:\laragon\bin\php\php-*) do (
        if exist "%%i\php.exe" (
            set "PHP_PATH=%%i\php.exe"
            goto :found_php
        )
    )
)

:found_php
if not exist "%PHP_PATH%" (
    echo [ERROR] No se encontro PHP en C:\laragon\bin\php\
    echo Por favor verifica tu instalacion de Laragon.
    pause
    exit /b 1
)

echo [INFO] Ruta del proyecto: %PROJECT_PATH%
echo [INFO] PHP encontrado: %PHP_PATH%
echo.

REM Eliminar tarea anterior si existe
schtasks /query /tn "Laravel Scheduler" >nul 2>&1
if %errorlevel% equ 0 (
    echo [INFO] Eliminando tarea anterior...
    schtasks /delete /tn "Laravel Scheduler" /f >nul 2>&1
)

REM Crear archivo XML para la tarea
echo [INFO] Creando tarea programada...
set "XML_FILE=%TEMP%\laravel_scheduler.xml"
(
echo ^<?xml version="1.0" encoding="UTF-16"?^>
echo ^<Task version="1.2" xmlns="http://schemas.microsoft.com/windows/2004/02/mit/task"^>
echo   ^<RegistrationInfo^>
echo     ^<Description^>Ejecuta el scheduler de Laravel cada minuto^</Description^>
echo   ^</RegistrationInfo^>
echo   ^<Triggers^>
echo     ^<CalendarTrigger^>
echo       ^<Repetition^>
echo         ^<Interval^>PT1M^</Interval^>
echo         ^<Duration^>P1D^</Duration^>
echo       ^</Repetition^>
echo       ^<StartBoundary^>2024-01-01T00:00:00^</StartBoundary^>
echo       ^<ScheduleByDay^>
echo         ^<DaysInterval^>1^</DaysInterval^>
echo       ^</ScheduleByDay^>
echo     ^</CalendarTrigger^>
echo   ^</Triggers^>
echo   ^<Principals^>
echo     ^<Principal id="Author"^>
echo       ^<LogonType^>InteractiveToken^</LogonType^>
echo       ^<RunLevel^>LeastPrivilege^</RunLevel^>
echo     ^</Principal^>
echo   ^</Principals^>
echo   ^<Settings^>
echo     ^<MultipleInstancesPolicy^>IgnoreNew^</MultipleInstancesPolicy^>
echo     ^<DisallowStartIfOnBatteries^>false^</DisallowStartIfOnBatteries^>
echo     ^<StopIfGoingOnBatteries^>false^</StopIfGoingOnBatteries^>
echo     ^<AllowHardTerminate^>true^</AllowHardTerminate^>
echo     ^<StartWhenAvailable^>true^</StartWhenAvailable^>
echo     ^<RunOnlyIfNetworkAvailable^>false^</RunOnlyIfNetworkAvailable^>
echo     ^<IdleSettings^>
echo       ^<StopOnIdleEnd^>true^</StopOnIdleEnd^>
echo       ^<RestartOnIdle^>false^</RestartOnIdle^>
echo     ^</IdleSettings^>
echo     ^<AllowStartOnDemand^>true^</AllowStartOnDemand^>
echo     ^<Enabled^>true^</Enabled^>
echo     ^<Hidden^>false^</Hidden^>
echo     ^<RunOnlyIfIdle^>false^</RunOnlyIfIdle^>
echo     ^<WakeToRun^>false^</WakeToRun^>
echo     ^<ExecutionTimeLimit^>PT1H^</ExecutionTimeLimit^>
echo     ^<Priority^>7^</Priority^>
echo   ^</Settings^>
echo   ^<Actions Context="Author"^>
echo     ^<Exec^>
echo       ^<Command^>%PHP_PATH%^</Command^>
echo       ^<Arguments^>artisan schedule:run^</Arguments^>
echo       ^<WorkingDirectory^>%PROJECT_PATH%^</WorkingDirectory^>
echo     ^</Exec^>
echo   ^</Actions^>
echo ^</Task^>
) > "%XML_FILE%"

REM Importar la tarea
schtasks /create /tn "Laravel Scheduler" /xml "%XML_FILE%" /f >nul 2>&1

if %errorlevel% neq 0 (
    echo [ERROR] No se pudo crear la tarea programada.
    echo Intentando metodo alternativo...
    schtasks /create /tn "Laravel Scheduler" /tr "'%PHP_PATH%' artisan schedule:run" /sc minute /mo 1 /sd 01/01/2024 /st 00:00 /rl lowest /f >nul 2>&1
)

REM Verificar que se creo
schtasks /query /tn "Laravel Scheduler" >nul 2>&1
if %errorlevel% equ 0 (
    echo [OK] Tarea "Laravel Scheduler" creada exitosamente!
    echo.
    echo =========================================
    echo  El scheduler correra cada minuto.
    echo  Tu backup se ejecutara a las 4:45 PM
    echo =========================================
) else (
    echo [ERROR] No se pudo verificar la tarea.
)

REM Limpiar archivo temporal
del "%XML_FILE%" 2>nul

echo.
pause
