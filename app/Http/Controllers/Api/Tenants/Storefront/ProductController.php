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

class ProductController extends Controller
{
    /**
     * Get list of products for storefront
     *
     * @summary List visible products
     * @operationId storefront.products.index
     * @tags Storefront
     * 
     * @queryParam page integer Page number for pagination. Example: 1
     * @queryParam per_page integer Items per page (default: 15). Example: 20
     * @queryParam sort string Sort by field. Prefix with - for descending. Allowed: name, selling_price, created_at. Example: -selling_price
     * @queryParam filter[name] string Filter by product name. Example: Laptop
     * @queryParam filter[category_id] integer Filter by category ID. Example: 5
     * @queryParam filter[category.name] string Filter by category name (exact match). Example: Electronics
     * @queryParam filter[type] string Filter by product type. Allowed: product, service. Example: product
     * @queryParam filter[unit] string Filter by unit. Example: pcs
     * @queryParam filter[stock-gt] integer Filter stock greater than. Example: 10
     * @queryParam filter[stock-ge] integer Filter stock greater or equal. Example: 10
     * @queryParam filter[stock-lt] integer Filter stock less than. Example: 50
     * @queryParam filter[stock-le] integer Filter stock less or equal. Example: 50
     * @queryParam filter[stock-eq] integer Filter stock equal to. Example: 20
     * @queryParam filter[stock-ne] integer Filter stock not equal to. Example: 0
     * @queryParam filter[global] string Search across name, SKU, and barcode. Example: LAP-001
     * @queryParam include string Include relations (comma separated). Allowed: category,images. Example: category,images
     * 
     * @response array{
     *   success: true,
     *   data: array{
     *     data: array[
     *       array{
     *         id: int,
     *         name: string,
     *         category: array{id: int, name: string},
     *         category_id: int,
     *         initial_price: float,
     *         selling_price: float,
     *         type: string,
     *         unit: string,
     *         stock: int,
     *         is_non_stock: bool,
     *         hero_images: string,
     *         sku: string,
     *         barcode: ?string,
     *         show: int
     *       }
     *     ],
     *     links: array{first: ?string, prev: ?string, next: ?string},
     *     meta: array{current_page: int, from: ?int, path: string, per_page: int, to: ?int}
     *   },
     *   message: ?string
     * }
     */
    public function index(ProductIndexRequest $request)
    {
        $products = QueryBuilder::for(Product::query()->storefrontVisible())
            ->allowedFilters([
                'name',
                'category_id',
                AllowedFilter::exact('sellingPrice', 'selling_price'),
                'type',
                'category.name',
                'unit',
                ...ComparisonFilter::setFilters('stock', ['gt', 'ge', 'lt', 'le', 'eq', 'ne']),
                AllowedFilter::custom('global', new SearchFields, 'name,sku,barcodes.code'),
            ])
            ->allowedIncludes(['category', 'images'])
            ->allowedSorts(['name', 'selling_price', 'created_at'])
            ->orderByDesc('created_at')
            ->simplePaginate($request->per_page);

        return $this->buildResponse()
            ->setData(ProductCollection::collection($products))
            ->present();
    }

    /**
     * Show a product details
     *
     * @summary Get product details
     * @operationId storefront.products.show
     * @tags Storefront
     * 
     * @urlParam id integer required Product ID. Example: 101
     * 
     * @response array{
     *   success: true,
     *   data: array{
     *     id: int,
     *     name: string,
     *     category: array{id: int, name: string},
     *     category_id: int,
     *     initial_price: float,
     *     selling_price: float,
     *     type: string,
     *     unit: string,
     *     stock: int,
     *     is_non_stock: bool,
     *     hero_images: string,
     *     sku: string,
     *     barcode: ?string,
     *     show: int,
     *     stocks: array[]
     *   },
     *   message: ?string
     * }
     * 
     * @response 404 {"success":false,"message":"Product not found"}
     */
    public function show(int $id)
    {
        $product = Product::query()
            ->storefrontVisible()
            ->with(['category', 'stocks'])
            ->find($id);

        if (! $product) {
            return $this->buildResponse()
                ->setCode(404)
                ->setMessage('Product not found')
                ->present();
        }

        return $this->buildResponse()
            ->setData(new ProductCollection($product))
            ->present();
    }
}
