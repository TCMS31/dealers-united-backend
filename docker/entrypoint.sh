#!/bin/sh
set -e

# Cache configuration, routes and events on boot. These caches are written to
# bootstrap/cache, which is the only writable path in the image besides
# storage/, and they are what makes a cold request fast.
if [ "${APP_SKIP_CACHE:-false}" != "true" ]; then
    php artisan config:cache
    php artisan route:cache
    php artisan event:cache
fi

exec "$@"
