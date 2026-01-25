<div align="center">

  <img src="https://lakasir.com/assets/logo/image.png" alt="logo" width="200" height="auto" />
  <h1>Lakasir Web App</h1>

  <p> Lakasir is a Point of Sale (POS) application built using Laravel for the API, the Filament admin panel for the web application, and Flutter for the mobile application. </p>
  
</div>

## Requirements
* php 8.1
* mysql 5.7 or higher
* php-ext.* base on laravel extenstion requirement


## Features
- **Role Management**: Define roles and permissions for users.
- **Transaction Management**: Handle sales transactions seamlessly.
- **Product Management**: Manage your inventory and products effectively.
- **Unit Price**: The product will have a different price base on the basic unit.
- **Discount**: You can sell the product with a discount per item or global discount.
- **Purchasing**: Manage purchase orders and supplier relationships.
- **Stock Opname**: Conduct stock taking and inventory audits to ensure accuracy.
- **Receivable Management**: Track and manage receivables owed by and to your business.
- **Payment Method Management**: Define and manage various payment methods.
- **Voucher Management**: Create, distribute, and track the usage of vouchers.
- **Reporting**: Generate reports for insights into sales and performance.
- **Simple Accounting**: Basic accounting features to track income, expenses, and profits.
- **Real-time Dashboard**: Monitor business metrics and performance in real-time.
- **Web usb direct printing**: support the thermal printer using usb feature from browser (Chrome, Firefox)
- **Barcode support**: we can use the barcode on stock opname, purchasing, and POS feature

## Screenshots

<div style="display:inline-block" align="center">
  <img src="./readme/Screenshot/cashier-menu.png" alt="Product Detail" width="400" />
  &emsp;
  <img src="./readme/Screenshot/product-detail.png" alt="Product Detail" width="400"/>  
</div>
<!-- ![Lakasir Screenshot](./readme/Screenshot/product-detail.png) -->

## Technologies Used
* **Backend**: [Laravel](https://laravel.com)
* **Frontend** (Web): [Filament Admin Panel](https://filamentphp.com)
* **Frontend** (Mobile): [Flutter](https://flutter.github.io)

## Installation
### Primer arranque (local, multi-tenant por defecto)
1) **Clona**: `git clone https://github.com/lakasir/lakasir.git && cd lakasir`
2) **Env**: `cp .env.example .env` y ajusta `APP_URL=http://localdomain.test:8000`, `APP_CENTRAL_DOMAIN=localdomain.test`, y la DB (`DB_*`).
3) **Dependencias**: `composer install` y `npm install`.
4) **Key**: `php artisan key:generate`.
5) **Migraciones (central)**: `php artisan migrate` (crea tablas de tenants/domains/usuarios central).
6) **Assets Filament/Livewire**: `php artisan filament:assets` y `php artisan livewire:publish --assets`.
7) **Servidor + Vite**: en dos terminales: `php artisan serve --host=localdomain.test --port=8000` y `npm run dev`.
8) **Hosts**: agrega `127.0.0.1 localdomain.test` y cada dominio de tenant que uses (ej. `127.0.0.1 tenantdemo.localdomain.test`). En VS Code existe la task `Hosts: add localdomain.test` para el host base.

### Usuarios admin / “super admin”
- **Multi-tenant (recomendado, APP_CENTRAL_DOMAIN definido)**: no hay panel central; cada tienda crea su propio usuario owner (rol `admin`) al registrarse. Administra desde `https://{tu-dominio-tenant}/member`.
- **Standalone (APP_CENTRAL_DOMAIN vacío)**: ejecuta `php artisan app:create-user` para crear el owner inicial y entra por `APP_URL/member/login`. Este comando está bloqueado cuando hay dominio central configurado.

## Usage
* api: localdomain.test/api/test
* webapp: localdomain.test/member/login
* api docs:
* Scalar: localdomain.test/scalar (uses /docs/api.json)
* OpenAPI JSON: /docs/api.json (generate with `php artisan scramble:export`; hard refresh Scalar to pick up changes)

## Accesos y rutas (local)
- **Dominio central (`http://localdomain.test:8000`)**: landing `/`, registro `/auth/register`, soporte PWA `/offline` y `/serviceworker.js`, API de registro `/api/domain/register`, ping `/api/test`. El grupo `/admin` está vacío.
- **Landing**: sale de `resources/views/livewire/pages/welcome.blade.php` expuesto por `Volt::route('/', 'pages/welcome')` en `routes/web.php`.
- **Dominio de tenant (ej. `http://tenantdemo.localdomain.test`)**: `/` redirige a `/member`; todo el panel/admin/POS vive bajo `/member/*` (login, dashboard, inventario, caja, etc.). Páginas extra: `/member/sellings/{selling}/print` (ticket), `/member/*-report/generate` (reportes PDF), `/reset-password/{token}` (reset).
- **API tenant**: bajo `https://{tu-dominio-tenant}/api/*`. Incluye auth (`/api/auth/login`, `/api/auth/me`), maestros (`/api/master/product`, `/api/master/category`, `/api/master/member`, `/api/master/supplier`, `/api/master/payment-method`), transacciones (`/api/transaction/selling`, `/api/transaction/cash-drawer`), configuración (`/api/setting*`, `/api/setting/secure-initial-price*`), reportes (`/api/report/cashier`), impresoras (`/api/printer`), notificaciones (`/api/notification`), uploads temporales (`/api/temp/upload`), y `GET /api/check` para validar el tenant activo.

## Cómo crear un tenant (local)
1. **Vía UI**: abre `http://localdomain.test:8000/auth/register`, ingresa dominio (sin protocolo), email, password y tipo de negocio. Si escribes `tenantdemo`, se registrará como `tenantdemo.localdomain.test`. El usuario owner se crea con las credenciales ingresadas.
2. **Vía API**: `POST http://localdomain.test:8000/api/domain/register` con JSON:
   ```json
   {
     "domain": "tenantdemo",
     "email": "owner@example.com",
     "password": "Secret123",
     "password_confirmation": "Secret123",
     "business_type": "retail", // retail|wholesale|fnb|fashion|pharmacy|other
     "other_business_type": null,
     "full_name": "Tenant Demo"
   }
   ```
   Responde con el tenant creado y su dominio final.
   El flujo crea la base `lakasir_{domain}`, corre migraciones de `database/migrations/tenant` y siembra permisos, métodos de pago y categorías base.
3. **Hosts**: agrega la entrada en `/etc/hosts` si no existe: `127.0.0.1 tenantdemo.localdomain.test`. En VS Code puedes correr la task `Hosts: add localdomain.test` para registrar el host base.
4. **Acceso al panel**: visita `http://tenantdemo.localdomain.test/member` y entra con el email/password que usaste al registrarlo.

## API (resumen de respuestas)
- Formato común: `{ "success": true|false, "data": <payload>, "message": "..." }`. Con paginación, `data` incluye `data[]`, `links`, `meta` (paginación simple o completa).
- **Ventas** (`/api/transaction/selling`): `GET` devuelve paginado con `member`, `payment_method`, `selling_details[]` (cada detalle trae `product`) y `cashier`. `POST` crea venta y devuelve el mismo objeto; valida stock, `payment_method_id`, `products[].qty/price/discount_price`, `payed_money` (si no es crédito). `GET /{id}` devuelve el detalle.
- **Historial de ventas**: es el `GET /api/transaction/selling` (permite filtros por código, miembro, fecha, total, producto, etc.; ordenado desc por `created_at`).
- **Productos** (`/api/master/product`): `GET` paginado (`id`, `name`, `category{ id,name }`, `initial_price`, `selling_price`, `unit`, `stock`, `is_non_stock`, `sku`, `barcode`, `hero_images`). `POST/PUT/DELETE` responden solo mensaje de éxito; `GET /{id}` incluye `stocks[]`. Filtros: `name`, `category_id`, `type`, `unit`, `stock` (gt/ge/lt/le/eq/ne), búsqueda global `global`.
- **Stock de producto** (`/api/master/product/{product}/stock`): `GET` paginado (`stock`, `init_stock`, `initial_price`, `selling_price`, `type`, `date`); `POST` crea movimiento; `DELETE /{product}/stock/{stock}` elimina movimiento.
- **Miembros** (`/api/master/member`): `GET` lista completa; `POST/PUT/DELETE` responden `{ success, message }`; `GET /{id}` retorna el miembro (`id`, `name`, `email`, timestamps).
- **Métodos de pago** (`/api/payment-method`): lista simple de métodos (`id`, `name`, `is_credit`, timestamps).
- **Configuración general / tienda**:
  - `GET /api/about`: `shop_name`, `shop_location`, `owner_name`, `business_type`, `other_business_type`, `currency`, `photo_url`.
  - `PUT /api/about`: actualiza datos de tienda (requiere `business_type`, `other_business_type` si aplica) y `currency`.
  - `GET /api/setting/all`: devuelve `currency`, `selling_method`, `cash_drawer_enabled`, `secure_initial_price_enabled`, `secure_initial_price_using_pin`, `minimum_stock_nofication`, `default_tax`.
  - `POST /api/setting`: `{ key, value }` para cualquiera de los anteriores (valida tipos/rangos).
- **Seguridad de costo** (`/api/setting/secure-initial-price*`): setea/verifica contraseña de costo inicial; responde con `success` y `message` (403 si no está habilitado).

## Contributing

We welcome contributions from the community! If you'd like to contribute to Lakasir, please follow these steps:

1. keep on eye on [roadmap](https://github.com/orgs/lakasir/discussions/321)
2. Fork the repository.
3. Create a new branch (git checkout -b feature/new-feature). 
4. Make your changes and commit them (git commit -am 'Add new feature').
5. Push to the branch (git push origin feature/new-feature).
6. Create a new Pull Request.
   
When contributing to this project, please keep an eye on our project features board on GitHub to stay updated with ongoing and planned features.

## License
This project is licensed under the GPL-3.0 license - see the [LICENSE](https://github.com/lakasir/lakasir?tab=GPL-3.0-1-ov-file) file for details.

## Contact
For any inquiries or support, please contact lakasirapp@gmail.com or you can open discussion in discussion features

## Donate for live longer

[<img src="https://trakteer.id/images/v2/trakteer-logo.png" alt="drawing" width="100"/>](https://trakteer.id/sheenazien8/tip?quantity=1)
<a href="https://www.buymeacoffee.com/sheenazien8" target="_blank"><img src="https://cdn.buymeacoffee.com/buttons/v2/default-green.png" alt="Buy Me A Coffee" style="height: 20px !important;width: 100px !important;" ></a>


## Star History

<a href="https://star-history.com/#lakasir/lakasir&Date">
 <picture>
   <source media="(prefers-color-scheme: dark)" srcset="https://api.star-history.com/svg?repos=lakasir/lakasir&type=Date&theme=dark" />
   <source media="(prefers-color-scheme: light)" srcset="https://api.star-history.com/svg?repos=lakasir/lakasir&type=Date" />
   <img alt="Star History Chart" src="https://api.star-history.com/svg?repos=lakasir/lakasir&type=Date" />
 </picture>
</a>
