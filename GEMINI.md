# Guía de Integración E-commerce (Lakasir)

Esta documentación está dirigida a los desarrolladores del Frontend (Tienda Online) que consumirán la API de Lakasir.

## Base URL
La API se consume a través del dominio del tenant (tienda).
Ejemplo: `https://tienda-demo.lakasir.com/api` o `https://midominio.com/api`

## Formato de Respuesta General
Todas las respuestas exitosas (200 OK) siguen esta estructura:

```json
{
  "success": true, // Indica si la operación lógica fue exitosa
  "data": { ... }, // El objeto o lista solicitada
  "message": "Mensaje opcional" // Mensaje para mostrar al usuario (si aplica)
}
```

Los errores de validación (422) siguen el estándar de Laravel:
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email field is required."]
  }
}
```

---

## 1. Catálogo de Productos (Público)

### Listar Productos
Obtiene el listado de productos visibles en la tienda.

- **Método:** `GET`
- **Endpoint:** `/storefront/products`
- **Parámetros (Query String):**
  - `page`: Número de página (default: 1).
  - `per_page`: Cantidad por página (default: 15).
  - `sort`: Ordenamiento. Usa `-` para descendente.
    - Campos permitidos: `name`, `selling_price`, `created_at`.
    - Ejemplo: `sort=-selling_price` (Mayor a menor precio).
  - `filter[name]`: Buscar por nombre.
  - `filter[category_id]`: Filtrar por ID de categoría.
  - `filter[category.name]`: Filtrar por nombre de categoría (exacto).
  - `filter[global]`: Búsqueda general por nombre, SKU o código de barras.

**Ejemplo de Respuesta:**
```json
{
  "success": true,
  "data": {
    "data": [
      {
        "id": 101,
        "name": "Camiseta Deportiva",
        "selling_price": 25.50,
        "images": [...],
        "category": { "id": 5, "name": "Ropa" },
        "sku": "CAM-001",
        "stock": 50
      }
    ],
    "links": { ... }, // Paginación
    "meta": {
      "current_page": 1,
      "last_page": 5,
      "total": 75
    }
  }
}
```

### Detalle de Producto
Obtiene la información completa de un producto específico.

- **Método:** `GET`
- **Endpoint:** `/storefront/products/{id}`

**Ejemplo de Respuesta:**
```json
{
  "success": true,
  "data": {
    "id": 101,
    "name": "Camiseta Deportiva",
    "description": "...",
    "selling_price": 25.50,
    "stock": 50,
    "images": [...],
    "category": { ... }
  }
}
```

---

## 2. Autenticación de Clientes (Members)

Los clientes de la tienda se autentican como `Members`. El token obtenido debe enviarse en el header `Authorization`.

**Header Requerido (para endpoints protegidos):**
`Authorization: Bearer <tu_token>`

### Registrar Cliente
- **Método:** `POST`
- **Endpoint:** `/storefront/auth/register`
- **Body:**
  ```json
  {
    "name": "Juan Perez",
    "email": "juan@example.com",
    "password": "password123",
    "phone": "555-0199" // Opcional
  }
  ```

### Iniciar Sesión
- **Método:** `POST`
- **Endpoint:** `/storefront/auth/login`
- **Body:**
  ```json
  {
    "email": "juan@example.com",
    "password": "password123"
  }
  ```
- **Respuesta:** Contiene el `token` que debes guardar (localStorage/cookies).

### Obtener Perfil (Me)
Requiere Token.

- **Método:** `GET`
- **Endpoint:** `/storefront/auth/me`
- **Respuesta:** Datos del cliente actual.

### Cerrar Sesión
Requiere Token.

- **Método:** `POST`
- **Endpoint:** `/storefront/auth/logout`

---

## 3. Flujo de Compra (Próximamente)
Actualmente el carrito se maneja del lado del cliente. La creación de la orden requerirá que el usuario esté autenticado (`/storefront/auth/login`).
