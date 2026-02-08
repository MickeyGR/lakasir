<?php

namespace App\Http\Controllers\Api\Tenants\Master;

use App\Filters\ComparisonFilter;
use App\Http\Controllers\Controller;
use App\Http\Filters\SearchFields;
use App\Http\Requests\Tenants\Master\ProductRequest;
use App\Http\Resources\ProductCollection;
use App\Models\Tenants\Product;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

use App\Http\Requests\Tenants\Master\ProductIndexRequest;

class ProductController extends Controller
{
    /**
     * Get list of products
     *
     * @response array{
     *   success: boolean,
     *   data: array{
     *     data: \App\Http\Resources\ProductCollection[],
     *     links: array{
     *       first: string,
     *       prev: ?string,
     *       next: ?string
     *     },
     *     meta: array{
     *       current_page: int,
     *       from: ?int,
     *       path: string,
     *       per_page: int,
     *       to: ?int
     *     }
     *   },
     *   message: ?string
     * }
     */
    public function index(ProductIndexRequest $request)
    {
        $products = QueryBuilder::for(Product::class)
            ->allowedFilters([
                'name',
                'category_id',
                'sellingPrice',
                'initialPrice',
                'type',
                'category.name',
                'unit',
                'show',
                ...ComparisonFilter::setFilters('stock', ['gt', 'ge', 'lt', 'le', 'eq', 'ne']),
                AllowedFilter::custom('global', new SearchFields, 'name,sku,barcode'),
            ])
            ->allowedIncludes(['category', 'images'])
            ->orderByDesc('created_at')
            ->simplePaginate();

        return $this->buildResponse()
            ->setData(ProductCollection::collection($products))
            ->present();
    }

    /**
     * Create a new product
     *
     * @response array{success: true, message: "success creating items"}
     */
    public function store(ProductRequest $request)
    {
        $request->created();

        return $this->buildResponse()
            ->setMessage('success creating items')
            ->present();
    }

    /**
     * Show a product
     *
     * @response ProductCollection
     */
    public function show(Product $product)
    {
        $product->load(['category', 'stocks']);
        $product = new ProductCollection($product);

        return $this->buildResponse()
            ->setData($product)
            ->present();
    }

    /**
     * Update a product
     *
     * @response array{success: true, message: "success updating items"}
     */
    public function update(ProductRequest $request)
    {
        $request->updated();

        return $this->buildResponse()
            ->setMessage('success updating items')
            ->present();
    }

    /**
     * Delete a product
     *
     * @response array{success: true, message: "success deleting items"}
     */
    public function destroy(Product $product, ProductRequest $request)
    {
        $request->deleteImages();
        $product->delete();

        return $this->buildResponse()
            ->setMessage('success deleting items')
            ->present();
    }
}
