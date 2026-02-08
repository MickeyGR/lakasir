<?php

namespace App\Http\Requests\Tenants\Master;

use Illuminate\Foundation\Http\FormRequest;

class ProductIndexRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'page' => ['integer', 'min:1'],
            'per_page' => ['integer', 'min:1', 'max:100'],
            'sort' => ['string', 'nullable'],
            'filter.global' => ['string', 'nullable'],
            'filter.name' => ['string', 'nullable'],
            'filter.category_id' => ['integer', 'nullable'],
            'filter.sellingPrice' => ['numeric', 'nullable'],
            'filter.initialPrice' => ['numeric', 'nullable'],
            'filter.type' => ['string', 'nullable'],
            'filter.unit' => ['string', 'nullable'],
            'filter.show' => ['boolean', 'nullable'],
            'filter.stock' => ['array', 'nullable'], // For comparison filters
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'filter.global' => 'Global Search',
            'filter.name' => 'Product Name',
        ];
    }
}
