#!/usr/bin/env sh

set -eu

sh /app/scripts/coolify-post-deploy.sh
node /assets/scripts/prestart.mjs /app/nginx.template.conf /nginx.conf
php-fpm -y /assets/php-fpm.conf &
exec nginx -c /nginx.conf
