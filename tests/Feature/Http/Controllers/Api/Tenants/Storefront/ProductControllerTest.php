<?php

use App\Models\Tenants\Category;
use App\Models\Tenants\Product;
use Tests\RefreshDatabaseWithTenant;

use function Pest\Laravel\get;

uses(RefreshDatabaseWithTenant::class);

beforeEach(function () {
    Category::query()->firstOrCreate([
        'name' => 'Electronics',
    ]);
});

function createStorefrontApiProduct(array $attributes = []): Product
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
    ], $attributes));
}

test('storefront product index excludes products hidden only from storefront', function () {
    createStorefrontApiProduct([
        'name' => 'Visible Product',
    ]);

    createStorefrontApiProduct([
        'name' => 'Battery Spare',
        'show_in_storefront' => false,
    ]);

    $response = get('/api/storefront/products');

    $response->assertOk()
        ->assertJsonCount(1, 'data.data')
        ->assertJsonPath('data.data.0.name', 'Visible Product');
});

test('storefront product detail returns 404 for products hidden only from storefront', function () {
    $product = createStorefrontApiProduct([
        'show_in_storefront' => false,
    ]);

    get("/api/storefront/products/{$product->id}")
        ->assertStatus(404)
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Product not found');
});
