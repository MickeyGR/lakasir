# AGENTS.md

## Project overview
- Lakasir is a Laravel 11 + Filament 3 POS web app with Livewire/Volt and Tailwind/Vite assets.
- Multi-tenant setup via `stancl/tenancy`.

## Tech stack
- PHP 8.2, Laravel 11, Filament 3, Livewire 3, Volt.
- Vite + TailwindCSS for frontend assets.
- MySQL (per README).

## Key directories
- `app/`: application code (models, actions, services, policies, etc.).
- `routes/`: HTTP routes (`web.php`, `api.php`).
- `resources/`: views, assets, and frontend entrypoints.
- `database/`: migrations, factories, seeders (tenant migrations in `database/migrations/tenant`).
- `config/`: application config.
- `public/`: public entry.
- `tests/`: Pest tests.

## Common commands
- Install PHP deps: `composer install`; JS deps: `npm install`.
- Env setup: `cp .env.example .env`, set `APP_URL=http://localdomain.test:8000`, `APP_CENTRAL_DOMAIN=localdomain.test`, DB creds.
- App key: `php artisan key:generate`.
- Migrations (central): `php artisan migrate`.
- Build assets: `npm run build`; dev assets: `npm run dev`.
- Run tests: `php artisan test` (Pest).
- Local hosts: add `localdomain.test` and tenant domains you create (e.g., `tenantdemo.localdomain.test`) to `/etc/hosts`; VS Code task `Hosts: add localdomain.test` handles the base entry.
- Generate API docs: `php artisan scramble:export` (writes `api.json` used by Scalar).

## API docs
- Scalar UI: `/scalar` (OpenAPI JSON served at `/docs/api.json`).
- Generate/refresh spec: `php artisan scramble:export` (uses Scramble; UI route is disabled).
- Examples seeded (tenant `tenantdemo`): auth/login/logout/me, check, about (GET/PUT), settings (GET/POST), category, product (list/create/update/stock), member, payment-method, selling (list/create/detail), dashboard totals, cash-drawer, cashier report, notifications, upload, secure-initial-price, register-fcm-token, domain/register, etc. (most API groups covered with real/sampled payloads).
- Refresh in Scalar: after `php artisan scramble:export`, hard reload the browser (or open in incognito) to bust cache and load the new `/docs/api.json`.

## Notes for agents
- Respect tenancy boundaries; tenant migrations live under `database/migrations/tenant`.
- Filament assets may require `php artisan filament:assets` and `php artisan livewire:publish --assets` during setup.
- Frontend build uses Vite; check `vite.config.js` for entrypoints.

## Local workflows
### First-time setup (multi-tenant)
- `cp .env.example .env` and set `APP_URL=http://localdomain.test:8000`, `APP_CENTRAL_DOMAIN=localdomain.test`, DB/mail/storage as needed.
- `composer install`, `npm install`.
- `php artisan key:generate`.
- `php artisan migrate` (central tables).
- `php artisan filament:assets`, `php artisan livewire:publish --assets`.
- Start servers: `php artisan serve --host=localdomain.test --port=8000` and `npm run dev`.
- Hosts: add `127.0.0.1 localdomain.test` and any tenant domains you register (e.g., `tenantdemo.localdomain.test`).
### Daily dev loop
- Backend: `php artisan serve`; Frontend: `npm run dev`; queue/scheduler if used.
- Web (central): landing `/`; tenant panel: `http://{tenant}.localdomain.test/member`.
- Tenancy: create tenants via UI `/auth/register` or API POST `/api/domain/register`; this migrates `database/migrations/tenant` and seeds permissions/payment methods/categories for that tenant.
### Standalone mode (no central domain)
- If `APP_CENTRAL_DOMAIN` is empty, use `php artisan app:create-user` to create the owner admin and log in at `{APP_URL}/member/login`.

### Useful checks
- `php artisan test`
- `php artisan route:list`
- `php artisan config:clear` when env/config changes

## WebStorm run configurations (suggested)
Create these in WebStorm > Run | Edit Configurations:

### 1) Laravel app server
- Type: PHP Built-in Web Server
- Document root: `public`
- Host: `127.0.0.1`
- Port: `8000`
- PHP interpreter: your local PHP 8.2+
- Alternative: Type `Shell Script` and run `php artisan serve`

### 2) Vite dev server
- Type: npm
- Package manager: project `package.json`
- Command: `run`
- Scripts: `dev`

### 3) Queue worker (optional)
- Type: Shell Script
- Script: `php artisan queue:work`
- Working directory: project root

### 4) Scheduler (optional)
- Type: Shell Script
- Script: `php artisan schedule:work`
- Working directory: project root

### 5) Tests
- Type: PHPUnit or Pest (if plugin installed)
- Config file: `phpunit.xml`
- Working directory: project root

## Deploy notes (fill in for your environment)
- Ensure `.env` is set for production and `APP_ENV=production`.
- Run `composer install --no-dev` on servers.
- Run `php artisan migrate` (central) and tenant migrations via `tenants:migrate` or tenant registration flow as needed.
- Run `npm run build` and publish assets.
- Configure queue/scheduler (Supervisor/systemd/cron) if used.

## Quick routes/URLs
- Central (`APP_URL`): landing `/` (from `resources/views/livewire/pages/welcome.blade.php`), register tenant `/auth/register`, PWA `/offline`, `/serviceworker.js`, API `/api/domain/register`, `/api/test`. No `/admin` panel.
- Tenant domain (e.g., `tenantdemo.localdomain.test`): `/` redirects to `/member` (Filament panel/POS). Extra pages: `/member/sellings/{id}/print`, `/member/*-report/generate`, `/reset-password/{token}`.
- Tenant API: `https://{tenant-domain}/api/*` (auth, master data, transaction selling/cash-drawer, settings, reports, printer, notification, uploads, `GET /api/check`).


-------------------


# Lakasir Storefront API - Guía para Desarrollo E-commerce

Esta guía documenta la API de Storefront de Lakasir para desarrolladores que construyan aplicaciones e-commerce (web, móvil, etc.).

## Base URL

La API de cada tienda está en su dominio de tenant:

```
https://{tu-tenant}.lakasir.com/api/storefront/
```

**Ejemplo**: Si tu tenant es `mitienda`, la base URL sería:
```
https://mitienda.lakasir.com/api/storefront/
```

---

## Nota Importante sobre Curl

**IMPORTANTE**: Cuando uses `curl` con filtros que contienen corchetes `[]`, debes incluir la opción `-g` (globbing off) para evitar errores:

```bash
# ✅ CORRECTO - Con -g
curl -g "https://mitienda.lakasir.com/api/storefront/products?filter[stock-gt]=0"

# ❌ ERROR - Sin -g
curl "https://mitienda.lakasir.com/api/storefront/products?filter[stock-gt]=0"
# curl: (3) bad range in URL position...
```

Alternativamente, puedes usar comillas simples sin `-g`:
```bash
curl 'https://mitienda.lakasir.com/api/storefront/products?filter[stock-gt]=0'
```

---

## Formato de Respuesta Estándar

Todas las respuestas exitosas siguen esta estructura:

```json
{
  "success": true,
  "data": { ... },
  "message": "Mensaje opcional"
}
```

### Respuestas Paginadas

```json
{
  "success": true,
  "data": {
    "data": [ ... ],
    "links": {
      "first": "https://...",
      "prev": null,
      "next": "https://..."
    },
    "meta": {
      "current_page": 1,
      "from": 1,
      "path": "https://...",
      "per_page": 15,
      "to": 15
    }
  }
}
```

### Respuestas de Error

**Errores de Validación (422)**:
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email field is required."]
  }
}
```

**Error de Autenticación (401)**:
```json
{
  "message": "Unauthenticated."
}
```

**Error de Recurso No Encontrado (404)**:
```json
{
  "success": false,
  "message": "Product not found"
}
```

---

## Autenticación

Los endpoints que requieren autenticación necesitan un token Bearer en el header:

```
Authorization: Bearer {token}
```

El token se obtiene al hacer login o registro de cliente.

---

## Endpoints de Autenticación de Clientes

### 1. Registro de Cliente

Crea una nueva cuenta de cliente.

**Endpoint**: `POST /storefront/auth/register`

**Headers**:
```
Content-Type: application/json
```

**Body**:
```json
{
  "name": "Juan Pérez",
  "email": "juan@example.com",
  "password": "password123",
  "phone": "555-1234"
}
```

**Campos**:
- `name` (string, requerido): Nombre completo del cliente
- `email` (string, requerido): Email único
- `password` (string, requerido, min 8 caracteres): Contraseña
- `phone` (string, opcional): Teléfono

**Respuesta Exitosa (201)**:
```json
{
  "success": true,
  "message": "User registered successfully",
  "token": "1|abcdefghijklmnopqrstuvwxyz123456",
  "user": {
    "id": 42,
    "name": "Juan Pérez",
    "email": "juan@example.com",
    "phone": "555-1234",
    "code": "CUS-1707434567",
    "created_at": "2024-02-08T19:22:47.000000Z",
    "updated_at": "2024-02-08T19:22:47.000000Z"
  }
}
```

**Errores (422)**:
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email has already been taken."]
  }
}
```

**Ejemplo curl**:
```bash
curl -g -X POST "https://mitienda.lakasir.com/api/storefront/auth/register" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Juan Pérez",
    "email": "juan@example.com",
    "password": "password123",
    "phone": "555-1234"
  }'
```

---

### 2. Inicio de Sesión

Autentica un cliente existente.

**Endpoint**: `POST /storefront/auth/login`

**Headers**:
```
Content-Type: application/json
```

**Body**:
```json
{
  "email": "juan@example.com",
  "password": "password123"
}
```

**Respuesta Exitosa (200)**:
```json
{
  "success": true,
  "message": "Login successful",
  "token": "2|xyz789abc456def123ghi456jkl789",
  "user": {
    "id": 42,
    "name": "Juan Pérez",
    "email": "juan@example.com",
    "phone": "555-1234",
    "code": "CUS-1707434567",
    "created_at": "2024-02-08T19:22:47.000000Z",
    "updated_at": "2024-02-08T19:22:47.000000Z"
  }
}
```

**Errores (422)**:
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["Las credenciales proporcionadas son incorrectas."]
  }
}
```

**Ejemplo curl**:
```bash
curl -g -X POST "https://mitienda.lakasir.com/api/storefront/auth/login" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "juan@example.com",
    "password": "password123"
  }'
```

---

### 3. Obtener Perfil del Cliente

Obtiene los datos del cliente autenticado.

**Endpoint**: `GET /storefront/auth/me`

**Headers**:
```
Authorization: Bearer {token}
```

**Respuesta Exitosa (200)**:
```json
{
  "success": true,
  "data": {
    "id": 42,
    "name": "Juan Pérez",
    "email": "juan@example.com",
    "phone": "555-1234",
    "code": "CUS-1707434567",
    "created_at": "2024-02-08T19:22:47.000000Z",
    "updated_at": "2024-02-08T19:22:47.000000Z"
  }
}
```

**Errores (401)**:
```json
{
  "message": "Unauthenticated."
}
```

**Ejemplo curl**:
```bash
curl -g -X GET "https://mitienda.lakasir.com/api/storefront/auth/me" \
  -H "Authorization: Bearer 2|xyz789abc456def123ghi456jkl789"
```

---

### 4. Cerrar Sesión

Invalida el token actual del cliente.

**Endpoint**: `POST /storefront/auth/logout`

**Headers**:
```
Authorization: Bearer {token}
```

**Respuesta Exitosa (200)**:
```json
{
  "success": true,
  "message": "Logged out"
}
```

**Errores (401)**:
```json
{
  "message": "Unauthenticated."
}
```

**Ejemplo curl**:
```bash
curl -g -X POST "https://mitienda.lakasir.com/api/storefront/auth/logout" \
  -H "Authorization: Bearer 2|xyz789abc456def123ghi456jkl789"
```

---

## Endpoints de Tienda (Públicos)

### 1. About de la Tienda

Obtiene la información pública de la tienda.

**Endpoint**: `GET /storefront/about`

**Headers**: Ninguno (público)

**Respuesta Exitosa (200)**:
```json
{
  "success": true,
  "data": {
    "shop_name": "NicaPC",
    "shop_location": "Managua",
    "owner_name": "MICKEY GUDIEL REYES",
    "business_type": "other",
    "other_business_type": "Computo",
    "phone": "+50589897898",
    "facebook": "https://facebook.com/nicapc",
    "messenger": "nicapc",
    "website": "https://nicapc.com",
    "linkedin": "https://linkedin.com/company/nicapc",
    "currency": "NIO",
    "photo_url": ""
  }
}
```

**Ejemplo curl**:
```bash
curl -g "https://mitienda.lakasir.com/api/storefront/about"
```

---

## Endpoints de Productos (Públicos)

### 1. Listar Productos

Obtiene la lista de productos visibles en la tienda.

**Endpoint**: `GET /storefront/products`

**Headers**: Ninguno (público)

**Query Parameters**:

#### Paginación
- `page` (integer): Número de página (default: 1). Ejemplo: `page=2`
- `per_page` (integer): Cantidad por página (default: 15). Ejemplo: `per_page=20`

#### Ordenamiento
- `sort` (string): Campo de ordenamiento. Usa `-` para descendente.
  - Campos permitidos: `name`, `selling_price`, `created_at`
  - Ejemplos:
    - `sort=name` (A-Z)
    - `sort=-selling_price` (Mayor a menor precio)
    - `sort=-created_at` (Más recientes primero)

#### Filtros
- `filter[name]` (string): Buscar por nombre de producto. Ejemplo: `filter[name]=Laptop`
- `filter[category_id]` (integer): Filtrar por ID de categoría. Ejemplo: `filter[category_id]=5`
- `filter[category.name]` (string): Filtrar por nombre de categoría. Ejemplo: `filter[category.name]=Electronics`
- `filter[type]` (string): Filtrar por tipo de producto. Ejemplo: `filter[type]=product`
- `filter[unit]` (string): Filtrar por unidad. Ejemplo: `filter[unit]=pcs`
- `filter[global]` (string): Búsqueda global en nombre, SKU y código de barras. Ejemplo: `filter[global]=LAP-001`

#### Filtros de Stock
- `filter[stock-gt]` (integer): Stock mayor que. Ejemplo: `filter[stock-gt]=10`
- `filter[stock-ge]` (integer): Stock mayor o igual que. Ejemplo: `filter[stock-ge]=10`
- `filter[stock-lt]` (integer): Stock menor que. Ejemplo: `filter[stock-lt]=50`
- `filter[stock-le]` (integer): Stock menor o igual que. Ejemplo: `filter[stock-le]=50`
- `filter[stock-eq]` (integer): Stock igual a. Ejemplo: `filter[stock-eq]=20`
- `filter[stock-ne]` (integer): Stock diferente de. Ejemplo: `filter[stock-ne]=0`

#### Includes (Relaciones)
- `include` (string): Relaciones a incluir (separadas por coma).
  - Opciones: `category`, `images`
  - Ejemplo: `include=category,images`

**Respuesta Exitosa (200)**:
```json
{
  "success": true,
  "data": {
    "data": [
      {
        "id": 101,
        "name": "Laptop HP Pavilion 15",
        "category": {
          "id": 5,
          "name": "Electrónica"
        },
        "category_id": 5,
        "initial_price": 800.00,
        "selling_price": 999.99,
        "type": "product",
        "unit": "pcs",
        "stock": 25,
        "is_non_stock": false,
        "hero_images": "https://mitienda.lakasir.com/storage/products/laptop.jpg",
        "sku": "LAP-HP-001",
        "barcode": "7501234567890",
        "show": 1
      },
      {
        "id": 102,
        "name": "Mouse Logitech MX Master 3",
        "category": {
          "id": 6,
          "name": "Accesorios"
        },
        "category_id": 6,
        "initial_price": 50.00,
        "selling_price": 79.99,
        "type": "product",
        "unit": "pcs",
        "stock": 150,
        "is_non_stock": false,
        "hero_images": "https://mitienda.lakasir.com/storage/products/mouse.jpg",
        "sku": "MOU-LOG-001",
        "barcode": null,
        "show": 1
      }
    ],
    "links": {
      "first": "https://mitienda.lakasir.com/api/storefront/products?page=1",
      "prev": null,
      "next": "https://mitienda.lakasir.com/api/storefront/products?page=2"
    },
    "meta": {
      "current_page": 1,
      "from": 1,
      "path": "https://mitienda.lakasir.com/api/storefront/products",
      "per_page": 15,
      "to": 15
    }
  }
}
```

**Ejemplo curl (Simple)**:
```bash
curl -g "https://mitienda.lakasir.com/api/storefront/products"
```

**Ejemplo curl (Con filtros y ordenamiento)**:
```bash
curl -g "https://mitienda.lakasir.com/api/storefront/products?per_page=20&sort=-selling_price&filter[category_id]=5&include=category,images"
```

**Ejemplo curl (Búsqueda global)**:
```bash
curl -g "https://mitienda.lakasir.com/api/storefront/products?filter[global]=laptop"
```

**Ejemplo curl (Productos con stock)**:
```bash
curl -g "https://mitienda.lakasir.com/api/storefront/products?filter[stock-gt]=0"
```

**Ejemplo curl (Productos con stock bajo)**:
```bash
curl -g "https://mitienda.lakasir.com/api/storefront/products?filter[stock-lt]=10&sort=stock"
```

---

### 2. Detalle de Producto

Obtiene los detalles de un producto específico.

**Endpoint**: `GET /storefront/products/{id}`

**Headers**: Ninguno (público)

**URL Parameters**:
- `id` (integer, requerido): ID del producto. Ejemplo: `101`

**Respuesta Exitosa (200)**:
```json
{
  "success": true,
  "data": {
    "id": 101,
    "name": "Laptop HP Pavilion 15",
    "category": {
      "id": 5,
      "name": "Electrónica"
    },
    "category_id": 5,
    "initial_price": 800.00,
    "selling_price": 999.99,
    "type": "product",
    "unit": "pcs",
    "stock": 25,
    "is_non_stock": false,
    "hero_images": "https://mitienda.lakasir.com/storage/products/laptop.jpg",
    "sku": "LAP-HP-001",
    "barcode": "7501234567890",
    "show": 1,
    "stocks": [
      {
        "id": 1,
        "product_id": 101,
        "stock": 10,
        "init_stock": 10,
        "initial_price": 800.00,
        "selling_price": 999.99,
        "type": "in",
        "date": "2024-01-15"
      },
      {
        "id": 2,
        "product_id": 101,
        "stock": 15,
        "init_stock": 15,
        "initial_price": 800.00,
        "selling_price": 999.99,
        "type": "in",
        "date": "2024-02-01"
      }
    ]
  }
}
```

**Errores (404)**:
```json
{
  "success": false,
  "message": "Product not found"
}
```

**Ejemplo curl**:
```bash
curl -g "https://mitienda.lakasir.com/api/storefront/products/101"
```

---

## Flujo de Trabajo Típico para E-commerce

### 1. Catálogo de Productos (Sin autenticación)

```javascript
// 1. Obtener categorías populares
const categories = await fetch('https://mitienda.lakasir.com/api/storefront/products?per_page=100')
  .then(r => r.json())
  .then(data => {
    const cats = new Set();
    data.data.data.forEach(p => cats.add(JSON.stringify(p.category)));
    return Array.from(cats).map(c => JSON.parse(c));
  });

// 2. Mostrar productos por categoría
const productsByCategory = await fetch(
  `https://mitienda.lakasir.com/api/storefront/products?filter[category_id]=5&per_page=20&sort=-created_at`
).then(r => r.json());

// 3. Búsqueda de productos
const searchResults = await fetch(
  `https://mitienda.lakasir.com/api/storefront/products?filter[global]=laptop`
).then(r => r.json());

// 4. Detalle de producto
const product = await fetch('https://mitienda.lakasir.com/api/storefront/products/101')
  .then(r => r.json());
```

### 2. Registro/Login de Usuario

```javascript
// Registro
const registerResponse = await fetch('https://mitienda.lakasir.com/api/storefront/auth/register', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    name: 'Juan Pérez',
    email: 'juan@example.com',
    password: 'password123',
    phone: '555-1234'
  })
}).then(r => r.json());

// Guardar token
const token = registerResponse.token;
localStorage.setItem('auth_token', token);

// O Login
const loginResponse = await fetch('https://mitienda.lakasir.com/api/storefront/auth/login', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    email: 'juan@example.com',
    password: 'password123'
  })
}).then(r => r.json());

localStorage.setItem('auth_token', loginResponse.token);
```

### 3. Obtener Perfil del Usuario

```javascript
const token = localStorage.getItem('auth_token');

const profile = await fetch('https://mitienda.lakasir.com/api/storefront/auth/me', {
  headers: { 'Authorization': `Bearer ${token}` }
}).then(r => r.json());

console.log('Usuario:', profile.data);
```

### 4. Logout

```javascript
const token = localStorage.getItem('auth_token');

await fetch('https://mitienda.lakasir.com/api/storefront/auth/logout', {
  method: 'POST',
  headers: { 'Authorization': `Bearer ${token}` }
});

localStorage.removeItem('auth_token');
```

---

## Manejo de Errores Recomendado

```javascript
async function apiRequest(url, options = {}) {
  try {
    const response = await fetch(url, options);
    const data = await response.json();
    
    if (!response.ok) {
      // Error HTTP
      if (response.status === 422) {
        // Errores de validación
        console.error('Validation errors:', data.errors);
        throw new Error(Object.values(data.errors).flat().join(', '));
      } else if (response.status === 401) {
        // No autenticado
        console.error('Unauthorized - redirecting to login');
        localStorage.removeItem('auth_token');
        // Redirigir a login
        throw new Error('Sesión expirada');
      } else if (response.status === 404) {
        throw new Error(data.message || 'Recurso no encontrado');
      } else {
        throw new Error(data.message || 'Error en la petición');
      }
    }
    
    return data;
  } catch (error) {
    console.error('API Error:', error);
    throw error;
  }
}

// Uso:
try {
  const products = await apiRequest('https://mitienda.lakasir.com/api/storefront/products');
  console.log(products.data);
} catch (error) {
  alert(error.message);
}
```

---

## Notas Importantes

### Seguridad
- **HTTPS**: Siempre usa HTTPS en producción
- **Token Storage**: Guarda el token de forma segura (localStorage para web, secure storage para móvil)
- **CORS**: La API permite peticiones CORS desde cualquier origen
- **Rate Limiting**: Hay límites de peticiones por minuto (contacta al administrador para detalles)

### Productos
- Solo se muestran productos con `show = 1` (visibles)
- El campo `is_non_stock` indica si el producto NO maneja inventario
- `initial_price` es el costo del producto (puede estar oculto en algunos casos)
- `hero_images` es la URL de la imagen principal del producto

### Estructura del Código de Cliente
- El código del cliente se genera automáticamente como `CUS-{timestamp}`
- Es único por cliente y puede usarse para referencias

### Próximas Funcionalidades
- Carrito de compras (próximamente)
- Órdenes/Pedidos (próximamente)
- Métodos de pago (próximamente)
- Direcciones de envío (próximamente)

---

## Ejemplos Completos

### E-commerce Simple con Vanilla JavaScript

```html
<!DOCTYPE html>
<html>
<head>
  <title>Mi Tienda</title>
</head>
<body>
  <div id="app">
    <h1>Catálogo de Productos</h1>
    <input type="text" id="search" placeholder="Buscar productos...">
    <div id="products"></div>
  </div>

  <script>
    const API_BASE = 'https://mitienda.lakasir.com/api/storefront';
    
    // Cargar productos
    async function loadProducts(search = '') {
      const url = search 
        ? `${API_BASE}/products?filter[global]=${search}`
        : `${API_BASE}/products?per_page=20&sort=-created_at`;
      
      const response = await fetch(url);
      const data = await response.json();
      
      const productsDiv = document.getElementById('products');
      productsDiv.innerHTML = data.data.data.map(p => `
        <div class="product">
          <img src="${p.hero_images}" alt="${p.name}" width="200">
          <h3>${p.name}</h3>
          <p>${p.category.name}</p>
          <p class="price">$${p.selling_price}</p>
          <p>Stock: ${p.stock}</p>
          <button onclick="viewProduct(${p.id})">Ver Detalles</button>
        </div>
      `).join('');
    }
    
    // Buscar productos
    document.getElementById('search').addEventListener('input', (e) => {
      loadProducts(e.target.value);
    });
    
    // Ver detalle
    async function viewProduct(id) {
      const response = await fetch(`${API_BASE}/products/${id}`);
      const data = await response.json();
      alert(`Producto: ${data.data.name}\nPrecio: $${data.data.selling_price}`);
    }
    
    // Inicializar
    loadProducts();
  </script>
</body>
</html>
```

---

## Soporte

Para más información o soporte técnico:
- Email: lakasirapp@gmail.com
- Documentación interactiva: `https://{tu-tenant}.lakasir.com/docs`
