# Dockerfile

# --- Etapa 1: Dependencias de PHP (Composer) ---
# Usamos una imagen oficial de Composer.
# Esta etapa instala las dependencias de Composer y las extensiones PHP necesarias para ello.
FROM composer:2.7 AS vendor_stage
WORKDIR /app

# Instalar dependencias del sistema y extensiones PHP necesarias para composer install
# La imagen composer:2.7 se basa en PHP Alpine, así que usamos apk.
# $PHPIZE_DEPS contiene las herramientas de construcción como autoconf, g++, make.
RUN apk add --no-cache \
        $PHPIZE_DEPS \
        icu-dev \
        libzip-dev \
        libpng-dev \
        libjpeg-turbo-dev \
        freetype-dev && \
    docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install -j$(nproc) \
        intl \
        gd \
        zip

# Copiar archivos de definición de dependencias.
COPY composer.json composer.lock ./

# Instalar dependencias de producción, optimiza el autoloader.
# --no-scripts evita que se ejecuten scripts de composer que podrían fallar si .env no existe aún.
RUN composer install --no-dev --no-interaction --no-scripts --optimize-autoloader

# --- Etapa 2: Construcción de Assets (Node.js) ---
# Usando Node 22-alpine según solicitado.
FROM node:22-alpine AS assets_stage
WORKDIR /app

# Copiar archivos de definición de dependencias de frontend.
COPY package.json package-lock.json ./

# Instalar dependencias usando NPM de forma limpia desde el lockfile.
RUN npm ci

# ANTES de copiar el resto del código y construir los assets,
# copiar la carpeta 'vendor' desde la etapa 'vendor_stage'.
# Esto es necesario si el proceso de build de frontend (ej. Tailwind)
# necesita acceder a archivos dentro de 'vendor' (ej. presets).
COPY --from=vendor_stage /app/vendor /app/vendor

# Copiar el resto del código fuente para tener acceso a los archivos de assets.
# (vendor está en .dockerignore, por lo que no se copiará desde el host aquí,
# usamos la copia limpia de vendor_stage).
COPY . .

# Ejecutar el script de construcción de assets usando NPM.
RUN npm run build

# --- Etapa 3: Imagen Final de Producción ---
# Imagen base de PHP 8.1 con FPM sobre Alpine Linux (Lakasir requiere PHP 8.1).
FROM php:8.2-fpm-alpine

WORKDIR /var/www/html

# Variables de entorno para configuración de PHP y Laravel dentro del contenedor.
ENV PHP_UPLOAD_MAX_FILESIZE=20M \
    PHP_POST_MAX_SIZE=20M \
    PHP_MEMORY_LIMIT=256M \
    PHP_MAX_EXECUTION_TIME=300 \
    APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    CACHE_DRIVER=file \
    SESSION_DRIVER=file \
    QUEUE_CONNECTION=sync \
    DB_CONNECTION=mysql

# Instalar dependencias del sistema necesarias para Laravel y el servidor web.
RUN apk add --no-cache \
    nginx \
    supervisor \
    curl \
    libzip-dev \
    zip \
    unzip \
    icu-libs \
    icu-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    mysql-client \
    oniguruma-dev \
    libxml2-dev \
    sqlite-dev

# Configurar e instalar extensiones PHP para la aplicación final.
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo_mysql \
    pdo_sqlite \
    bcmath \
    gd \
    intl \
    zip \
    exif \
    mbstring \
    opcache \
    pcntl

# Limpiar cache de apk para reducir el tamaño de la imagen.
RUN rm -rf /var/cache/apk/*

# Copiar el código de la aplicación.
COPY . .

COPY .env.example /var/www/html/.env.example

# Copiar las dependencias de Composer desde la etapa 'vendor_stage'.
COPY --from=vendor_stage /app/vendor /var/www/html/vendor

# Copiar los assets construidos desde la etapa 'assets_stage'.
COPY --from=assets_stage /app/public/build /var/www/html/public/build

# Copiar la configuración de Nginx.
COPY .docker/nginx/default.conf /etc/nginx/http.d/default.conf

# Copiar la configuración de Supervisor.
COPY .docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Crear directorios necesarios para Laravel y establecer permisos.
RUN mkdir -p storage/framework/{sessions,views,cache} \
    && mkdir -p storage/logs \
    && mkdir -p bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache \
    && chmod -R ug+w storage bootstrap/cache

# Script de entrada para ejecutar optimizaciones de Laravel y luego iniciar Supervisor.
RUN echo "#!/bin/sh" > /usr/local/bin/docker-entrypoint.sh \
    && echo "set -e" >> /usr/local/bin/docker-entrypoint.sh \
    && echo "echo 'Running Laravel entrypoint script...'" >> /usr/local/bin/docker-entrypoint.sh \
    && echo "echo 'Listing /var/www/html contents:'" >> /usr/local/bin/docker-entrypoint.sh \
    && echo "ls -la /var/www/html/" >> /usr/local/bin/docker-entrypoint.sh \
    && echo "echo 'Ensuring SQLite database file exists...'" >> /usr/local/bin/docker-entrypoint.sh \
    && echo "mkdir -p /var/www/html/database" >> /usr/local/bin/docker-entrypoint.sh \
    && echo "touch /var/www/html/database/database.sqlite" >> /usr/local/bin/docker-entrypoint.sh \
    && echo "echo 'Ensuring .env file exists...'" >> /usr/local/bin/docker-entrypoint.sh \
    && echo "if [ ! -f /var/www/html/.env ]; then" >> /usr/local/bin/docker-entrypoint.sh \
    && echo "  if [ -f /var/www/html/.env.example ]; then" >> /usr/local/bin/docker-entrypoint.sh \
    && echo "    echo 'Found .env.example, copying to .env...';" >> /usr/local/bin/docker-entrypoint.sh \
    && echo "    cp /var/www/html/.env.example /var/www/html/.env;" >> /usr/local/bin/docker-entrypoint.sh \
    && echo "  else" >> /usr/local/bin/docker-entrypoint.sh \
    && echo "    echo '.env.example NOT found, creating empty .env file...';" >> /usr/local/bin/docker-entrypoint.sh \
    && echo "    touch /var/www/html/.env;" >> /usr/local/bin/docker-entrypoint.sh \
    && echo "  fi" >> /usr/local/bin/docker-entrypoint.sh \
    && echo "else" >> /usr/local/bin/docker-entrypoint.sh \
    && echo "  echo '.env file already exists.';" >> /usr/local/bin/docker-entrypoint.sh \
    && echo "fi" >> /usr/local/bin/docker-entrypoint.sh \
    && echo "echo 'Clearing Laravel cache...'" >> /usr/local/bin/docker-entrypoint.sh \
    && echo "php artisan optimize:clear" >> /usr/local/bin/docker-entrypoint.sh \
    && echo "if [ -z \"\$APP_KEY\" ] || [ \"\$APP_KEY\" = \"\" ]; then echo 'APP_KEY is not set or is empty, generating...'; php artisan key:generate --force; else echo 'APP_KEY is set, not generating.'; fi" >> /usr/local/bin/docker-entrypoint.sh \
    && echo "if [ \"\$APP_ENV\" = \"production\" ]; then" >> /usr/local/bin/docker-entrypoint.sh \
    && echo "  echo 'Production environment detected, caching configurations...'" >> /usr/local/bin/docker-entrypoint.sh \
    && echo "  php artisan config:cache" >> /usr/local/bin/docker-entrypoint.sh \
    && echo "  php artisan route:cache" >> /usr/local/bin/docker-entrypoint.sh \
    && echo "  php artisan view:cache" >> /usr/local/bin/docker-entrypoint.sh \
    && echo "  php artisan event:cache" >> /usr/local/bin/docker-entrypoint.sh \
    && echo "else" >> /usr/local/bin/docker-entrypoint.sh \
    && echo "  echo 'Non-production environment (\$APP_ENV), skipping production caches.'" >> /usr/local/bin/docker-entrypoint.sh \
    && echo "fi" >> /usr/local/bin/docker-entrypoint.sh \
    && echo "php artisan storage:link || echo 'Storage link already exists or failed to create.'" >> /usr/local/bin/docker-entrypoint.sh \
    && echo "echo 'Publishing Filament and Livewire assets...'" >> /usr/local/bin/docker-entrypoint.sh \
    && echo "php artisan filament:assets" >> /usr/local/bin/docker-entrypoint.sh \
    && echo "php artisan livewire:publish --assets" >> /usr/local/bin/docker-entrypoint.sh \
    && echo "echo 'Starting Supervisor...'" >> /usr/local/bin/docker-entrypoint.sh \
    && echo "/usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf" >> /usr/local/bin/docker-entrypoint.sh \
    && chmod +x /usr/local/bin/docker-entrypoint.sh

# Exponer el puerto 80 (HTTP) que Nginx escuchará.
EXPOSE 80

# Comando para iniciar el script de entrada cuando el contenedor arranque.
CMD ["/usr/local/bin/docker-entrypoint.sh"]
