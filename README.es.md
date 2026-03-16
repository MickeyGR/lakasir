<div align="center">

  <img src="https://lakasir.com/assets/logo/image.png" alt="logo" width="200" height="auto" />
  <h1>Lakasir Web App</h1>

  <p> Lakasir es una aplicación de Punto de Venta (POS) construida con Laravel para la API, el panel de administración Filament para la aplicación web y Flutter para la aplicación móvil. </p>
  
</div>

## Requisitos
* php 8.1
* mysql 5.7 o superior
* php-ext.* basado en los requisitos de extensiones de Laravel

## Características
- **Gestión de Roles**: Define roles y permisos para usuarios.
- **Gestión de Transacciones**: Maneja transacciones de venta sin problemas.
- **Gestión de Productos**: Administra tu inventario y productos eficazmente.
- **Precio por Unidad**: El producto tendrá un precio diferente basado en la unidad básica.
- **Descuentos**: Puedes vender el producto con descuento por artículo o descuento global.
- **Compras**: Gestiona órdenes de compra y relaciones con proveedores.
- **Inventario Físico**: Realiza conteos de stock y auditorías de inventario para asegurar precisión.
- **Gestión de Cuentas por Cobrar**: Rastrea y administra cuentas por cobrar de y a tu negocio.
- **Gestión de Métodos de Pago**: Define y administra varios métodos de pago.
- **Gestión de Cupones**: Crea, distribuye y rastrea el uso de cupones.
- **Reportes**: Genera reportes para obtener información sobre ventas y rendimiento.
- **Contabilidad Simple**: Características básicas de contabilidad para rastrear ingresos, gastos y ganancias.
- **Dashboard en Tiempo Real**: Monitorea métricas y rendimiento del negocio en tiempo real.
- **Impresión directa USB web**: Soporta impresoras térmicas usando la función USB desde el navegador (Chrome, Firefox)
- **Soporte de códigos de barras**: Podemos usar códigos de barras en inventario físico, compras y funcionalidad POS

## Capturas de Pantalla

<div style="display:inline-block" align="center">
  <img src="./readme/Screenshot/cashier-menu.png" alt="Product Detail" width="400" />
  &emsp;
  <img src="./readme/Screenshot/product-detail.png" alt="Product Detail" width="400"/>  
</div>
<!-- ![Lakasir Screenshot](./readme/Screenshot/product-detail.png) -->

## Arquitectura

### Diseño Multi-Tenant
Lakasir usa arquitectura **multi-tenant**, permitiendo que múltiples tiendas (tenants) funcionen en una sola instalación:
- Cada tenant tiene su propia base de datos aislada (`lakasir_{domain}`)
- Los tenants se identifican por dominio/subdominio (ej., `tienda1.lakasir.com`, `tienda2.lakasir.com`)
- La base de datos central gestiona el registro de tenants y mapeo de dominios
- Detección automática de tenant vía middleware (`InitializeTenancyByDomain`)

### Modos de Despliegue

#### 1. Modo Multi-Tenant (Recomendado para Producción)
- **Configuración**: Define `APP_CENTRAL_DOMAIN` en `.env` (ej., `lakasir.com`)
- **Registro de Tenant**: Los usuarios se registran vía `/auth/register` o endpoint API
- **Mapeo de Dominio**: Cada tenant obtiene `{subdominio}.{APP_CENTRAL_DOMAIN}`
- **Panel Admin**: Cada tenant accede a su admin en `https://{dominio-tenant}/member`
- **Sin Admin Central**: Cada tenant está completamente aislado y auto-gestionado

#### 2. Modo Standalone (Tienda Única)
- **Configuración**: Deja `APP_CENTRAL_DOMAIN` vacío en `.env`
- **Base de Datos Única**: Usa solo la base de datos central, sin multi-tenancy
- **Creación de Usuario**: Ejecuta `php artisan app:create-user` para crear el propietario
- **Panel Admin**: Accede en `{APP_URL}/member/login`
- **Caso de Uso**: Pequeños negocios con una sola tienda

## Tecnologías Utilizadas
* **Backend**: [Laravel](https://laravel.com)
* **Multi-Tenancy**: [Stancl/Tenancy](https://tenancyforlaravel.com/)
* **Frontend** (Web): [Panel Admin Filament](https://filamentphp.com)
* **Frontend** (Móvil): [Flutter](https://flutter.github.io)
* **Documentación API**: [Scramble](https://scramble.dedoc.co/) + [Scalar](https://scalar.com/)

## Mapa de Módulos del Código

| Módulo | Puntos de entrada de rutas | Archivos principales |
| --- | --- | --- |
| Tenancy y arranque | `routes/web.php`, `routes/api.php`, `routes/tenant.php` | `app/Http/Middleware/InitializeTenancyByDomain.php`, `app/Providers/TenancyServiceProvider.php`, `app/Services/RegisterTenant.php`, `app/Tenant.php`, `database/migrations/tenant/*` |
| Autenticación | Auth de tenant en `routes/tenant.php` (`/api/auth/*`), registro de tenant en `routes/web.php` (`/auth/register`) y `routes/api.php` (`/api/domain/register`) | `app/Http/Controllers/Auth/AuthenticatedSessionController.php`, `app/Http/Controllers/Auth/RegisteredUserController.php`, `app/Http/Controllers/Auth/PasswordResetLinkController.php`, `app/Http/Controllers/Auth/VerifyEmailController.php`, `app/Http/Requests/Auth/LoginRequest.php`, `app/Livewire/Forms/Auth/RegisterTenantForm.php`, `app/Http/Controllers/Api/Tenants/Storefront/AuthController.php` |
| Productos e inventario | `routes/tenant.php` (`/api/master/product*`, `/api/master/category*`, `/api/master/supplier*`, `/api/master/payment-method*`) | `app/Http/Controllers/Api/Tenants/Master/ProductController.php`, `app/Http/Controllers/Api/Tenants/Master/Product/StockController.php`, `app/Http/Controllers/Api/Tenants/Master/CategoryController.php`, `app/Http/Controllers/Api/Tenants/Master/SupplierController.php`, `app/Filament/Tenant/Resources/ProductResource.php`, `app/Filament/Tenant/Resources/CategoryResource.php`, `app/Filament/Tenant/Resources/SupplierResource.php`, `app/Filament/Tenant/Resources/PaymentMethodResource.php`, `app/Services/Tenants/ProductService.php`, `app/Services/Tenants/StockService.php`, `app/Models/Tenants/Product.php`, `app/Models/Tenants/Stock.php` |
| Ventas y POS | `routes/tenant.php` (`/api/transaction/selling*`, `/api/transaction/cash-drawer*`, `/member/sellings/{selling}/print`) | `app/Http/Controllers/Api/Tenants/Transaction/SellingController.php`, `app/Http/Controllers/Api/Tenants/Transaction/CashDrawerController.php`, `app/Http/Controllers/Api/Tenants/Transaction/DashboardController.php`, `app/Http/Requests/Tenants/Sellings/TransactionSellingStoreRequest.php`, `app/Filament/Tenant/Pages/POS.php`, `app/Filament/Tenant/Pages/Cashier.php`, `app/Filament/Tenant/Resources/SellingResource.php`, `app/Services/Tenants/SellingService.php`, `app/Models/Tenants/Selling.php`, `app/Models/Tenants/SellingDetail.php`, `app/Models/Tenants/CashDrawer.php` |
| Reportes | `routes/tenant.php` (`/member/*-report/generate`, `/api/report/*`) | `app/Http/Controllers/PurchasingReportController.php`, `app/Http/Controllers/SellingReportController.php`, `app/Http/Controllers/ProductReportController.php`, `app/Http/Controllers/CashierReportController.php`, `app/Http/Controllers/Api/Tenants/Reports/PurchasingReportController.php`, `app/Http/Controllers/Api/Tenants/Reports/SellingReportController.php`, `app/Filament/Tenant/Pages/Report.php`, `app/Filament/Tenant/Pages/PurchasingReport.php`, `app/Filament/Tenant/Pages/SellingReport.php`, `app/Filament/Tenant/Pages/ProductReport.php`, `app/Filament/Tenant/Pages/CashierReport.php`, `app/Services/Tenants/PurchasingReportService.php`, `app/Services/Tenants/SellingReportService.php`, `app/Services/Tenants/ProductReportService.php`, `app/Services/Tenants/CashierReportService.php` |
| Configuración y perfil | `routes/tenant.php` (`/api/about*`, `/api/setting*`, `/api/printer*`, `/api/notification*`, `/api/auth/me`) | `app/Http/Controllers/Api/Tenants/AboutController.php`, `app/Http/Controllers/Api/Tenants/SettingController.php`, `app/Http/Controllers/Api/Tenants/Settings/SecureInitialPriceController.php`, `app/Http/Controllers/Api/Tenants/PrinterController.php`, `app/Http/Controllers/Api/Tenants/NotificationController.php`, `app/Http/Controllers/Api/Tenants/ProfileController.php`, `app/Filament/Tenant/Pages/GeneralSetting.php`, `app/Filament/Tenant/Pages/Printer.php`, `app/Services/Tenants/AboutService.php`, `app/Models/Tenants/About.php`, `app/Models/Tenants/Setting.php`, `app/Models/Tenants/SecureInitialPrice.php`, `app/Models/Tenants/Printer.php` |
| API Storefront | `routes/tenant.php` (`/api/storefront/*`) | `app/Http/Controllers/Api/Tenants/Storefront/AboutController.php`, `app/Http/Controllers/Api/Tenants/Storefront/CategoryController.php`, `app/Http/Controllers/Api/Tenants/Storefront/ProductController.php`, `app/Http/Controllers/Api/Tenants/Storefront/AuthController.php`, `app/Models/Tenants/Member.php` |

## Instalación

### Configuración de Desarrollo Local (Modo Multi-Tenant)

1. **Clonar el repositorio**
   ```bash
   git clone https://github.com/lakasir/lakasir.git
   cd lakasir
   ```

2. **Configurar entorno**
   ```bash
   cp .env.example .env
   ```
   Edita `.env` y configura:
   ```env
   APP_URL=http://localdomain.test:8000
   APP_CENTRAL_DOMAIN=localdomain.test
   
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=lakasir_central
   DB_USERNAME=root
   DB_PASSWORD=
   ```

3. **Instalar dependencias**
   ```bash
   composer install
   npm install
   ```

4. **Generar clave de aplicación**
   ```bash
   php artisan key:generate
   ```

5. **Ejecutar migraciones de base de datos central**
   ```bash
   php artisan migrate
   ```
   Esto crea las tablas de la base de datos central para gestión de tenants.

6. **Publicar assets**
   ```bash
   php artisan filament:assets
   php artisan livewire:publish --assets
   ```

7. **Agregar entradas DNS**
   Agregar a `/etc/hosts` (Linux/Mac) o `C:\Windows\System32\drivers\etc\hosts` (Windows):
   ```
   127.0.0.1 localdomain.test
   ```
   Nota: Los subdominios de tenant (ej., `tenant1.localdomain.test`) también necesitan entradas.

8. **Iniciar servidores de desarrollo**
   En terminales separadas:
   ```bash
   # Terminal 1: Servidor Laravel
   php artisan serve --host=localdomain.test --port=8000
   
   # Terminal 2: Servidor dev Vite
   npm run dev
   ```

9. **Acceder a la aplicación**
   - Dominio central: `http://localdomain.test:8000`
   - Registra tu primer tenant en: `http://localdomain.test:8000/auth/register`

### Configuración Modo Standalone (Tienda Única)

Para un despliegue de tienda única sin multi-tenancy:

1. Sigue los pasos 1-6 del modo Multi-Tenant

2. **Configurar modo standalone**
   Edita `.env`:
   ```env
   APP_URL=http://localhost:8000
   APP_CENTRAL_DOMAIN=  # ¡Dejar vacío!
   ```

3. **Ejecutar migraciones**
   ```bash
   php artisan migrate
   ```

4. **Crear usuario propietario**
   ```bash
   php artisan app:create-user
   ```
   Sigue las instrucciones para crear tu cuenta admin.

5. **Iniciar servidor y acceder**
   ```bash
   php artisan serve
   ```
   Inicia sesión en: `http://localhost:8000/member/login`

### Actualizaciones de Despliegue en Producción

1. **Obtener últimos cambios**
   ```bash
   git pull
   ```

2. **Instalar dependencias PHP** (optimizado para producción)
   ```bash
   composer install --no-dev --optimize-autoloader
   ```

3. **Ejecutar migraciones de base de datos central**
   ```bash
   php artisan migrate
   ```
   Esperar "Nothing to migrate" si no hay nuevas migraciones.

4. **Migrar bases de datos de tenant** (solo multi-tenant)
   ```bash
   # Tenant único
   php artisan tenants:migrate --tenants=tu_tenant
   
   # Todos los tenants
   php artisan tenants:migrate --all
   ```

5. **Limpiar y reconstruir cachés de aplicación**
   ```bash
   php artisan optimize:clear
   php artisan optimize
   ```

### Coolify

Este repositorio ahora ejecuta el reseteo de cachés de Laravel automáticamente cuando un contenedor Nixpacks/Coolify arranca.

Si igual quieres un respaldo desde la UI de Coolify, configura este valor en **Post-deployment Command**:

```bash
sh /app/scripts/coolify-post-deploy.sh
```

Eso sirve cuando un servicio existente sigue usando una imagen anterior o si quieres forzar el mismo reseteo directamente desde el panel de Coolify.

### Gestión de Usuarios Admin

- **Modo Multi-Tenant** (Recomendado, `APP_CENTRAL_DOMAIN` configurado):
  - No existe panel admin central
  - Cada tenant crea su propio usuario propietario (rol: `admin`) durante el registro
  - Accede al panel admin en: `https://{tu-dominio-tenant}/member`

- **Modo Standalone** (`APP_CENTRAL_DOMAIN` vacío):
  - Ejecuta `php artisan app:create-user` para crear el propietario inicial
  - Inicia sesión en: `{APP_URL}/member/login`
  - Nota: Este comando está bloqueado cuando el dominio central está configurado

## Uso
* api: localdomain.test/api/test
* webapp: localdomain.test/member/login
* docs api:
* Scalar: localdomain.test/scalar (usa /docs/api.json)
* OpenAPI JSON: /docs/api.json (genera con `php artisan scramble:export`; fuerza recarga de Scalar para ver cambios)

## Rutas y Acceso (Desarrollo Local)

### Dominio Central (`http://localdomain.test:8000`)
- `/` - Página de inicio
- `/auth/register` - Registro de tenant
- `/offline`, `/serviceworker.js` - Soporte PWA
- `/api/domain/register` - API de registro de tenant
- `/api/test` - Endpoint de verificación de salud
- `/admin/*` - Vacío (sin panel admin central)

**Origen de Página de Inicio**: `resources/views/livewire/pages/welcome.blade.php` expuesto via `Volt::route('/', 'pages/welcome')` en `routes/web.php`.

### Dominio de Tenant (ej., `http://tenantdemo.localdomain.test`)

**Rutas Web**:
- `/` - Redirige a `/member`
- `/member/*` - Panel admin (login, dashboard, inventario, POS, etc.)
- `/member/sellings/{selling}/print` - Impresión de recibo
- `/member/*-report/generate` - Generación de reportes PDF
- `/reset-password/{token}` - Restablecimiento de contraseña

**Rutas API** (`/api/*`):
- **Auth**: `/api/auth/login`, `/api/auth/me`
- **Maestros**: `/api/master/product`, `/api/master/category`, `/api/master/member`, `/api/master/supplier`, `/api/master/payment-method`
- **Transacciones**: `/api/transaction/selling`, `/api/transaction/cash-drawer`
- **Configuración**: `/api/setting*`, `/api/setting/secure-initial-price*`
- **Reportes**: `/api/report/cashier`
- **Impresoras**: `/api/printer`
- **Notificaciones**: `/api/notification`
- **Subidas**: `/api/temp/upload`
- **Salud**: `/api/check` - Valida tenant activo

## Crear Tenants (Modo Multi-Tenant)

### Método 1: Interfaz de Registro Web

1. Navegar a `http://localdomain.test:8000/auth/register`
2. Llenar el formulario de registro:
   - **Dominio**: Ingresar nombre de subdominio (ej., `mitienda`) → se convierte en `mitienda.localdomain.test`
   - **Email**: Dirección de email del propietario
   - **Contraseña**: Establecer contraseña del propietario
   - **Tipo de Negocio**: Seleccionar del dropdown (retail, mayorista, F&B, etc.)
3. Click en **Registrar**
4. El sistema:
   - Creará base de datos de tenant (`lakasir_mitienda`)
   - Ejecutará migraciones de tenant
   - Sembrar datos predeterminados (métodos de pago, categorías, permisos)
   - Crear cuenta de usuario propietario

### Método 2: Registro vía API

```bash
POST http://localdomain.test:8000/api/domain/register
Content-Type: application/json

{
  "domain": "mitienda",
  "email": "propietario@example.com",
  "password": "ContraseñaSegura123",
  "password_confirmation": "ContraseñaSegura123",
  "business_type": "retail",
  "other_business_type": null,
  "full_name": "Juan Pérez"
}
```

**Tipos de Negocio**: `retail`, `wholesale`, `fnb`, `fashion`, `pharmacy`, `other`

### Método 3: Comando Artisan (Producción)

Para ambientes de producción donde la UI no es accesible:

```bash
php artisan tenants:create {domain}
```

Luego agregar manualmente el usuario propietario vía base de datos o ejecutar seeder personalizado.

### Pasos Post-Creación

1. **Agregar entrada DNS/Hosts** (desarrollo local):
   ```bash
   echo "127.0.0.1 mitienda.localdomain.test" | sudo tee -a /etc/hosts
   ```

2. **Acceder panel admin de tenant**:
   - URL: `http://mitienda.localdomain.test:8000/member`
   - Iniciar sesión con el email/contraseña usado durante el registro

3. **Configurar ajustes de tenant**:
   - Navegar a Configuración en el panel admin
   - Configurar moneda, impuestos, horario comercial, etc.

## Documentación de API

### Documentación Interactiva

Lakasir provee documentación interactiva de API vía **Scalar**:

- **UI Scalar**: `https://{dominio-tenant}/docs` o `http://localdomain.test:8000/docs`
- **OpenAPI JSON**: `https://{dominio-tenant}/docs/api-json`

La documentación incluye:
- ✅ **Autenticación** (Token Bearer con botón global "Autorizar")
- ✅ **Ejemplos de Petición/Respuesta**
- ✅ **Reglas de validación de parámetros**
- ✅ **Función "Probar"** para probar endpoints

### Formato de Respuesta API

Todas las respuestas de API siguen esta estructura:

**Respuesta Exitosa** (200 OK):
```json
{
  "success": true,
  "data": { ... },
  "message": "Mensaje opcional"
}
```

**Respuesta Paginada**:
```json
{
  "success": true,
  "data": {
    "data": [ ... ],
    "links": { "first": "...", "last": "...", "prev": null, "next": "..." },
    "meta": { "current_page": 1, "last_page": 5, "total": 100 }
  }
}
```

**Respuesta de Error** (4xx/5xx):
```json
{
  "success": false,
  "message": "Descripción del error",
  "errors": { "field": ["Error de validación"] }  // Para errores de validación 422
}
```

### Resumen de Endpoints API

#### API Admin (Requiere Autenticación)

**Autenticación**:
- `POST /api/auth/login` - Iniciar sesión (retorna `token`)
- `POST /api/auth/logout` - Cerrar sesión
- `GET /api/user` - Obtener perfil de usuario autenticado

**Productos**:
- `GET /api/master/product` - Listar productos (paginado, filtrable, ordenable)
- `POST /api/master/product` - Crear producto
- `GET /api/master/product/{id}` - Obtener detalles de producto
- `PUT /api/master/product/{id}` - Actualizar producto
- `DELETE /api/master/product/{id}` - Eliminar producto

**Transacciones**:
- `GET /api/transaction/selling` - Listar transacciones de venta
- `POST /api/transaction/selling` - Crear venta
- `GET /api/transaction/selling/{id}` - Obtener detalles de venta
- `GET /api/transaction/cash-drawer` - Obtener estado de caja registradora
- `POST /api/transaction/cash-drawer` - Abrir caja registradora

**Configuración**:
- `GET /api/about` - Obtener información de la tienda
- `PUT /api/about` - Actualizar información de la tienda
- `GET /api/setting/all` - Obtener todas las configuraciones
- `POST /api/setting` - Actualizar configuración

#### API Storefront (E-commerce/Público)

La API Storefront permite construir frontends de e-commerce personalizados:

**Endpoints Públicos** (Sin Autenticación):
- `GET /api/storefront/products` - Listar productos visibles
- `GET /api/storefront/products/{id}` - Obtener detalles de producto

**Autenticación de Cliente**:
- `POST /api/storefront/auth/register` - Registrar nuevo cliente
- `POST /api/storefront/auth/login` - Inicio de sesión de cliente
- `GET /api/storefront/auth/me` - Obtener perfil de cliente (requiere token)
- `POST /api/storefront/auth/logout` - Cerrar sesión de cliente

**Ejemplo: Listar Productos**
```bash
curl "https://mitienda.lakasir.com/api/storefront/products?per_page=20&sort=-selling_price&filter[category_id]=5"
```

**Ejemplo: Inicio de Sesión de Cliente**
```bash
curl -X POST "https://mitienda.lakasir.com/api/storefront/auth/login" \
  -H "Content-Type: application/json" \
  -d '{"email":"cliente@example.com","password":"password123"}'
```

Para documentación completa de API, visita los docs de Scalar en `https://{tu-dominio-tenant}/docs`.

## Contribuir

¡Damos la bienvenida a contribuciones de la comunidad! Si te gustaría contribuir a Lakasir, por favor sigue estos pasos:

1. mantente al tanto del [roadmap](https://github.com/orgs/lakasir/discussions/321)
2. Haz fork del repositorio.
3. Crea una nueva rama (git checkout -b feature/nueva-caracteristica). 
4. Haz tus cambios y commítelos (git commit -am 'Agregar nueva característica').
5. Push a la rama (git push origin feature/nueva-caracteristica).
6. Crea un nuevo Pull Request.
   
Al contribuir a este proyecto, por favor mantente al tanto del tablero de características del proyecto en GitHub para estar actualizado con las características en curso y planificadas.

## Licencia
Este proyecto está licenciado bajo la licencia GPL-3.0 - ver el archivo [LICENSE](https://github.com/lakasir/lakasir?tab=GPL-3.0-1-ov-file) para detalles.

## Contacto
Para cualquier consulta o soporte, por favor contactar a lakasirapp@gmail.com o puedes abrir una discusión en las funciones de discusión

## Donar para vivir más tiempo

[<img src="https://trakteer.id/images/v2/trakteer-logo.png" alt="drawing" width="100"/>](https://trakteer.id/sheenazien8/tip?quantity=1)
<a href="https://www.buymeacoffee.com/sheenazien8" target="_blank"><img src="https://cdn.buymeacoffee.com/buttons/v2/default-green.png" alt="Buy Me A Coffee" style="height: 20px !important;width: 100px !important;" ></a>


## Historial de Estrellas

<a href="https://star-history.com/#lakasir/lakasir&Date">
 <picture>
   <source media="(prefers-color-scheme: dark)" srcset="https://api.star-history.com/svg?repos=lakasir/lakasir&type=Date&theme=dark" />
   <source media="(prefers-color-scheme: light)" srcset="https://api.star-history.com/svg?repos=lakasir/lakasir&type=Date" />
   <img alt="Star History Chart" src="https://api.star-history.com/svg?repos=lakasir/lakasir&type=Date" />
 </picture>
</a>
