#!/usr/bin/env sh

set -eu

# Rebuild Laravel caches after Coolify mounts persistent volumes.
php artisan storage:link || true
php artisan optimize:clear
php artisan optimize
