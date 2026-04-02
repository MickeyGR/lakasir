<?php

namespace App\Http\Requests\Tenants\Storefront;

use Illuminate\Foundation\Http\FormRequest;

class ProductIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'page' => ['integer', 'min:1'],
            'per_page' => ['integer', 'min:1', 'max:100'],
            'sort' => ['string', 'nullable'],
            'include' => ['string', 'nullable'],
            'filter.global' => ['string', 'nullable'],
            'filter.name' => ['string', 'nullable'],
            'filter.category_id' => ['integer', 'nullable'],
            'filter.category.name' => ['string', 'nullable'],
            'filter.type' => ['string', 'nullable'],
            'filter.unit' => ['string', 'nullable'],
            'filter.stock-gt' => ['numeric', 'nullable'],
            'filter.stock-ge' => ['numeric', 'nullable'],
            'filter.stock-lt' => ['numeric', 'nullable'],
            'filter.stock-le' => ['numeric', 'nullable'],
            'filter.stock-eq' => ['numeric', 'nullable'],
            'filter.stock-ne' => ['numeric', 'nullable'],
        ];
    }
}
