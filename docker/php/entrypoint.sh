#!/bin/sh
set -e

# Cache config/routes at runtime (env vars are only available here, not at build time).
php artisan config:cache
php artisan route:cache

exec "$@"
