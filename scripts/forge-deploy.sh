#!/usr/bash
#
# Laravel Forge deploy script (paste into Site → Deployment Script).
# Forge sets: FORGE_SITE_PATH, FORGE_SITE_BRANCH, FORGE_COMPOSER, etc.
#
# For zero-downtime sites, FORGE_SITE_PATH is the site root;
# the active release is at $FORGE_SITE_PATH/current

set -e

cd "$FORGE_SITE_PATH/current"

git pull origin "$FORGE_SITE_BRANCH"

$FORGE_COMPOSER install --no-dev --optimize-autoloader --no-interaction

# Never load Vite dev server in production
rm -f public/hot

npm ci
npm run build

php artisan migrate --force

# Shared storage lives at site root — ensure log/cache dirs exist and are writable
cd "$FORGE_SITE_PATH"
mkdir -p storage/logs storage/framework/{cache,sessions,views} storage/app/public
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

cd "$FORGE_SITE_PATH/current"

php artisan storage:link 2>/dev/null || true
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

php artisan queue:restart

echo "Deploy complete: $(git log -1 --oneline)"
