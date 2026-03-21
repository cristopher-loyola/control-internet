# Guía Completa de Instalación - Control Internet

## 📋 Descripción del Proyecto

**Control Internet** es una aplicación web desarrollada con:
- **Backend**: Laravel 12 (PHP 8.3+)
- **Frontend**: React 19 + TailwindCSS
- **Base de Datos**: SQLite (configuración por defecto)
- **Build Tool**: Vite
- **Gestor de Paquetes**: Composer (PHP) y npm (Node.js)

## 🔧 Requisitos del Sistema

### Software Requerido
- **PHP**: 8.3.30 o superior
- **Node.js**: 22.22.0 o superior  
- **npm**: 10.9.4 o superior
- **Composer**: 2.9.4 o superior
- **Sistema Operativo**: Windows 10/11 (con Laragon recomendado)

### Extensiones PHP Necesarias
- bcmath
- calendar
- Core
- ctype
- curl
- date
- dom
- exif
- fileinfo
- filter
- gd
- hash
- iconv
- intl
- json
- libxml
- mbstring
- mysqli
- mysqlnd
- openssl
- pcre
- PDO
- pdo_mysql
- pdo_sqlite
- Phar
- random
- readline
- Reflection
- session
- SimpleXML
- sodium
- SPL
- sqlite3
- standard
- tokenizer
- xml
- xmlreader
- xmlwriter
- xsl
- zlib

## 📦 Proceso de Instalación Completo

### Paso 1: Verificación de Herramientas

```powershell
# Verificar PHP
& "C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe" --version

# Verificar Node.js
& "C:\laragon\bin\nodejs\node-v22\node.exe" --version

# Verificar Composer
& "C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe" "C:\laragon\bin\composer\composer.phar" --version

# Verificar npm
& "C:\laragon\bin\nodejs\node-v22\npm.cmd" --version
```

### Paso 2: Instalación de Dependencias PHP

```powershell
# Instalar dependencias de Composer
& "C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe" "C:\laragon\bin\composer\composer.phar" install --no-scripts --prefer-source

# Generar autoload
& "C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe" "C:\laragon\bin\composer\composer.phar" dump-autoload --no-scripts
```

### Paso 3: Instalación de Dependencias Node.js

```powershell
# Configurar PATH para Node.js
$env:PATH = "C:\laragon\bin\nodejs\node-v22;" + $env:PATH

# Instalar dependencias de npm
& "C:\laragon\bin\nodejs\node-v22\npm.cmd" install
```

### Paso 4: Configuración del Entorno

```powershell
# Crear archivo .env desde el ejemplo
Copy-Item .env.example .env

# Generar key de la aplicación
& "C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe" artisan key:generate
```

### Paso 5: Configuración de Base de Datos

```powershell
# Ejecutar migraciones (creará automáticamente la base de datos SQLite)
& "C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe" artisan migrate

# Ejecutar seeders para datos iniciales
& "C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe" artisan db:seed
```

### Paso 6: Construcción de Assets Frontend

```powershell
# Construir assets para producción
$env:PATH = "C:\laragon\bin\nodejs\node-v22;" + $env:PATH
& "C:\laragon\bin\nodejs\node-v22\npm.cmd" run build
```

### Paso 7: Iniciar Servidor de Desarrollo

```powershell
# Iniciar servidor Laravel
& "C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe" artisan serve --host=0.0.0.0 --port=8000
```

## Acceso a la Aplicación

Una vez completada la instalación, accede a la aplicación en:
- **URL**: http://localhost:8000
- **Usuario Admin**: Creado automáticamente mediante seeder
- **Panel de Administración**: Disponible después del login

## Estructura de Archivos Importantes

```
control-internet/
├── .env                    # Configuración del entorno
├── composer.json          # Dependencias PHP
├── package.json           # Dependencias Node.js
├── database/
│   ├── database.sqlite    # Base de datos SQLite (se crea automáticamente)
│   └── migrations/        # Migraciones de la base de datos
├── vendor/                # Dependencias PHP instaladas
├── node_modules/          # Dependencias Node.js instaladas
├── public/build/          # Assets compilados
├── resources/             # Vistas y assets frontend
├── routes/                # Rutas de la aplicación
└── storage/               # Archivos de almacenamiento
```

## 🔍 Verificación de Funcionamiento

### Tests Básicos
```powershell
# Verificar conexión a base de datos
& "C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe" artisan tinker
> DB::connection()->getPdo();

# Verificar rutas
& "C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe" artisan route:list

# Verificar configuración
& "C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe" artisan config:cache
```

### Funcionalidades a Verificar
1. Acceso a la página principal
2. Sistema de login/autenticación
3. Panel de administración
4. Gestión de usuarios
5. Control de operaciones de internet
6. Sistema de facturación
7. Reportes y estadísticas

##  Solución de Problemas Comunes

### Problema 1: "php no se reconoce como comando"
**Solución**: Usar la ruta completa de PHP en Laragon:
```powershell
& "C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe" [comando]
```

### Problema 2: Error de dependencias Composer
**Solución**: Instalar con --no-scripts --prefer-source:
```powershell
& "C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe" "C:\laragon\bin\composer\composer.phar" install --no-scripts --prefer-source
```

### Problema 3: npm no encuentra node
**Solución**: Configurar PATH antes de usar npm:
```powershell
$env:PATH = "C:\laragon\bin\nodejs\node-v22;" + $env:PATH
```

### Problema 4: Base de datos SQLite no existe
**Solución**: Las migraciones crearán automáticamente la base de datos. Asegúrate de tener permisos de escritura en la carpeta `database/`.

### Problema 5: Error de permisos en Windows
**Solución**: Ejecutar PowerShell como Administrador o verificar permisos en las carpetas del proyecto.

### Problema 6: Assets no se cargan correctamente
**Solución**: Reconstruir los assets:
```powershell
$env:PATH = "C:\laragon\bin\nodejs\node-v22;" + $env:PATH
& "C:\laragon\bin\nodejs\node-v22\npm.cmd" run build
& "C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe" artisan config:cache
```

##  Comandos de Mantenimiento

### Desarrollo
```powershell
# Iniciar servidor desarrollo
& "C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe" artisan serve

# Modo desarrollo para assets
$env:PATH = "C:\laragon\bin\nodejs\node-v22;" + $env:PATH
& "C:\laragon\bin\nodejs\node-v22\npm.cmd" run dev
```

### Producción
```powershell
# Optimizar aplicación
& "C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe" artisan config:cache
& "C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe" artisan route:cache
& "C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe" artisan view:cache

# Construir assets para producción
$env:PATH = "C:\laragon\bin\nodejs\node-v22;" + $env:PATH
& "C:\laragon\bin\nodejs\node-v22\npm.cmd" run build
```

### Base de Datos
```powershell
# Nueva migración
& "C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe" artisan make:migration nombre_migration

# Resetear base de datos
& "C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe" artisan migrate:fresh --seed
```

## Notas Adicionales

1. **Laragon**: Este proyecto está configurado para funcionar óptimamente con Laragon en Windows
2. **SQLite**: La configuración por defecto usa SQLite para simplicidad, pero puede cambiarse a MySQL/PostgreSQL
3. **Assets**: Los assets se compilan en `public/build/` y son gestionados por Vite
4. **Logs**: Los errores se registran en `storage/logs/laravel.log`
5. **Cache**: Limpiar cache si hay problemas de configuración: `php artisan cache:clear`

##  Resumen de Instalación Exitosa

**PHP 8.3.30** - Verificado y funcionando  
**Node.js 22.22.0** - Verificado y funcionando  
**Composer 2.9.4** - Dependencias PHP instaladas  
**npm 10.9.4** - Dependencias Node.js instaladas  
**Base de Datos SQLite** - Migraciones ejecutadas  
**Datos Iniciales** - Seeders ejecutados  
**Application Key** - Generada correctamente  
**Assets Frontend** - Construidos exitosamente  
**Servidor Web** - Corriendo en http://localhost:8000  

La aplicación está lista para usar en el entorno de desarrollo.
