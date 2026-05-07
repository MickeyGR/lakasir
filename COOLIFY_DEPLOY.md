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

## 7. Routing SaaS en Coolify

Si una sola app debe responder tanto al dominio central como a todos los subdominios de tenants, no basta con el DNS wildcard. La app debe capturar:

- `pos.atokatl.work`
- `*.pos.atokatl.work`

En la aplicación de Coolify:

- `Configuration`
- `Container Labels`

Reemplazar las labels `traefik.*` autogeneradas por un bloque manual como este:

```text
traefik.enable=true
traefik.http.middlewares.gzip.compress=true
traefik.http.middlewares.redirect-to-https.redirectscheme.scheme=https

traefik.http.routers.lakasir-pos-http.entryPoints=http
traefik.http.routers.lakasir-pos-http.middlewares=redirect-to-https
traefik.http.routers.lakasir-pos-http.rule=Host(`pos.atokatl.work`) || HostRegexp(`^.+\.pos\.atokatl\.work$`)
traefik.http.routers.lakasir-pos-http.service=lakasir-pos

traefik.http.routers.lakasir-pos-https.entryPoints=https
traefik.http.routers.lakasir-pos-https.middlewares=gzip
traefik.http.routers.lakasir-pos-https.rule=Host(`pos.atokatl.work`) || HostRegexp(`^.+\.pos\.atokatl\.work$`)
traefik.http.routers.lakasir-pos-https.service=lakasir-pos
traefik.http.routers.lakasir-pos-https.tls=true
traefik.http.routers.lakasir-pos-https.tls.certresolver=letsencrypt

traefik.http.services.lakasir-pos.loadbalancer.server.port=80
```

Después:

- Guardar
- `Redeploy`

Si los tenants se crean pero al abrir `demoatokatl.pos.atokatl.work` aparece `no available server`, casi seguro falta este paso.

## 8. Primer deploy

Después de:

- guardar variables
- crear Redis
- montar el archivo CA
- configurar DNS

hacer `Deploy` o `Redeploy`.

Prueba inicial:

- `https://pos.atokatl.work/up`

Si todo está bien, la app ya arrancó correctamente en el dominio central.

## 9. Migraciones centrales

Una vez que `/up` responda, correr:

```bash
php artisan migrate --force
```

Esto crea las tablas centrales como `tenants` y `domains`.

## 10. Wildcard SSL con Cloudflare y Traefik

Si el navegador sigue mostrando `No seguro`, todavía falta emitir un certificado wildcard para:

- `pos.atokatl.work`
- `*.pos.atokatl.work`

Esto se configura en:

- `Servers`
- `localhost`
- `Proxy`
- `Configuration`

### 10.1. Crear token de Cloudflare

Ir a:

- `Cloudflare`
- `My Profile`
- `API Tokens`
- `Create Token`

Permisos recomendados:

- `Zone -> DNS -> Edit`
- `Zone -> Zone -> Read`

Recursos:

- `Include -> Specific zone -> atokatl.work`

Si el mismo proxy de Coolify ya emite certificados para otras zonas, por ejemplo `nicacomputers.com`, lo correcto es crear **un solo token** con acceso a todas las zonas que ese proxy va a administrar.

Ejemplo:

- `atokatl.work`
- `nicacomputers.com`

No usar:

- `Global API Key`
- `Origin CA Key`

### 10.2. Editar el YAML del proxy

En el YAML de `services.traefik`, agregar o actualizar:

```yaml
environment:
  - CF_DNS_API_TOKEN=REEMPLAZAR
```

En `command:` debe existir:

```yaml
- '--certificatesresolvers.letsencrypt.acme.dnschallenge.provider=cloudflare'
- '--certificatesresolvers.letsencrypt.acme.dnschallenge.delaybeforecheck=0'
- '--certificatesresolvers.letsencrypt.acme.storage=/traefik/acme.json'
```

En `labels:` agregar:

```yaml
- traefik.http.routers.traefik.tls.certresolver=letsencrypt
- traefik.http.routers.traefik.tls.domains[0].main=pos.atokatl.work
- traefik.http.routers.traefik.tls.domains[0].sans=*.pos.atokatl.work
```

Si el mismo proxy ya tiene otro wildcard, por ejemplo:

```yaml
- traefik.http.routers.traefik.tls.domains[0].main=pos.nicacomputers.com
- traefik.http.routers.traefik.tls.domains[0].sans=*.pos.nicacomputers.com
```

entonces agregar el nuevo como segundo bloque:

```yaml
- traefik.http.routers.traefik.tls.domains[1].main=pos.atokatl.work
- traefik.http.routers.traefik.tls.domains[1].sans=*.pos.atokatl.work
```

También confirmar:

```yaml
- coolify.proxy=true
```

Después:

- Guardar
- `Restart Proxy`

Si la emisión falla muy rápido, probar con:

```yaml
- '--certificatesresolvers.letsencrypt.acme.dnschallenge.delaybeforecheck=30'
```

## 11. Registrar el primer tenant

Abrir:

- `https://pos.atokatl.work/auth/register`

Primer tenant sugerido:

- slug: `demoatokatl`
- dominio esperado: `demoatokatl.pos.atokatl.work`
- base esperada: `lakasir_demoatokatl`

## 12. Problemas comunes

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
- labels de Traefik configuradas para `HostRegexp`

Si al abrir el tenant aparece:

```text
no available server
```

el problema no es el tenant ni Cloudflare: falta el routing wildcard en la app de Coolify.

### El tenant abre pero listas como permisos, categorías o métodos de pago están vacías

Eso indica que el tenant fue creado, pero sus seeders no corrieron correctamente.

Verificar el código de registro del tenant y, para reparar un tenant existente, correr:

```bash
php artisan tenants:seed --tenants=demoatokatl --class=PermissionSeeder
php artisan tenants:seed --tenants=demoatokatl --class=PaymentMethodSeeder
php artisan tenants:seed --tenants=demoatokatl --class=CategorySeeder
```

Si el proyecto corre en producción, los `db:seed` del flujo de creación deben usar `--force`.

### Un proxy ya sirve varios dominios

Si el mismo proxy de Coolify ya emite certificados para otra zona:

- no crear tokens separados y luego alternarlos
- usar un solo `CF_DNS_API_TOKEN`
- darle acceso a todas las zonas que ese proxy gestiona
- agregar un índice nuevo en `tls.domains[n]` por cada wildcard extra

## 13. Resumen mínimo

Orden recomendado:

1. Crear proyecto/app en Coolify.
2. Crear base central en DigitalOcean.
3. Crear Redis propio en Coolify.
4. Configurar variables.
5. Montar CA de MySQL con `File Mount`.
6. Crear DNS `pos` y `*.pos` en Cloudflare.
7. Configurar labels SaaS en la app para capturar `pos` y `*.pos`.
8. Deploy.
9. Probar `/up`.
10. Ejecutar `php artisan migrate --force`.
11. Crear token de Cloudflare para DNS challenge.
12. Configurar wildcard SSL en el proxy de Coolify.
13. Reiniciar proxy.
14. Registrar `demoatokatl`.
