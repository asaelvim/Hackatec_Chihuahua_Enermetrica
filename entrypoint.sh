#!/bin/sh
set -e

# Instala dependencias de Composer si vendor/ no existe aún (primer arranque).
if [ ! -d "/var/www/html/vendor" ]; then
    composer install --no-interaction --prefer-dist
fi

# Genera APP_KEY si falta (solo en desarrollo; en producción se hace una vez al desplegar).
if [ -f "/var/www/html/artisan" ] && ! grep -q "^APP_KEY=base64" /var/www/html/.env 2>/dev/null; then
    php /var/www/html/artisan key:generate --force
fi

chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true

exec apache2-foreground
