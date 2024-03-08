# syntax=docker/dockerfile:1

# ---------------------------------------------------------------------------
# Stage 1 — vendor: resolve PHP dependencies with the composer image so that
# the runtime image never carries composer or the dev requirements.
# ---------------------------------------------------------------------------
FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install \
        --no-dev \
        --no-scripts \
        --no-interaction \
        --prefer-dist \
        --optimize-autoloader

# ---------------------------------------------------------------------------
# Stage 2 — app: PHP-FPM runtime. No web server here; nginx lives in the
# `web` stage below and talks to this container over the compose network.
# ---------------------------------------------------------------------------
FROM php:8.3-fpm-alpine AS app

# pdo_mysql for the database, opcache because this is a request-per-process
# runtime and the framework is thousands of files.
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql opcache bcmath \
    && apk del .build-deps

COPY docker/php.ini /usr/local/etc/php/conf.d/99-app.ini

WORKDIR /var/www/html

COPY --from=vendor /app/vendor ./vendor
COPY . .

# storage/ and bootstrap/cache are the only paths the app writes to.
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwX storage bootstrap/cache

COPY docker/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

# Never run the application as root.
USER www-data

EXPOSE 9000

# php-fpm answers FastCGI, not HTTP, so the check asks the framework to boot
# rather than curling a URL.
HEALTHCHECK --interval=30s --timeout=5s --start-period=30s --retries=3 \
    CMD php -r 'exit(is_dir("/var/www/html/vendor") ? 0 : 1);' \
        && php artisan --version > /dev/null || exit 1

ENTRYPOINT ["entrypoint"]
CMD ["php-fpm"]

# ---------------------------------------------------------------------------
# Stage 3 — web: nginx serving public/ and proxying PHP to the app container.
# Only the public directory is copied; application code never sits inside the
# web server's document root.
# ---------------------------------------------------------------------------
FROM nginx:1.27-alpine AS web

COPY docker/nginx.conf /etc/nginx/conf.d/default.conf
COPY public /var/www/html/public

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
    CMD wget -qO- http://127.0.0.1/ > /dev/null || exit 1
