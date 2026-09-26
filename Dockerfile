# ==============================================================================
# Stage 1: Build Frontend Assets (Vite & Tailwind CSS)
# ==============================================================================
FROM node:22-alpine AS node-builder

WORKDIR /app

# Copy dependency specifications for cached layer installation
COPY package.json package-lock.json ./
RUN npm ci --ignore-scripts

# Copy configuration and frontend source files
COPY vite.config.js ./
COPY resources ./resources
COPY public ./public

# Build production assets (emitted to /app/public/build)
RUN npm run build

# ==============================================================================
# Stage 2: Production PHP Runtime with Nginx and PHP-FPM
# ==============================================================================
FROM php:8.4-fpm-alpine

WORKDIR /var/www/html

# Default container environment variables
ENV PORT=8080 \
    APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    SESSION_DRIVER=file \
    CACHE_STORE=file \
    QUEUE_CONNECTION=sync

# Install system dependencies, Nginx, Supervisor, and fonts required by Dompdf
RUN apk add --no-cache \
    nginx \
    supervisor \
    bash \
    tzdata \
    fontconfig \
    font-dejavu \
    freetype \
    libjpeg-turbo \
    libpng

# Install PHP extensions using official mlocati extension installer
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions \
    bcmath \
    ctype \
    curl \
    dom \
    fileinfo \
    filter \
    gd \
    hash \
    iconv \
    json \
    libxml \
    mbstring \
    opcache \
    openssl \
    pcre \
    pdo_sqlite \
    session \
    simplexml \
    sqlite3 \
    tokenizer \
    xml \
    xmlwriter \
    zip

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# Copy composer definitions for layer caching
COPY composer.json composer.lock ./

# Install production PHP dependencies without running scripts
RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts

# Copy application source code
COPY . .

# Copy compiled frontend assets from node-builder
COPY --from=node-builder /app/public/build /var/www/html/public/build

# Generate optimized autoload mapping for production
RUN composer dump-autoload --optimize --no-dev

# Copy configuration files
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/default.conf /etc/nginx/conf.d/default.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/99-app.ini
COPY docker/www.conf /usr/local/etc/php-fpm.d/zz-app.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/start.sh /usr/local/bin/start.sh

# Ensure proper permissions and storage directory structure
RUN sed -i 's/\r$//' /usr/local/bin/start.sh \
    && chmod +x /usr/local/bin/start.sh \
    && mkdir -p \
        storage/app/data \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
        /var/lib/nginx/tmp \
        /var/log/nginx \
        /run/nginx \
    && chown -R www-data:www-data storage bootstrap/cache /var/lib/nginx /var/log/nginx /run/nginx \
    && chmod -R 775 storage bootstrap/cache

# Port exposed for Render (templated dynamically at container startup)
EXPOSE 8080

# Start services via startup script
CMD ["/usr/local/bin/start.sh"]
