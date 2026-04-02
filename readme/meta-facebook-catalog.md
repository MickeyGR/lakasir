# Meta/Facebook Catalog Feed

Ruta final por tenant:

`GET /api/storefront/meta/catalog.csv`

Configuracion requerida:

- Setting tenant `storefront_public_base_url` con una URL absoluta del storefront publico, por ejemplo `https://midominio.com`.
- Si no existe, el endpoint responde `500` con un error controlado y escribe un error en logs.
- La moneda sale de `Setting::get('currency', 'IDR')`.
- El feed es publico. No requiere `Authorization: Bearer ...`.

Nota operativa para que el endpoint responda:

- El tenant debe existir en la base central (`tenants` + `domains`) y su base tenant debe estar accesible.
- Debe existir el setting `storefront_public_base_url` dentro de la base tenant.
- Puedes configurarlo desde el panel en `General Setting`, via `POST /api/setting`, o insertando la fila en `settings` si estas recuperando un tenant antiguo.
- Si el tenant tiene base de datos pero no registro central, primero hay que re-vincularlo en `tenants` y `domains`; de lo contrario el dominio tenant devolvera `404` o no inicializara tenancy.

Ejemplo local:

- Tenant: `tenantdemo.localdomain.test`
- `storefront_public_base_url`: `http://tenantdemo.localdomain.test/`
- Feed resultante: `http://tenantdemo.localdomain.test:8000/api/storefront/meta/catalog.csv`

Mapeo principal:

- `id`: `sku` si existe; en su defecto el `id` interno como string.
- `title`: `products.name`.
- `description`: texto plano de `description` si existiera; hoy hace fallback al nombre del producto.
- `availability`: `in stock` si `stock > 0`, `out of stock` si `stock <= 0`.
- `condition`: `new`.
- `price`: `selling_price` formateado como `1234.56 NIO`.
- `link`: `{storefront_public_base_url}/product/{id}`.
- `image_link`: primera imagen publica valida.
- `additional_image_link`: imagenes adicionales validas separadas por comas.
- `brand`: marca del producto si existiera; hoy hace fallback a `about.shop_name`.
- `product_type`: nombre de la categoria.
- `status`: `active`.
- `inventory`: stock actual entero.
- `fb_product_category` y `google_product_category`: vacios mientras no exista un mapeo taxonomico propio.

Decisiones de inclusion/exclusion:

- Se reutiliza la misma base de visibilidad del storefront actual: productos con `show = 1` y no borrados por soft delete.
- Productos sin stock no se excluyen; se publican como `out of stock`.
- Productos ocultos o borrados no entran al feed.
- Productos sin imagen publica valida se excluyen del feed y se registra una advertencia en logs. No se introdujo placeholder porque no existe una convencion valida en el proyecto.
- No se incluyen filas `archived`; en este codebase la publicacion storefront actual se resuelve con `show` + `soft deletes`, asi que el feed solo emite `status=active` para filas realmente publicadas.

Ejemplo CSV:

```csv
id,title,description,availability,condition,price,link,image_link,brand,additional_image_link,product_type,status,inventory,fb_product_category,google_product_category
ELE-LAP-0001,"Laptop HP Pavilion 15","Laptop HP Pavilion 15","in stock","new","1234.56 NIO","https://midominio.com/product/101","https://tenant.midominio.com/storage/product/laptop-main.jpg","Mi Tienda","https://tenant.midominio.com/storage/product/laptop-side.jpg,https://tenant.midominio.com/storage/product/laptop-back.jpg","Electronics","active","25",,
ELE-MOU-0002,"Mouse Logitech MX Master 3","Mouse Logitech MX Master 3","out of stock","new","79.99 NIO","https://midominio.com/product/102","https://tenant.midominio.com/storage/product/mouse-main.jpg","Mi Tienda","","Accessories","active","0",,
```
