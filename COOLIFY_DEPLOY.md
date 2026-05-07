# Despliegue de Lakasir en Coolify desde cero

Esta guía resume el flujo usado para desplegar una nueva instancia multi-tenant de Lakasir en Coolify usando:

- repositorio GitHub
- rama `develop`
- MySQL administrado en DigitalOcean
- Redis propio dentro del proyecto de Coolify
- DNS en Cloudflare

## 1. Crear el proyecto en Coolify

- Crear un proyecto nuevo, por ejemplo `atokatl-work-lakasir`.
- Crear el environment `production`.
- Agregar una `Application` desde `Private Repository (with GitHub App)`.

Configuración inicial de la app:

- `Repository`: `lakasir`
- `Branch`: `develop`
- `Build Pack`: `Nixpacks`
- `Base Directory`: `/`
- `Port`: `80`
- `Is it a static site?`: apagado
- `Domain`: `https://pos.atokatl.work`
- `Publish Directory`: vacío
- `Install Command`: vacío
- `Build Command`: vacío
- `Start Command`: vacío

## 2. Base de datos central en DigitalOcean

Crear una base central nueva en el cluster MySQL administrado, por ejemplo:

- `lakasir_pos_atokatl_central`

Usar `doadmin` como usuario.

Importante:

- Este proyecto crea una base por tenant con el patrón `lakasir_{slug}`.
- Si en el mismo cluster ya existe una base con ese nombre, el tenant fallará al registrarse.
- No reutilizar slugs viejos como `storebase` si ya existe `lakasir_storebase`.

Ejemplos seguros:

- `demoatokatl`
- `clienteacentro`
- `clienteanorte`

## 3. Crear Redis propio en Coolify

No reutilizar Redis de otro proyecto.

Crear un recurso `Redis` dentro del mismo proyecto/environment:

- Nombre sugerido: `atokatl-work-lakasir-redis`
- `Image`: `redis:7.2`
- `Username`: `default`
- `Password`: generado por Coolify
- `Enable SSL`: apagado
- `Make it publicly available`: apagado

Usar el `Redis URL (internal)` del recurso para la app.

## 4. Variables de entorno de la app

Generar `APP_KEY` localmente:

```bash
php artisan key:generate --show
```

Variables base:

```env
APP_NAME=Lakasir
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:REEMPLAZAR
APP_URL=https://pos.atokatl.work
APP_CENTRAL_DOMAIN=pos.atokatl.work

DB_CONNECTION=mysql
DB_HOST=db-mysql-atokatl-do-user-14058577-0.l.db.ondigitalocean.com
DB_PORT=25060
DB_DATABASE=lakasir_pos_atokatl_central
DB_USERNAME=doadmin
DB_PASSWORD=REEMPLAZAR
MYSQL_ATTR_SSL_CA=/app/storage/certs/do-ca.crt

CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=sync

REDIS_CLIENT=predis
REDIS_URL=redis://default:REEMPLAZAR@HOST_INTERNO_REDIS:6379/0
REDIS_DB=0
REDIS_CACHE_DB=1
REDIS_PREFIX=lakasir_poslak_database_
CACHE_PREFIX=lakasir_poslak_cache_
SESSION_COOKIE=lakasir_poslak_session

MAIL_MAILER=log
FILESYSTEM_DISK=local

NIXPACKS_PHP_ROOT_DIR=/app/public
NIXPACKS_PHP_FALLBACK_PATH=/index.php
```

Notas:

- `REDIS_URL` debe salir del Redis interno creado en Coolify.
- No hace falta duplicar `REDIS_HOST`, `REDIS_PORT` y `REDIS_PASSWORD` si ya se usa `REDIS_URL`.

## 5. Certificado CA de DigitalOcean para MySQL SSL

Si se usa `MYSQL_ATTR_SSL_CA`, el archivo debe existir dentro del contenedor.

Descargar el certificado desde DigitalOcean:

- Cluster MySQL
- `Overview`
- `Connection Details`
- `Download CA certificate`

Luego en la app, ir a `Persistent Storage` y agregar `File Mount`.

Valores:

- `Destination Path`: `/app/storage/certs/do-ca.crt`
- `Content`: pegar el contenido completo del `.crt`

Debe incluir:

```text
-----BEGIN CERTIFICATE-----
...
-----END CERTIFICATE-----
```

## 6. DNS en Cloudflare

Crear estos registros:

- `A` `pos` -> `203.0.113.10`
- `A` `*.pos` -> `203.0.113.10`

Para la primera prueba se puede dejar en `DNS only`.

## 7. Primer deploy

Después de:

- guardar variables
- crear Redis
- montar el archivo CA
- configurar DNS

hacer `Deploy` o `Redeploy`.

Prueba inicial:

- `https://pos.atokatl.work/up`

Si todo está bien, la app ya arrancó correctamente en el dominio central.

## 8. Migraciones centrales

Una vez que `/up` responda, correr:

```bash
php artisan migrate --force
```

Esto crea las tablas centrales como `tenants` y `domains`.

## 9. Registrar el primer tenant

Abrir:

- `https://pos.atokatl.work/auth/register`

Primer tenant sugerido:

- slug: `demoatokatl`
- dominio esperado: `demoatokatl.pos.atokatl.work`
- base esperada: `lakasir_demoatokatl`

## 10. Problemas comunes

### Error 500 en `/up`

Revisar:

```bash
tail -n 200 storage/logs/laravel.log
```

Si aparece:

```text
Cannot connect to MySQL using SSL
failed loading cafile stream: `/app/storage/certs/do-ca.crt`
```

entonces falta montar el CA en `Persistent Storage`.

### Redis de otro proyecto

No recomendado.

Crear Redis propio para la instancia nueva y usar su URL interna.

### Tenant no se puede registrar

Revisar si ya existe una base con el mismo nombre:

- `lakasir_{slug}`

Cambiar el slug por uno nuevo.

### Dominio central responde pero subdominios no

Confirmar:

- `A pos` creado
- `A *.pos` creado
- tenant registrado en la app

## 11. Resumen mínimo

Orden recomendado:

1. Crear proyecto/app en Coolify.
2. Crear base central en DigitalOcean.
3. Crear Redis propio en Coolify.
4. Configurar variables.
5. Montar CA de MySQL con `File Mount`.
6. Crear DNS `pos` y `*.pos` en Cloudflare.
7. Deploy.
8. Probar `/up`.
9. Ejecutar `php artisan migrate --force`.
10. Registrar `demoatokatl`.
