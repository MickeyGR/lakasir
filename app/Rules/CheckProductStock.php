<?php

namespace App\Rules;

use App\Models\Tenants\PriceUnit;
use App\Models\Tenants\Product;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;

class CheckProductStock implements DataAwareRule, ValidationRule
{
    /**
     * All of the data under validation.
     *
     * @var array
     */
    protected $data = [];

    /**
     * key index from the data
     *
     * @var int
     */
    protected $index;

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $index = Str::of($attribute)->explode('.')[1];
        $dataProduct = $this->data['products'][$index];
        $product = Product::find($dataProduct['product_id']);
        if (! $product) {
            $fail('The product is not found.');

            return;
        }
        if ($product->is_non_stock) {
            return;
        }
        $requestedQty = (float) $value;
        if (isset($dataProduct['price_unit_id']) && $dataProduct['price_unit_id'] != null) {
            $priceUnit = PriceUnit::query()->find($dataProduct['price_unit_id']);
            if (! $priceUnit) {
                $fail('The selected price unit is invalid.');

                return;
            }
            if ((int) $priceUnit->product_id !== (int) $product->id) {
                $fail('The selected price unit does not belong to the selected product.');

                return;
            }
            if ((float) $priceUnit->stock <= 0) {
                $fail('The selected price unit has invalid stock conversion value.');

                return;
            }

            $requestedQty = $priceUnit->stock * $dataProduct['qty'];
        }
        $availableStock = $product->stocks()->exists()
            ? (float) $product->stocks()->sum('stock')
            : (float) $product->stock;
        $bool = $availableStock < $requestedQty;

        if ($bool) {
            $fail($this->message());
        }
    }

    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The stock of product less than from you request.';
    }
}
