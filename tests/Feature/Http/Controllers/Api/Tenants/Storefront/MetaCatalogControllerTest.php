<?php

use App\Models\Tenants\About;
use App\Models\Tenants\Category;
use App\Models\Tenants\Product;
use App\Models\Tenants\ProductImage;
use App\Models\Tenants\Setting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Tests\RefreshDatabaseWithTenant;

use function Pest\Laravel\get;

uses(RefreshDatabaseWithTenant::class);

function metaCatalogHeaders(): array
{
    return [
        'id',
        'title',
        'description',
        'availability',
        'condition',
        'price',
        'link',
        'image_link',
        'brand',
        'additional_image_link',
        'product_type',
        'status',
        'inventory',
        'fb_product_category',
        'google_product_category',
    ];
}

function metaCatalogRows(string $content): array
{
    $lines = preg_split('/\r\n|\r|\n/', trim($content)) ?: [];
    $headers = str_getcsv(array_shift($lines) ?: '');

    return array_values(array_filter(array_map(function (string $line) use ($headers) {
        if ($line === '') {
            return null;
        }

        $row = str_getcsv($line);

        return array_combine($headers, $row);
    }, $lines)));
}

function fakePublicImage(string $path): string
{
    Storage::disk('public')->put($path, 'image');

    return Storage::disk('public')->url($path);
}

beforeEach(function () {
    Storage::fake('public');

    $about = About::query()->first() ?? new About();
    $about->fill([
        'shop_name' => 'Tienda Demo',
        'business_type' => 'retail',
        'shop_location' => 'Managua',
    ]);
    $about->save();

    Setting::set('currency', 'NIO');
    Setting::set('storefront_public_base_url', 'https://storefront.example.com');

    Category::query()->firstOrCreate([
        'name' => 'Electronics',
    ]);
});

function createStorefrontProduct(array $attributes = []): Product
{
    return Product::factory()->create(array_merge([
        'category_id' => Category::query()->where('name', 'Electronics')->firstOrFail()->getKey(),
        'name' => 'Laptop HP Pavilion 15',
        'stock' => 25,
        'initial_price' => 800,
        'selling_price' => 999.99,
        'unit' => 'pcs',
        'type' => 'product',
        'show' => true,
        'show_in_storefront' => true,
        'hero_images' => [fakePublicImage('product/laptop-main.jpg')],
    ], $attributes));
}

test('meta catalog feed includes a visible product with positive stock', function () {
    $product = createStorefrontProduct([
        'name' => 'Laptop HP Pavilion 15',
        'selling_price' => 1234.56,
    ]);

    $response = get('/api/storefront/meta/catalog.csv');

    $response->assertOk();
    expect(strtolower((string) $response->headers->get('content-type')))->toContain('text/csv');
    expect(strtolower((string) $response->headers->get('content-type')))->toContain('charset=utf-8');

    $content = $response->streamedContent();
    $rows = metaCatalogRows($content);
    $lines = preg_split('/\r\n|\r|\n/', trim($content)) ?: [];

    expect(str_getcsv($lines[0] ?? ''))->toBe(metaCatalogHeaders());
    expect($rows)->toHaveCount(1);
    expect($rows[0]['id'])->toBe($product->sku);
    expect($rows[0]['title'])->toBe('Laptop HP Pavilion 15');
    expect($rows[0]['availability'])->toBe('in stock');
    expect($rows[0]['price'])->toBe('1234.56 NIO');
    expect($rows[0]['link'])->toBe("https://storefront.example.com/product/{$product->id}");
    expect($rows[0]['brand'])->toBe('Tienda Demo');
    expect($rows[0]['product_type'])->toBe('Electronics');
    expect($rows[0]['status'])->toBe('active');
    expect($rows[0]['inventory'])->toBe('25');
    expect($rows[0]['image_link'])->toMatch('/^https?:\/\/.+\/storage\/product\/laptop-main\.jpg$/');
});

test('meta catalog feed keeps visible products without stock and marks them out of stock', function () {
    createStorefrontProduct([
        'name' => 'Mouse Logitech',
        'stock' => 0,
        'hero_images' => [fakePublicImage('product/mouse-main.jpg')],
    ]);

    $response = get('/api/storefront/meta/catalog.csv');
    $rows = metaCatalogRows($response->streamedContent());

    expect($rows)->toHaveCount(1);
    expect($rows[0]['availability'])->toBe('out of stock');
    expect($rows[0]['inventory'])->toBe('0');
});

test('meta catalog feed falls back description to product name when no description exists', function () {
    createStorefrontProduct([
        'name' => 'Teclado Mecanico',
        'hero_images' => [fakePublicImage('product/keyboard-main.jpg')],
    ]);

    $response = get('/api/storefront/meta/catalog.csv');
    $rows = metaCatalogRows($response->streamedContent());

    expect($rows[0]['description'])->toBe('Teclado Mecanico');
});

test('meta catalog feed falls back brand to shop name when product brand does not exist', function () {
    createStorefrontProduct([
        'name' => 'Monitor Samsung',
        'hero_images' => [fakePublicImage('product/monitor-main.jpg')],
    ]);

    $response = get('/api/storefront/meta/catalog.csv');
    $rows = metaCatalogRows($response->streamedContent());

    expect($rows[0]['brand'])->toBe('Tienda Demo');
});

test('meta catalog feed emits additional image links for products with multiple images', function () {
    createStorefrontProduct([
        'name' => 'Camara Canon',
        'hero_images' => [
            fakePublicImage('product/camera-main.jpg'),
            fakePublicImage('product/camera-side.jpg'),
            fakePublicImage('product/camera-back.jpg'),
        ],
    ]);

    $response = get('/api/storefront/meta/catalog.csv');
    $rows = metaCatalogRows($response->streamedContent());

    expect($rows[0]['image_link'])->toMatch('/camera-main\.jpg$/');
    expect($rows[0]['additional_image_link'])->toMatch('/camera-side\.jpg/');
    expect($rows[0]['additional_image_link'])->toMatch('/camera-back\.jpg/');
});

test('meta catalog feed excludes products hidden from storefront', function () {
    createStorefrontProduct([
        'name' => 'Visible Product',
        'hero_images' => [fakePublicImage('product/visible-main.jpg')],
    ]);

    createStorefrontProduct([
        'name' => 'Hidden Product',
        'show_in_storefront' => false,
        'hero_images' => [fakePublicImage('product/hidden-main.jpg')],
    ]);

    $response = get('/api/storefront/meta/catalog.csv');
    $rows = metaCatalogRows($response->streamedContent());

    expect($rows)->toHaveCount(1);
    expect($rows[0]['title'])->toBe('Visible Product');
});

test('meta catalog feed excludes products inactive in the system', function () {
    createStorefrontProduct([
        'name' => 'Visible Product',
        'hero_images' => [fakePublicImage('product/visible-main.jpg')],
    ]);

    createStorefrontProduct([
        'name' => 'Inactive Product',
        'show' => false,
        'show_in_storefront' => true,
        'hero_images' => [fakePublicImage('product/inactive-main.jpg')],
    ]);

    $response = get('/api/storefront/meta/catalog.csv');
    $rows = metaCatalogRows($response->streamedContent());

    expect($rows)->toHaveCount(1);
    expect($rows[0]['title'])->toBe('Visible Product');
});

test('meta catalog feed supports legacy product_images relation as image source', function () {
    $product = createStorefrontProduct([
        'hero_images' => [],
    ]);

    ProductImage::query()->create([
        'product_id' => $product->getKey(),
        'name' => 'legacy-main.jpg',
        'url' => fakePublicImage('product/legacy-main.jpg'),
        'size' => '10',
        'type' => 'image/jpeg',
    ]);

    ProductImage::query()->create([
        'product_id' => $product->getKey(),
        'name' => 'legacy-second.jpg',
        'url' => fakePublicImage('product/legacy-second.jpg'),
        'size' => '10',
        'type' => 'image/jpeg',
    ]);

    $response = get('/api/storefront/meta/catalog.csv');
    $rows = metaCatalogRows($response->streamedContent());

    expect($rows[0]['image_link'])->toMatch('/legacy-main\.jpg$/');
    expect($rows[0]['additional_image_link'])->toMatch('/legacy-second\.jpg$/');
});

test('meta catalog feed returns controlled error when storefront public base url is missing', function () {
    Log::spy();
    Setting::set('storefront_public_base_url', '');
    createStorefrontProduct();

    $response = get('/api/storefront/meta/catalog.csv');

    $response->assertStatus(500)
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Storefront public base URL is not configured for this tenant.');

    Log::shouldHaveReceived('error')->once();
});
