#!/usr/bin/env bash
set -e

# Garante permissões adequadas para as pastas do Laravel
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache || true
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache || true

# Executa otimizações apenas fora do ambiente local/dev
if [ "${APP_ENV:-production}" != "local" ]; then
    echo "⚡ Otimizando caches do Laravel..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan event:cache
    
    echo "🔄 Executando migrations..."
    php artisan migrate --force
fi

# Inicia o processo principal definido pelo Docker (ex: php-fpm ou php artisan serve)
exec "$@"