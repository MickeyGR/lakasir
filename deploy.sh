#!/bin/bash

# deploy.sh - Script para ejecutar en el "Pre-deploy Command" de Railway
# Asume que composer install, npm install y npm run build ya fueron ejecutados
# por el buildpack de Railway o en el "Build Command" de Railway.

echo "--- Iniciando script de despliegue de Lakasir ---"

# Poner la aplicación en modo mantenimiento (opcional, si las migraciones son largas)
# Esto requiere que la variable de entorno MAINTENANCE_MODE esté disponible si se quiere controlar.
# if [[ "$MAINTENANCE_MODE" = "true" ]]; then
#   echo "Entrando en modo mantenimiento..."
#   php artisan down || echo "Fallo al entrar en modo mantenimiento (puede que ya esté abajo o APP_KEY no lista)."
# fi

# Asegurar que APP_KEY existe (Railway debería proveer esto como variable de entorno)
# Si APP_KEY es una variable de entorno, key:generate no es estrictamente necesario aquí
# a menos que quieras que se escriba en un archivo .env que el buildpack genere.
# La mayoría de las plataformas PaaS inyectan APP_KEY como variable de entorno.
# Comentado por ahora, ya que el entrypoint de nuestro Dockerfile lo hacía y
# en un escenario sin Dockerfile, se espera que Railway lo provea.
# echo "Verificando APP_KEY..."
# php artisan key:generate --force --no-interaction # El --force es para evitar prompts

echo "Limpiando cachés de Laravel..."
php artisan optimize:clear

echo "Recacheando configuración (si APP_ENV=production)..."
# Los comandos de caché solo tienen sentido si APP_ENV es 'production'.
# Railway debería setear APP_ENV=production en tus variables de entorno.
if [[ "$APP_ENV" = "production" ]]; then
  php artisan config:cache
  php artisan event:cache
  php artisan route:cache
  php artisan view:cache
else
  echo "APP_ENV no es 'production', omitiendo cachés de optimización."
fi

echo "Ejecutando migraciones centrales de Tenancy..."
php artisan migrate --force # Para tablas como tenants, domains, y otras centrales.

echo "Ejecutando migraciones de Tenant de Lakasir..."
php artisan migrate --path=database/migrations/tenant --force

# Publicar assets de Filament y Livewire
echo "Publicando assets de Filament y Livewire..."
php artisan filament:assets
php artisan livewire:publish --assets

# Crear enlace simbólico de storage
echo "Creando enlace de storage..."
php artisan storage:link || echo "Enlace de storage ya existe o falló al crear (esto puede ser normal)."

# El comando php artisan app:create-user es interactivo.
# NO DEBERÍA ejecutarse en un script automático a menos que lo modifiques
# para que tome los datos de variables de entorno o argumentos, o uses seeders.
# echo "Considera ejecutar 'php artisan app:create-user' manualmente después del despliegue si es necesario."
# O si tienes un seeder que crea el usuario inicial y quieres correr todos los seeders:
# echo "Ejecutando seeders..."
# php artisan db:seed --force

# Salir de modo mantenimiento (si se activó)
# if [[ "$MAINTENANCE_MODE" = "true" ]]; then # O siempre intentar levantarlo si no se controla con variable
#   echo "Saliendo de modo mantenimiento..."
#   php artisan up || echo "Fallo al salir de modo mantenimiento."
# fi

echo "--- Script de despliegue de Lakasir completado ---"
