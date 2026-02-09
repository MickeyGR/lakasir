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

## Architecture

### Multi-Tenant Design
Lakasir uses **multi-tenancy** architecture, allowing multiple stores (tenants) to run on a single installation:
- Each tenant has its own isolated database (`lakasir_{domain}`)
- Tenants are identified by domain/subdomain (e.g., `store1.lakasir.com`, `store2.lakasir.com`)
- Central database manages tenant registration and domain mapping
- Automatic tenant detection via middleware (`InitializeTenancyByDomain`)

### Deployment Modes

#### 1. Multi-Tenant Mode (Production Recommended)
- **Configuration**: Set `APP_CENTRAL_DOMAIN` in `.env` (e.g., `lakasir.com`)
- **Tenant Registration**: Users register via `/auth/register` or API endpoint
- **Domain Mapping**: Each tenant gets `{subdomain}.{APP_CENTRAL_DOMAIN}`
- **Admin Panel**: Each tenant accesses their admin at `https://{tenant-domain}/member`
- **No Central Admin**: Each tenant is fully isolated and self-managed

#### 2. Standalone Mode (Single Store)
- **Configuration**: Leave `APP_CENTRAL_DOMAIN` empty in `.env`
- **Single Database**: Uses only the central database, no multi-tenancy
- **User Creation**: Run `php artisan app:create-user` to create the owner
- **Admin Panel**: Access at `{APP_URL}/member/login`
- **Use Case**: Small businesses running a single store

## Technologies Used
* **Backend**: [Laravel](https://laravel.com)
* **Multi-Tenancy**: [Stancl/Tenancy](https://tenancyforlaravel.com/)
* **Frontend** (Web): [Filament Admin Panel](https://filamentphp.com)
* **Frontend** (Mobile): [Flutter](https://flutter.github.io)
* **API Documentation**: [Scramble](https://scramble.dedoc.co/) + [Scalar](https://scalar.com/)

## Installation

### Local Development Setup (Multi-Tenant Mode)

1. **Clone the repository**
   ```bash
   git clone https://github.com/lakasir/lakasir.git
   cd lakasir
   ```

2. **Configure environment**
   ```bash
   cp .env.example .env
   ```
   Edit `.env` and configure:
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

3. **Install dependencies**
   ```bash
   composer install
   npm install
   ```

4. **Generate application key**
   ```bash
   php artisan key:generate
   ```

5. **Run central database migrations**
   ```bash
   php artisan migrate
   ```
   This creates the central database tables for tenant management.

6. **Publish assets**
   ```bash
   php artisan filament:assets
   php artisan livewire:publish --assets
   ```

7. **Add DNS entries**
   Add to `/etc/hosts` (Linux/Mac) or `C:\Windows\System32\drivers\etc\hosts` (Windows):
   ```
   127.0.0.1 localdomain.test
   ```
   Note: Tenant subdomains (e.g., `tenant1.localdomain.test`) also need entries.

8. **Start development servers**
   In separate terminals:
   ```bash
   # Terminal 1: Laravel server
   php artisan serve --host=localdomain.test --port=8000
   
   # Terminal 2: Vite dev server
   npm run dev
   ```

9. **Access the application**
   - Central domain: `http://localdomain.test:8000`
   - Register your first tenant at: `http://localdomain.test:8000/auth/register`

### Standalone Mode Setup (Single Store)

For a single-store deployment without multi-tenancy:

1. Follow steps 1-6 from Multi-Tenant setup

2. **Configure standalone mode**
   Edit `.env`:
   ```env
   APP_URL=http://localhost:8000
   APP_CENTRAL_DOMAIN=  # Leave empty!
   ```

3. **Run migrations**
   ```bash
   php artisan migrate
   ```

4. **Create owner user**
   ```bash
   php artisan app:create-user
   ```
   Follow the prompts to create your admin account.

5. **Start server and access**
   ```bash
   php artisan serve
   ```
   Login at: `http://localhost:8000/member/login`

### Actualización en producción
1) Trae cambios: `git pull`.
2) Dependencias PHP: `composer install --no-dev --optimize-autoloader`.
3) Migraciones (espera “Nothing to migrate.” si no hay nuevas): `php artisan migrate`.
4) Multi-tenant (solo si aplica): `php artisan tenants:migrate --tenants=tu_tenant` o `--all`.
5) Limpia cachés: `php artisan optimize:clear`.

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

## Creating Tenants (Multi-Tenant Mode)

### Method 1: Web Registration UI

1. Navigate to `http://localdomain.test:8000/auth/register`
2. Fill in the registration form:
   - **Domain**: Enter subdomain name (e.g., `mystore`) → becomes `mystore.localdomain.test`
   - **Email**: Owner's email address
   - **Password**: Set owner password
   - **Business Type**: Select from dropdown (retail, wholesale, F&B, etc.)
3. Click **Register**
4. The system will:
   - Create tenant database (`lakasir_mystore`)
   - Run tenant migrations
   - Seed default data (payment methods, categories, permissions)
   - Create owner user account

### Method 2: API Registration

```bash
POST http://localdomain.test:8000/api/domain/register
Content-Type: application/json

{
  "domain": "mystore",
  "email": "owner@example.com",
  "password": "SecurePassword123",
  "password_confirmation": "SecurePassword123",
  "business_type": "retail",
  "other_business_type": null,
  "full_name": "John Doe"
}
```

**Business Types**: `retail`, `wholesale`, `fnb`, `fashion`, `pharmacy`, `other`

### Method 3: Artisan Command (Production)

For production environments where UI is not accessible:

```bash
php artisan tenants:create {domain}
```

Then manually add the owner user via database or run custom seeder.

### Post-Creation Steps

1. **Add DNS/Hosts entry** (local development):
   ```bash
   echo "127.0.0.1 mystore.localdomain.test" | sudo tee -a /etc/hosts
   ```

2. **Access tenant admin panel**:
   - URL: `http://mystore.localdomain.test:8000/member`
   - Login with the email/password used during registration

3. **Configure tenant settings**:
   - Navigate to Settings in the admin panel
   - Configure currency, tax, business hours, etc.

## API Documentation

### Interactive Documentation

Lakasir provides interactive API documentation via **Scalar**:

- **Scalar UI**: `https://{tenant-domain}/docs` or `http://localdomain.test:8000/docs`
- **OpenAPI JSON**: `https://{tenant-domain}/docs/api-json`

The documentation includes:
- ✅ **Authentication** (Bearer Token with global "Authorize" button)
- ✅ **Request/Response examples**
- ✅ **Parameter validation rules**
- ✅ **Try It Out** feature for testing endpoints

### API Response Format

All API responses follow this structure:

**Success Response** (200 OK):
```json
{
  "success": true,
  "data": { ... },
  "message": "Optional message"
}
```

**Paginated Response**:
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

**Error Response** (4xx/5xx):
```json
{
  "success": false,
  "message": "Error description",
  "errors": { "field": ["Validation error"] }  // For 422 validation errors
}
```

### API Endpoints Overview

#### Admin API (Requires Authentication)

**Authentication**:
- `POST /api/auth/login` - Login (returns `token`)
- `POST /api/auth/logout` - Logout
- `GET /api/user` - Get authenticated user profile

**Products**:
- `GET /api/master/product` - List products (paginated, filterable, sortable)
- `POST /api/master/product` - Create product
- `GET /api/master/product/{id}` - Get product details
- `PUT /api/master/product/{id}` - Update product
- `DELETE /api/master/product/{id}` - Delete product

**Transactions**:
- `GET /api/transaction/selling` - List sales transactions
- `POST /api/transaction/selling` - Create sale
- `GET /api/transaction/selling/{id}` - Get sale details
- `GET /api/transaction/cash-drawer` - Get cash drawer status
- `POST /api/transaction/cash-drawer` - Open cash drawer

**Settings**:
- `GET /api/about` - Get shop information
- `PUT /api/about` - Update shop information
- `GET /api/setting/all` - Get all settings
- `POST /api/setting` - Update setting

#### Storefront API (E-commerce/Public)

The Storefront API allows building custom e-commerce frontends:

**Public Endpoints** (No Authentication):
- `GET /api/storefront/products` - List visible products
- `GET /api/storefront/products/{id}` - Get product details

**Customer Authentication**:
- `POST /api/storefront/auth/register` - Register new customer
- `POST /api/storefront/auth/login` - Customer login
- `GET /api/storefront/auth/me` - Get customer profile (requires token)
- `POST /api/storefront/auth/logout` - Customer logout

**Example: List Products**
```bash
curl "https://mystore.lakasir.com/api/storefront/products?per_page=20&sort=-selling_price&filter[category_id]=5"
```

**Example: Customer Login**
```bash
curl -X POST "https://mystore.lakasir.com/api/storefront/auth/login" \
  -H "Content-Type: application/json" \
  -d '{"email":"customer@example.com","password":"password123"}'
```

For complete API documentation, visit the Scalar docs at `https://{your-tenant-domain}/docs`.

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
