#!/bin/bash

# deploy-railway.sh - Script para ejecutar en el "Pre-deploy Command" de Railway
# Asume que las dependencias de Composer y Node ya están instaladas,
# y los assets de frontend ya están construidos por Railway (Nixpacks o Build Command).
# Asume que APP_KEY, APP_ENV, DB_*, etc., están configuradas como variables de entorno en Railway.

echo "--- Iniciando script de pre-despliegue de Lakasir en Railway ---"

# Opcional: Poner la aplicación en modo mantenimiento
# if [[ "$MAINTENANCE_MODE" = "true" ]]; then
#   echo "Entrando en modo mantenimiento..."
#   php artisan down || echo "Fallo al entrar en modo mantenimiento."
# fi

echo "Limpiando cachés de Laravel..."
php artisan optimize:clear

# Los comandos de caché de producción se ejecutan si APP_ENV es 'production'
# (deberías tener APP_ENV=production en tus variables de Railway).
echo "Recacheando configuración si APP_ENV=production..."
if [[ "$APP_ENV" = "production" ]]; then
  php artisan config:cache
  php artisan event:cache
  php artisan route:cache
  php artisan view:cache
else
  echo "APP_ENV no es 'production' (es '$APP_ENV'), omitiendo cachés de optimización."
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
php artisan storage:link || echo "Enlace de storage ya existe o falló al crear."

# CREACIÓN DE USUARIO:
# El comando `php artisan app:create-user` es interactivo. No funcionará bien aquí.
# Debes crear tu usuario inicial de otra manera:
# 1. Manualmente después del primer despliegue exitoso, usando la shell de Railway si la encuentras.
# 2. Creando un Seeder específico que cree tu usuario admin y llamándolo aquí:
#    echo "Ejecutando seeder para usuario inicial..."
#    php artisan db:seed --class=MiUsuarioAdminSeeder --force
# 3. O si tu DatabaseSeeder principal ya crea el usuario y quieres correr todos los seeders:
#    echo "Ejecutando todos los seeders..."
#    php artisan db:seed --force
# Por ahora, se omite la creación automática de usuario. Hazlo manualmente después.
echo "Recordatorio: Crea el usuario inicial manualmente si es el primer despliegue."

# Opcional: Salir de modo mantenimiento
# if [[ "$MAINTENANCE_MODE" = "true" ]]; then
#   echo "Saliendo de modo mantenimiento..."
#   php artisan up || echo "Fallo al salir de modo mantenimiento."
# fi

echo "--- Script de pre-despliegue de Lakasir completado ---"
