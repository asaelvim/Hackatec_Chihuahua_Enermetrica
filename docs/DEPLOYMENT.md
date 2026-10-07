# Despliegue en DreamHost (hosting compartido)

Esta guía asume un plan de **hosting compartido** de DreamHost con un dominio ya
agregado en el panel, acceso SSH habilitado y una base de datos MySQL creada
desde el panel de DreamHost.

## 1. Requisitos previos en el panel de DreamHost

1. **Dominio**: agrégalo en *Websites → Manage Websites* (o usa un subdominio,
   p. ej. `enermetrica.tudominio.com`).
2. **PHP 8.3**: en la configuración del dominio, cambia la versión de PHP a
   `8.3` (o la más reciente disponible). Laravel 13 requiere `^8.3`.
3. **Base de datos MySQL**: créala en *Databases → MySQL Databases*. Anota:
   host, nombre de BD, usuario y contraseña.
4. **SSH**: actívalo en *Users → Manage Users* para tu usuario de shell.
5. **Composer**: ya viene preinstalado en DreamHost (`composer` disponible por
   SSH). Verifícalo con `composer --version`.

## 2. Estructura de carpetas (clave en hosting compartido)

DreamHost no permite apuntar el *document root* a una subcarpeta como
`public/` (a diferencia de un VPS). La solución recomendada:

1. Sube **todo el proyecto** a una carpeta fuera del directorio web, por
   ejemplo `~/enermetrica-app` (fuera de `~/tudominio.com`).
2. Copia el **contenido** de `public/` dentro de `~/tudominio.com` (el
   directorio que DreamHost sirve como raíz web).
3. Edita `~/tudominio.com/index.php` para que apunte a la ubicación real del
   proyecto:

```php
require __DIR__.'/../enermetrica-app/vendor/autoload.php';
$app = require_once __DIR__.'/../enermetrica-app/bootstrap/app.php';
```

   (Ajusta las rutas relativas según dónde quede `enermetrica-app` respecto a
   `tudominio.com`.)

4. Repite lo mismo para `.htaccess` (cópialo desde `public/.htaccess`).

## 3. Subir el código y dependencias

Por SSH, dentro de `~/enermetrica-app`:

```bash
git clone <tu-repositorio> .
composer install --no-dev --optimize-autoloader
npm ci && npm run build   # si Node está disponible; si no, compílalo localmente
                          # y sube solo la carpeta public/build generada
```

Si DreamHost no tiene Node disponible en el plan compartido, compila los
assets (`npm run build`) en tu máquina local y sube `public/build/` junto con
el resto de `public/`.

## 4. Variables de entorno (`.env`)

Copia `.env.example` a `.env` y ajusta:

```env
APP_NAME=Enermetrica
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tudominio.com
APP_TIMEZONE=America/Chihuahua

DB_CONNECTION=mysql
DB_HOST=mysql.tudominio.com   # host que te da el panel de DreamHost
DB_PORT=3306
DB_DATABASE=nombre_bd
DB_USERNAME=usuario_bd
DB_PASSWORD=contraseña_bd

SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database

MAIL_MAILER=smtp
MAIL_HOST=smtp.dreamhost.com   # o tu proveedor SMTP preferido
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_FROM_ADDRESS=notificaciones@tudominio.com

# Sube el JSON de la cuenta de servicio de Firebase al servidor (fuera del
# document root, por seguridad) y apunta aquí a su ruta absoluta.
FIREBASE_CREDENTIALS=/home/tu-usuario/enermetrica-app/storage/firebase-credentials.json
```

Genera la clave de la app y optimiza:

```bash
php artisan key:generate
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 5. Permisos

```bash
chmod -R 775 storage bootstrap/cache
```

## 6. Cron jobs (panel DreamHost → *Cron Jobs*)

DreamHost no soporta procesos persistentes (`queue:work` como demonio), así
que todo se ejecuta vía cron cada minuto:

**Scheduler de Laravel** (ejecuta los jobs `SummarizeDailyConsumption` y
`MarkOfflineDevices` definidos en `routes/console.php`):

```
* * * * * cd /home/tu-usuario/enermetrica-app && php artisan schedule:run >> /dev/null 2>&1
```

**Cola de notificaciones** (procesa los jobs encolados —correos y push— y se
detiene solo cuando ya no hay pendientes, para no dejar procesos huérfanos):

```
* * * * * cd /home/tu-usuario/enermetrica-app && php artisan queue:work --stop-when-empty --max-time=50 >> /dev/null 2>&1
```

> Nota: si tu plan no soporta cron por minuto o prefieres simplicidad, puedes
> cambiar `QUEUE_CONNECTION=sync` en `.env` para enviar las notificaciones de
> forma inmediata (sin cola), a costa de que la petición HTTP que generó la
> anomalía tarde un poco más en responder.

## 7. Verificación post-despliegue

- Visita `https://tudominio.com/login` y confirma que carga con los estilos
  compilados.
- Revisa `storage/logs/laravel.log` si algo falla.
- Prueba el endpoint de ingesta de la API con un token de dispositivo real.
- Confirma que el cron corre: `tail -f storage/logs/laravel.log` tras el
  minuto en que debería ejecutarse `schedule:run`.

## 8. Actualizaciones futuras

```bash
cd ~/enermetrica-app
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

Si cambiaste assets de frontend, recuerda recompilar (`npm run build`) y subir
de nuevo `public/build/`.
