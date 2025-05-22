#!/bin/bash

set -e

echo "Running Laravel setup tasks..."

# Ensure .env exists
if [ ! -f ".env" ]; then
  echo ".env file missing. Exiting."
  exit 1
fi

# Ensure Laravel storage and cache folders are ready
mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache

# Set correct permissions
chown -R www-data:www-data /var/www
chmod -R 755 /var/www

# Run Laravel setup
php artisan config:clear
php artisan config:cache

if [ "$APP_ENV" != "production" ]; then
  php artisan migrate --force || true
else
  php artisan migrate --force
fi

php artisan queue:restart

# Execute passed command (e.g., supervisor)
exec "$@"
