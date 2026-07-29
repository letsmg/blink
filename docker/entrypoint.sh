#!/bin/bash
set -e

echo "=== Blink Entrypoint ==="

# Wait for PostgreSQL
echo "Waiting for PostgreSQL..."
until PGPASSWORD="$DB_PASSWORD" psql -h "$DB_HOST" -U "$DB_USERNAME" -d "$DB_DATABASE" -c '\q' 2>/dev/null; do
    echo "PostgreSQL is unavailable - sleeping"
    sleep 2
done
echo "PostgreSQL is up!"

# Install Composer dependencies if vendor is missing
if [ ! -d "/var/www/vendor" ]; then
    echo "Installing Composer dependencies..."
    composer install --no-interaction --optimize-autoloader
fi

# Install Node dependencies and build if public/build is missing
if [ ! -d "/var/www/public/build" ] && [ -f "/var/www/package.json" ]; then
    echo "Building frontend assets..."
    npm ci && npm run build
fi

# Run Laravel setup
echo "Running Laravel setup..."
php artisan key:generate --force --no-interaction
php artisan storage:link --force --no-interaction
php artisan migrate --force --no-interaction

# Clear and cache config
php artisan config:clear
php artisan route:clear
php artisan view:clear

echo "Entrypoint complete. Starting $@"
exec "$@"