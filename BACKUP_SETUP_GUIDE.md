# Guia de Configuracion del Backup Automatico

## Resumen
El sistema genera backups diarios de la base de datos y archivos, enviandolos por email a las **4:45 PM** (configurable).

---

## 1. Configuracion Inicial (Cualquier Computadora)

### 1.1 Instalar Dependencias
```bash
composer install
```

### 1.2 Configurar Variables de Entorno (.env)
```env
# Email para recibir backups
BACKUP_EMAIL=tu-email@gmail.com

# Configuracion de email (Gmail ejemplo)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=tu-email@gmail.com
MAIL_PASSWORD=tu-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=tu-email@gmail.com
MAIL_FROM_NAME="Control Internet"
```

### 1.3 Configurar Ruta de MySQLDump (Windows con Laragon)
En `config/database.php`, agregar dentro de la configuracion 'mysql':
```php
'dump' => [
    'dump_binary_path' => 'C:\\laragon\\bin\\mysql\\mysql-8.4.3-winx64\\bin',
    'use_single_transaction' => true,
],
```

**Nota:** Ajusta la ruta segun tu version de MySQL en Laragon.

---

## 2. Configuracion del Programador de Tareas

### Opcion A: Windows (Task Scheduler)

#### Metodo 1: Script Automatico
1. Abre terminal como Administrador en la carpeta del proyecto
2. Ejecuta:
```bash
setup-scheduler.bat
```

#### Metodo 2: Manual
1. Abre "Task Scheduler" (Programador de Tareas)
2. Crea nueva tarea basica:
   - **Nombre:** Laravel Scheduler
   - **Trigger:** Diario, cada 1 minuto
   - **Action:** Iniciar programa
   - **Programa:** `C:\laragon\bin\php\php.exe` (ajusta la ruta)
   - **Argumentos:** `artisan schedule:run`
   - **Iniciar en:** `C:\laragon\www\control-internet`

### Opcion B: Linux / Servidor (CRON)

#### Para Servidor Linux (Recomendado)
1. Edita el crontab:
```bash
crontab -e
```

2. Agrega esta linea:
```bash
* * * * * cd /ruta/del/proyecto && /usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

3. Verifica que esta instalado:
```bash
which mysqldump    # Debe mostrar /usr/bin/mysqldump
```

#### Verificar Configuracion MySQL en Linux
En `config/database.php`, asegurate de que la seccion 'dump' use la ruta correcta o dejala vacia si mysqldump esta en el PATH:
```php
'dump' => [
    'dump_binary_path' => '/usr/bin',  # Linux usualmente
    'use_single_transaction' => true,
],
```

---

## 3. Verificacion

### Verificar que las tareas estan registradas:
```bash
php artisan schedule:list
```

Debe mostrar:
```
30 1  * * *  php artisan backup:clean ...... Next Due: ...
45 16 * * *  php artisan backup:email ...... Next Due: ...
```

### Ejecutar manualmente (para prueba):
```bash
php artisan backup:email
```

### Verificar que se guardo localmente:
Los backups se guardan en:
- **Windows:** `storage/app/private/control-internet/`
- **Linux:** `storage/app/private/control-internet/`

---

## 4. Cambiar Horario del Backup

Edita `routes/console.php`:
```php
Schedule::command('backup:email')
    ->daily()
    ->at('16:45')  // Cambia la hora aqui (formato 24h)
    ->onFailure(function () {
        \Log::error('Backup por email fallo');
    });
```

Despues limpia cache:
```bash
php artisan config:clear
```

---

## 5. Solucion de Problemas

### Error: "mysqldump no se reconoce"
**Solucion:** Configurar `dump_binary_path` en `config/database.php` con la ruta correcta a mysqldump.

### Error: "No scheduled commands are ready to run"
**Causa:** El scheduler corre cada minuto, pero el backup esta programado para una hora especifica.
**Solucion:** Espera a la hora programada o cambiala a una hora cercana para probar.

### Error: "No se encuentra el backup"
**Causa:** El backup se genera pero no se encuentra al momento de enviar.
**Verificacion:** Revisa que la carpeta `storage/app/private/control-internet/` exista y tenga permisos de escritura.

---

## 6. Resumen de Comandos Utiles

```bash
# Ejecutar backup manual (con email)
php artisan backup:email

# Solo generar backup local (sin email)
php artisan backup:run

# Limpiar backups antiguos
php artisan backup:clean

# Ver tareas programadas
php artisan schedule:list

# Verificar scheduler
php artisan schedule:run

# Limpiar cache de configuracion
php artisan config:clear
php artisan cache:clear
```

---

## Notas Importantes

1. **La tarea programada debe estar activa** (Task Scheduler en Windows, CRON en Linux)
2. **Sin la tarea programada**, el backup no se ejecutara automaticamente
3. **Se mantienen backups por 15 dias** (configurable en `config/backup.php`)
4. **El email se envia con el archivo adjunto** (.zip)

## Archivos Modificados en este Proyecto

- `routes/console.php` - Define las tareas programadas
- `config/database.php` - Configuracion de mysqldump
- `config/backup.php` - Configuracion del backup
- `app/Console/Commands/BackupAndEmail.php` - Comando personalizado
