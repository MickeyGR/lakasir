<?php

namespace App\Http\Controllers\Api\Tenants\Storefront;

use App\Filters\ComparisonFilter;
use App\Http\Controllers\Controller;
use App\Http\Filters\SearchFields;
use App\Http\Requests\Tenants\Master\ProductIndexRequest;
use App\Http\Resources\ProductCollection;
use App\Models\Tenants\Product;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Get list of products for storefront
     *
     * @response array{
     *   success: boolean,
     *   data: array{
     *     data: \App\Http\Resources\ProductCollection[],
     *     links: array,
     *     meta: array
     *   },
     *   message: ?string
     * }
     */
    public function index(ProductIndexRequest $request)
    {
        // Reuse the same logic but maybe apply stricter scopes for public view if needed
        // For now, mirroring the main ProductController but strictly readonly
        
        $products = QueryBuilder::for(Product::class)
            ->allowedFilters([
                'name',
                'category_id',
                'sellingPrice',
                // 'initialPrice', // Maybe hide initial price filter?
                'type',
                'category.name',
                'unit',
                // 'show', // Force show=1
                ...ComparisonFilter::setFilters('stock', ['gt', 'ge', 'lt', 'le', 'eq', 'ne']),
                AllowedFilter::custom('global', new SearchFields, 'name,sku,barcode'),
            ])
            ->allowedIncludes(['category', 'images'])
            ->allowedSorts(['name', 'selling_price', 'created_at']) // Removed initial_price sort
            ->where('show', 1) // Enforce showing only visible products
            ->orderByDesc('created_at')
            ->simplePaginate($request->per_page);

        return $this->buildResponse()
            ->setData(ProductCollection::collection($products))
            ->present();
    }

    /**
     * Show a product details
     *
     * @response array{
     *   success: boolean,
     *   data: \App\Http\Resources\ProductCollection,
     *   message: ?string
     * }
     */
    public function show(int $id)
    {
        $product = Product::find($id);
        
        if (!$product || !$product->show) {
             return $this->error('Product not found', 404);
        }

        $product->load(['category', 'stocks']);
        
        // Use the same resource for now, but potentially map to a simpler one later
        $productResource = new ProductCollection($product);

        return $this->buildResponse()
            ->setData($productResource)
            ->present();
    }
}
