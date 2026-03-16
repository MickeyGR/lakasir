#!/bin/sh

set -eu

php artisan storage:link || true
php artisan optimize:clear
php artisan optimize

([ -e /app/storage ] && chmod -R ugo+w /app/storage)
perl /assets/transform-config.pl /assets/nginx.template.conf /nginx.conf
echo "Server starting on port $PORT"

php-fpm -y /assets/php-fpm.conf &
exec nginx -c /nginx.conf
