<?php

namespace App\Http\Controllers\Api\Tenants\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Tenants\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Get list of categories for storefront
     *
     * @summary List all categories
     * @operationId storefront.categories.index
     * @tags Storefront
     * 
     * @response array{
     *   success: true,
     *   data: array[
     *     array{
     *       id: int,
     *       name: string,
     *       created_at: string,
     *       updated_at: string
     *     }
     *   ]
     * }
     */
    public function index()
    {
        $categories = Category::orderBy('name', 'asc')->get();

        return $this->buildResponse()
            ->setData($categories)
            ->present();
    }
}
