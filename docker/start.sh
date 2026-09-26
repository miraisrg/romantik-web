#!/bin/sh
set -e

# Configure port dynamically based on Render's $PORT (fallback to 8080)
PORT="${PORT:-8080}"
echo "=> Setting Nginx listen port to ${PORT}..."
sed -i "s/__PORT__/${PORT}/g" /etc/nginx/conf.d/default.conf

# Ensure critical Laravel storage directories exist at runtime
echo "=> Ensuring storage and cache directories exist..."
mkdir -p \
    /var/www/html/storage/app/data \
    /var/www/html/storage/framework/cache/data \
    /var/www/html/storage/framework/sessions \
    /var/www/html/storage/framework/views \
    /var/www/html/storage/logs \
    /var/www/html/bootstrap/cache \
    /var/lib/nginx/tmp \
    /var/log/nginx \
    /run/nginx

# Ensure correct permissions for www-data user
chown -R www-data:www-data \
    /var/www/html/storage \
    /var/www/html/bootstrap/cache \
    /var/lib/nginx \
    /var/log/nginx \
    /run/nginx

chmod -R 775 \
    /var/www/html/storage \
    /var/www/html/bootstrap/cache

# Clear stale caches to read fresh runtime environment variables from Render
echo "=> Clearing runtime configuration and view caches..."
php artisan config:clear || true
php artisan view:clear || true
php artisan route:clear || true

# Verify rule_catalog.json presence
if [ -f "/var/www/html/storage/app/data/rule_catalog.json" ]; then
    echo "=> OK: Rule catalog verified at /var/www/html/storage/app/data/rule_catalog.json"
else
    echo "=> WARNING: /var/www/html/storage/app/data/rule_catalog.json is not found!"
fi

echo "=> Launching Supervisord (PHP-FPM + Nginx)..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
